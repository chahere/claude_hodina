<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AiChatbotSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Réglage unique (singleton) du fournisseur IA utilisé par le chatbot.
 * La clé API n'est jamais stockée en clair : voir AiChatbotCredentialCipher.
 */
#[ORM\Entity(repositoryClass: AiChatbotSettingRepository::class)]
#[ORM\Table(name: 'ai_chatbot_setting')]
class AiChatbotSetting
{
    public const PROVIDER_MOCK = 'mock';
    public const PROVIDER_ANTHROPIC = 'anthropic';
    public const PROVIDER_OPENAI = 'openai';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $provider = self::PROVIDER_MOCK;

    #[ORM\Column(length: 120)]
    private string $model = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $apiKeyEncrypted = null;

    /**
     * Saisie write-only dans le backoffice. Jamais persistée telle quelle :
     * elle sert uniquement à produire apiKeyEncrypted, puis est effacée.
     */
    private ?string $plainApiKey = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): self
    {
        $this->provider = $provider;
        $this->touch();

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = trim($model);
        $this->touch();

        return $this;
    }

    public function getApiKeyEncrypted(): ?string
    {
        return $this->apiKeyEncrypted;
    }

    public function setApiKeyEncrypted(?string $apiKeyEncrypted): self
    {
        $this->apiKeyEncrypted = $apiKeyEncrypted;
        $this->touch();

        return $this;
    }

    public function hasApiKey(): bool
    {
        return $this->apiKeyEncrypted !== null && $this->apiKeyEncrypted !== '';
    }

    public function getPlainApiKey(): ?string
    {
        return $this->plainApiKey;
    }

    public function setPlainApiKey(?string $plainApiKey): self
    {
        $this->plainApiKey = $plainApiKey;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
