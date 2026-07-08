<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EmailLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailLogRepository::class)]
#[ORM\Table(name: 'email_log')]
#[ORM\Index(name: 'idx_email_log_customer_order', columns: ['customer_order_id'])]
#[ORM\Index(name: 'idx_email_log_customer', columns: ['customer_id'])]
#[ORM\Index(name: 'IDX_EMAIL_LOG_STATUS', columns: ['status'])]
#[ORM\Index(name: 'IDX_EMAIL_LOG_EVENT_KEY', columns: ['event_key'])]
class EmailLog
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_SENT = 'SENT';
    public const STATUS_FAILED = 'FAILED';

    public const EVENT_ORDER_CREATED = 'ORDER_CREATED';
    public const EVENT_SELLER_COLLECTION_CODE = 'SELLER_COLLECTION_CODE';
    public const EVENT_CUSTOMER_DELIVERY_CODE = 'CUSTOMER_DELIVERY_CODE';
    public const EVENT_ORDER_STATUS_CONFIRMED = 'ORDER_STATUS_CONFIRMED';
    public const EVENT_ORDER_STATUS_PREPARING = 'ORDER_STATUS_PREPARING';
    public const EVENT_ORDER_STATUS_READY_FOR_PICKUP = 'ORDER_STATUS_READY_FOR_PICKUP';
    public const EVENT_ORDER_STATUS_PICKED_UP = 'ORDER_STATUS_PICKED_UP';
    public const EVENT_ORDER_STATUS_DELIVERED = 'ORDER_STATUS_DELIVERED';
    public const EVENT_ORDER_STATUS_CANCELED = 'ORDER_STATUS_CANCELED';
    public const EVENT_ORDER_SELLER_COLLECTIONS_COMPLETED = 'ORDER_SELLER_COLLECTIONS_COMPLETED';
    public const EVENT_COURIER_PAYOUT_RECAP = 'COURIER_PAYOUT_RECAP';
    public const EVENT_SUPPORT_TICKET_CREATED = 'SUPPORT_TICKET_CREATED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CustomerOrder $customerOrder = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Customer $customer = null;

    #[ORM\Column(length: 180)]
    private string $recipientEmail = '';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $fromEmail = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $fromName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $replyToEmail = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $replyToName = null;

    #[ORM\Column(length: 255)]
    private string $subject = '';

    #[ORM\Column(length: 100)]
    private string $templateKey = '';

    #[ORM\Column(length: 100)]
    private string $eventKey = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $body = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->recipientEmail, $this->status);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomerOrder(): ?CustomerOrder
    {
        return $this->customerOrder;
    }

    public function setCustomerOrder(?CustomerOrder $customerOrder): self
    {
        $this->customerOrder = $customerOrder;

        return $this;
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

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(string $recipientEmail): self
    {
        $this->recipientEmail = trim($recipientEmail);

        return $this;
    }

    public function getFromEmail(): ?string
    {
        return $this->fromEmail;
    }

    public function setFromEmail(?string $fromEmail): self
    {
        $fromEmail = $fromEmail !== null ? trim($fromEmail) : null;
        $this->fromEmail = $fromEmail !== '' ? $fromEmail : null;

        return $this;
    }

    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    public function setFromName(?string $fromName): self
    {
        $fromName = $fromName !== null ? trim($fromName) : null;
        $this->fromName = $fromName !== '' ? $fromName : null;

        return $this;
    }

    public function getReplyToEmail(): ?string
    {
        return $this->replyToEmail;
    }

    public function setReplyToEmail(?string $replyToEmail): self
    {
        $replyToEmail = $replyToEmail !== null ? trim($replyToEmail) : null;
        $this->replyToEmail = $replyToEmail !== '' ? $replyToEmail : null;

        return $this;
    }

    public function getReplyToName(): ?string
    {
        return $this->replyToName;
    }

    public function setReplyToName(?string $replyToName): self
    {
        $replyToName = $replyToName !== null ? trim($replyToName) : null;
        $this->replyToName = $replyToName !== '' ? $replyToName : null;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getTemplateKey(): string
    {
        return $this->templateKey;
    }

    public function setTemplateKey(string $templateKey): self
    {
        $this->templateKey = $templateKey;

        return $this;
    }

    public function getEventKey(): string
    {
        return $this->eventKey;
    }

    public function setEventKey(string $eventKey): self
    {
        $this->eventKey = $eventKey;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $body = $body !== null ? trim($body) : null;
        $this->body = $body !== '' ? $body : null;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
