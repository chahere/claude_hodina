<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Entity\ChatbotMessage;
use App\Entity\Customer;
use App\Service\ChatbotConversationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Interface de conversation du chatbot IA dans /mon-compte. Le frontend
 * n'échange que du texte avec cet endpoint : jamais d'objet commande/client
 * brut. Réservé aux clients connectés (voir aussi security.yaml).
 */
#[IsGranted('ROLE_USER')]
#[Route('/mon-compte/assistant')]
final class ChatbotController extends AbstractController
{
    #[Route('', name: 'client_chatbot_index', methods: ['GET'])]
    public function index(ChatbotConversationService $conversationService): Response
    {
        $customer = $this->getCurrentCustomer();
        $conversation = $conversationService->getOrCreateActiveConversation($customer);

        $messages = array_values(array_filter(
            $conversation->getMessages()->toArray(),
            static fn (ChatbotMessage $message): bool => in_array($message->getRole(), [ChatbotMessage::ROLE_USER, ChatbotMessage::ROLE_ASSISTANT], true)
        ));

        return $this->render('client/chatbot/index.html.twig', [
            'messages' => $messages,
        ]);
    }

    #[Route('/message', name: 'client_chatbot_send', methods: ['POST'])]
    public function send(
        Request $request,
        ChatbotConversationService $conversationService,
        #[Autowire(service: 'limiter.chatbot_per_customer')]
        RateLimiterFactory $perCustomerLimiterFactory,
        #[Autowire(service: 'limiter.chatbot_per_ip')]
        RateLimiterFactory $perIpLimiterFactory,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        $payload = is_array($payload) ? $payload : [];

        if (!$this->isCsrfTokenValid('chatbot_send', (string) ($payload['_token'] ?? ''))) {
            return $this->json(['error' => 'Jeton de sécurité invalide, recharge la page.'], Response::HTTP_BAD_REQUEST);
        }

        $customer = $this->getCurrentCustomer();

        $customerLimiter = $perCustomerLimiterFactory->create('customer_' . $customer->getId());
        $ipLimiter = $perIpLimiterFactory->create('ip_' . ($request->getClientIp() ?? 'unknown'));

        if (!$customerLimiter->consume(1)->isAccepted() || !$ipLimiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de messages envoyés. Réessaie dans quelques instants.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            return $this->json(['error' => 'Le message ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($message) > 2000) {
            return $this->json(['error' => 'Message trop long (2000 caractères maximum).'], Response::HTTP_BAD_REQUEST);
        }

        $conversation = $conversationService->getOrCreateActiveConversation($customer);
        $reply = $conversationService->reply($conversation, $message);

        return $this->json(['reply' => $reply]);
    }

    private function getCurrentCustomer(): Customer
    {
        $user = $this->getUser();

        if (!$user instanceof Customer) {
            throw $this->createAccessDeniedException('Compte client requis.');
        }

        return $user;
    }
}
