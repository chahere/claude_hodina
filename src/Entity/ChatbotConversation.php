<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ChatbotConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatbotConversationRepository::class)]
#[ORM\Table(name: 'chatbot_conversation')]
#[ORM\Index(name: 'IDX_CHATBOT_CONVERSATION_CUSTOMER', columns: ['customer_id'])]
class ChatbotConversation
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_ESCALATED = 'ESCALATED';
    public const STATUS_CLOSED = 'CLOSED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Customer $customer;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\OneToOne(mappedBy: 'chatbotConversation', targetEntity: SupportTicket::class)]
    private ?SupportTicket $supportTicket = null;

    /**
     * @var Collection<int, ChatbotMessage>
     */
    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: ChatbotMessage::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function getSupportTicket(): ?SupportTicket
    {
        return $this->supportTicket;
    }

    /**
     * @return Collection<int, ChatbotMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(ChatbotMessage $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setConversation($this);
        }

        $this->touch();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
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
