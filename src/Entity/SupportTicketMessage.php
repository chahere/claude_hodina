<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SupportTicketMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportTicketMessageRepository::class)]
#[ORM\Table(name: 'support_ticket_message')]
#[ORM\Index(name: 'IDX_SUPPORT_TICKET_MESSAGE_TICKET', columns: ['support_ticket_id'])]
class SupportTicketMessage
{
    public const SENDER_CUSTOMER = 'CUSTOMER';
    public const SENDER_ADMIN = 'ADMIN';
    public const SENDER_SYSTEM = 'SYSTEM';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SupportTicket::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(name: 'support_ticket_id', nullable: false, onDelete: 'CASCADE')]
    private SupportTicket $ticket;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(name: 'author_customer_id', nullable: true, onDelete: 'SET NULL')]
    private ?Customer $authorCustomer = null;

    #[ORM\Column(length: 20)]
    private string $senderType = self::SENDER_CUSTOMER;

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

    public function getTicket(): SupportTicket
    {
        return $this->ticket;
    }

    public function setTicket(SupportTicket $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getAuthorCustomer(): ?Customer
    {
        return $this->authorCustomer;
    }

    public function setAuthorCustomer(?Customer $authorCustomer): self
    {
        $this->authorCustomer = $authorCustomer;

        return $this;
    }

    public function getSenderType(): string
    {
        return $this->senderType;
    }

    public function setSenderType(string $senderType): self
    {
        $this->senderType = $senderType;

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
