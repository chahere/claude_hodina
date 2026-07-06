<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SupportTicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportTicketRepository::class)]
#[ORM\Table(name: 'support_ticket')]
#[ORM\UniqueConstraint(name: 'UNIQ_SUPPORT_TICKET_CHATBOT_CONVERSATION', columns: ['chatbot_conversation_id'])]
#[ORM\Index(name: 'IDX_SUPPORT_TICKET_CUSTOMER', columns: ['customer_id'])]
#[ORM\Index(name: 'IDX_SUPPORT_TICKET_STATUS', columns: ['status'])]
class SupportTicket
{
    public const ORIGIN_CONTACT_FORM = 'CONTACT_FORM';
    public const ORIGIN_CHATBOT_ESCALATION = 'CHATBOT_ESCALATION';

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_CLOSED = 'CLOSED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Customer $customer = null;

    #[ORM\OneToOne(inversedBy: 'supportTicket', targetEntity: ChatbotConversation::class)]
    #[ORM\JoinColumn(name: 'chatbot_conversation_id', nullable: true, onDelete: 'SET NULL')]
    private ?ChatbotConversation $chatbotConversation = null;

    #[ORM\Column(length: 30)]
    private string $origin = self::ORIGIN_CONTACT_FORM;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(length: 150)]
    private string $contactName = '';

    #[ORM\Column(length: 180)]
    private string $contactEmail = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(length: 200)]
    private string $subject = '';

    /**
     * @var Collection<int, SupportTicketMessage>
     */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: SupportTicketMessage::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->messages = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf('#%s — %s', $this->id ?? '?', $this->subject);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getChatbotConversation(): ?ChatbotConversation
    {
        return $this->chatbotConversation;
    }

    public function setChatbotConversation(?ChatbotConversation $chatbotConversation): self
    {
        $this->chatbotConversation = $chatbotConversation;

        return $this;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function setOrigin(string $origin): self
    {
        $this->origin = $origin;

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

        if ($status === self::STATUS_CLOSED) {
            $this->closedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getContactName(): string
    {
        return $this->contactName;
    }

    public function setContactName(string $contactName): self
    {
        $this->contactName = trim($contactName);

        return $this;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): self
    {
        $this->contactEmail = trim($contactEmail);

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): self
    {
        $contactPhone = $contactPhone !== null ? trim($contactPhone) : null;
        $this->contactPhone = $contactPhone !== '' ? $contactPhone : null;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = trim($subject);

        return $this;
    }

    /**
     * @return Collection<int, SupportTicketMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(SupportTicketMessage $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setTicket($this);
        }

        $this->touch();

        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
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
