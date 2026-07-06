<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Chiffrement de la clé API du fournisseur LLM. Réutilise le même mécanisme
 * AES-256-GCM que CustomerDeliveryCodeService (clé dérivée de kernel.secret),
 * sans dépendre de cette classe : ce projet duplique volontairement ces
 * petites méthodes de chiffrement plutôt que de les partager via un trait.
 */
final class AiChatbotCredentialCipher
{
    private const CIPHER = 'aes-256-gcm';

    public function __construct(
        #[Autowire('%kernel.secret%')]
        private readonly string $appSecret,
    ) {
    }

    public function encrypt(string $plainText): string
    {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plainText, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($ciphertext === false) {
            throw new \RuntimeException('Impossible de chiffrer la clé API du fournisseur IA.');
        }

        $payload = [
            'v' => 1,
            'alg' => self::CIPHER,
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode($ciphertext),
        ];

        return base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function decrypt(string $cipherText): string
    {
        $decodedPayload = base64_decode($cipherText, true);
        if ($decodedPayload === false) {
            throw new \DomainException('Clé API du fournisseur IA illisible.');
        }

        try {
            $payload = json_decode($decodedPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \DomainException('Clé API du fournisseur IA invalide.');
        }

        if (!is_array($payload) || ($payload['alg'] ?? '') !== self::CIPHER) {
            throw new \DomainException('Clé API du fournisseur IA invalide.');
        }

        $iv = base64_decode((string) ($payload['iv'] ?? ''), true);
        $tag = base64_decode((string) ($payload['tag'] ?? ''), true);
        $ciphertext = base64_decode((string) ($payload['data'] ?? ''), true);

        if ($iv === false || $tag === false || $ciphertext === false) {
            throw new \DomainException('Clé API du fournisseur IA incomplète.');
        }

        $plainText = openssl_decrypt($ciphertext, self::CIPHER, $this->getEncryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($plainText === false) {
            throw new \DomainException('Clé API du fournisseur IA impossible à déchiffrer.');
        }

        return $plainText;
    }

    private function getEncryptionKey(): string
    {
        if (trim($this->appSecret) === '') {
            throw new \RuntimeException('APP_SECRET manquant : impossible de chiffrer la clé API du fournisseur IA.');
        }

        return hash('sha256', $this->appSecret, true);
    }
}
