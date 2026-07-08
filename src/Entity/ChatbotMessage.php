<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ChatbotMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatbotMessageRepository::class)]
#[ORM\Table(name: 'chatbot_message')]
#[ORM\Index(name: 'IDX_CHATBOT_MESSAGE_CONVERSATION', columns: ['chatbot_conversation_id'])]
class ChatbotMessage
{
    public const ROLE_USER = 'USER';
    public const ROLE_ASSISTANT = 'ASSISTANT';
    public const ROLE_SYSTEM = 'SYSTEM';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ChatbotConversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(name: 'chatbot_conversation_id', nullable: false, onDelete: 'CASCADE')]
    private ChatbotConversation $conversation;

    #[ORM\Column(length: 20)]
    private string $role = self::ROLE_USER;

    #[ORM\Column(type: 'text')]
    private string $content = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): ChatbotConversation
    {
        return $this->conversation;
    }

    public function setConversation(ChatbotConversation $conversation): self
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = trim($content);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
