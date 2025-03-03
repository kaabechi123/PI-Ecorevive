<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\HasLifecycleCallbacks] // Enable lifecycle callbacks

class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    private ?Event $event = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    private ?User $owner = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $qrcode = null; 

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
    public function getQrcode(): ?string
    {
        return $this->qrcode;
    }

    public function setQrcode(?string $qrcode): static
    {
        $this->qrcode = $qrcode;

        return $this;
    }
    #[ORM\PrePersist] // This method will be called automatically before the ticket is persisted (created)
public function generateQrcode(): void
{
    // Ensure the ticket has an owner and event
    if ($this->getOwner() && $this->getEvent()) {
        // Prepare the ticket details as a JSON string
        $ticketDetails = json_encode([
            'ticket_id' => $this->getId(),
            'owner' => $this->getOwner()->getUsername(), 
            'event' => $this->getEvent()->getName(), 
        ]);

        // Store the ticket details in the QR code field
        $this->setQrcode($ticketDetails);
    } else {
        // Handle case where owner or event is not set (optional)
        throw new \RuntimeException('Ticket must have an owner and an event to generate a QR code.');
    }
}
}