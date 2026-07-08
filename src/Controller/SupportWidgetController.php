<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\HodinaSetting;
use App\Service\SupportWidgetAnswerService;
use App\Service\SupportWidgetEscalationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Widget flottant "Assistant Hodina" : moteur à règles (SupportWidgetAnswerService),
 * accessible aux visiteurs anonymes ET aux clients connectés, sur tout le site
 * public (inclus une seule fois dans base.html.twig). Aucun appel IA ici —
 * cf. Client\ChatbotController pour le chatbot IA réel, réservé aux clients
 * connectés dans /mon-compte/assistant.
 */
#[Route('/assistant-hodina')]
final class SupportWidgetController extends AbstractController
{
    private const MAX_MESSAGE_LENGTH = 2000;

    #[Route('/message', name: 'support_widget_message', methods: ['POST'])]
    public function message(
        Request $request,
        SupportWidgetAnswerService $answerService,
        #[Autowire(service: 'limiter.widget_message_per_ip')]
        RateLimiterFactory $limiterFactory,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        $payload = is_array($payload) ? $payload : [];

        if (!$this->isCsrfTokenValid('widget_chat_message', (string) ($payload['_token'] ?? ''))) {
            return $this->json(['error' => 'Jeton de sécurité invalide, recharge la page.'], Response::HTTP_BAD_REQUEST);
        }

        $limiter = $limiterFactory->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de messages envoyés. Réessaie dans quelques instants.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            return $this->json(['error' => 'Le message ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
            return $this->json(['error' => 'Message trop long.'], Response::HTTP_BAD_REQUEST);
        }

        $customer = $this->getUser();
        $isConnected = $customer instanceof Customer;

        $reply = $answerService->answer($message, $isConnected);

        return $this->json([
            'reply' => [
                'text' => $reply->text,
                'quickReplies' => $reply->quickReplies,
                'suggestEscalation' => $reply->suggestEscalation,
                'action' => $this->resolveAction($reply->actionKey, $isConnected),
            ],
        ]);
    }

    #[Route('/escalade', name: 'support_widget_escalate', methods: ['POST'])]
    public function escalate(
        Request $request,
        SupportWidgetEscalationService $escalationService,
        EntityManagerInterface $entityManager,
        #[Autowire(service: 'limiter.widget_escalation_per_ip')]
        RateLimiterFactory $limiterFactory,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        $payload = is_array($payload) ? $payload : [];

        if (!$this->isCsrfTokenValid('widget_chat_escalate', (string) ($payload['_token'] ?? ''))) {
            return $this->json(['error' => 'Jeton de sécurité invalide, recharge la page.'], Response::HTTP_BAD_REQUEST);
        }

        // Piège à robots (même principe que ContactFormType::website) : rempli
        // -> on fait semblant que tout va bien, sans créer de ticket.
        if (trim((string) ($payload['website'] ?? '')) !== '') {
            return $this->json(['ok' => true, 'messengerUrl' => null]);
        }

        $limiter = $limiterFactory->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de demandes envoyées. Réessaie un peu plus tard.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $message = trim((string) ($payload['message'] ?? ''));
        if (mb_strlen($message) < 10) {
            return $this->json(['error' => 'Merci de préciser votre demande (10 caractères minimum).'], Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
            return $this->json(['error' => 'Message trop long.'], Response::HTTP_BAD_REQUEST);
        }

        $recentExchange = $this->sanitizeRecentExchange($payload['recentExchange'] ?? null);
        $customer = $this->getUser();

        if ($customer instanceof Customer) {
            $escalationService->escalateForCustomer($customer, $message, $recentExchange);

            return $this->json(['ok' => true, 'messengerUrl' => $this->resolveMessengerUrl($entityManager)]);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? ''));
        $validEmail = $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        if (mb_strlen($name) < 2) {
            return $this->json(['error' => 'Merci d’indiquer votre nom.'], Response::HTTP_BAD_REQUEST);
        }

        if ($email !== '' && !$validEmail) {
            return $this->json(['error' => 'Cet e-mail n’est pas valide.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$validEmail && $phone === '') {
            return $this->json(['error' => 'Merci d’indiquer un e-mail ou un numéro de téléphone.'], Response::HTTP_BAD_REQUEST);
        }

        $escalationService->escalateForGuest($name, $validEmail ? $email : '', $phone !== '' ? $phone : null, $message, $recentExchange);

        return $this->json(['ok' => true, 'messengerUrl' => $this->resolveMessengerUrl($entityManager)]);
    }

    /** @return array{label: string, url: string}|null */
    private function resolveAction(?string $actionKey, bool $isConnected): ?array
    {
        return match (true) {
            $actionKey === 'delivery_info' => ['label' => 'Infos livraison', 'url' => $this->generateUrl('app_carnet_livraison')],
            $actionKey === 'my_orders' && $isConnected => ['label' => 'Mes commandes', 'url' => $this->generateUrl('client_orders_index')],
            $actionKey === 'login' => ['label' => 'Se connecter', 'url' => $this->generateUrl('app_login')],
            $actionKey === 'catalogue' => ['label' => 'Voir le catalogue', 'url' => $this->generateUrl('product_catalogue')],
            default => null,
        };
    }

    /**
     * @return list<array{role: string, text: string}>
     */
    private function sanitizeRecentExchange(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $sanitized = [];

        foreach (array_slice($raw, -10) as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $role = ($entry['role'] ?? '') === 'user' ? 'user' : 'assistant';
            $text = mb_substr(trim((string) ($entry['text'] ?? '')), 0, 500);

            if ($text !== '') {
                $sanitized[] = ['role' => $role, 'text' => $text];
            }
        }

        return $sanitized;
    }

    private function resolveMessengerUrl(EntityManagerInterface $entityManager): ?string
    {
        $setting = $entityManager->getRepository(HodinaSetting::class)
            ->findOneBy(['settingKey' => HodinaSetting::KEY_SUPPORT_MESSENGER_URL]);

        if (!$setting instanceof HodinaSetting) {
            return null;
        }

        $url = trim((string) $setting->getValue());

        return $url !== '' && filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : null;
    }
}
