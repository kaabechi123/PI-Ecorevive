<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File; // Add this use statement
use Vich\UploaderBundle\Mapping\Annotation as Vich; // Add this use statement


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[Vich\Uploadable] // Add this annotation to make the entity uploadable
class User implements UserInterface, PasswordAuthenticatedUserInterface, \Serializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Username is required.")]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Email is required.")]
    #[Assert\Email(message: "The email '{{ value }}' is not a valid email.")]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Password is required.")]
    #[Assert\Length(min: 4, minMessage: "Password must be at least {{ limit }} characters long.")]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Role is required.")]
    private ?string $role = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $token = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $otp = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    // Add this field for VichUploaderBundle
    #[Vich\UploadableField(mapping: 'user_avatar', fileNameProperty: 'avatar')]
    private ?File $avatarFile = null;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'organizer')]
    private Collection $organizedEvents;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'participants')]
    private Collection $participatedEvents;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'owner')]
    private Collection $tickets;

    public function __construct()
    {
        $this->organizedEvents = new ArrayCollection();
        $this->participatedEvents = new ArrayCollection();
        $this->tickets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getOTP(): ?string
    {
        return $this->otp;
    }

    public function setOTP(?string $otp): static
    {
        $this->otp = $otp;

        return $this;
    }

    // Add getter and setter for avatarFile
    public function getAvatarFile(): ?File
    {
        return $this->avatarFile;
    }

    public function setAvatarFile(?File $avatarFile = null): void
    {
        $this->avatarFile = $avatarFile;

        // Update the avatar field only if a file is uploaded
        if ($avatarFile) {
            $this->avatar = $avatarFile->getFilename(); // Or let Vich handle the filename
        }
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    // Implement getUserIdentifier() from UserInterface
    public function getUserIdentifier(): string
    {
        return $this->email;  // Using email as the user identifier
    }

    // Implement eraseCredentials() from UserInterface
    public function eraseCredentials(): void
    {
        // Erase sensitive data (like plain-text passwords)
        // If you store plain-text passwords temporarily, clear them here
    }

    // Implement getRoles() from UserInterface
    public function getRoles(): array
    {
        // Return roles as an array; if role is stored as a string, return it as an array
        return [$this->role];  // Assuming 'role' is a string, like 'ROLE_USER' or 'ROLE_ADMIN'
    }

    // Implement \Serializable interface
    public function serialize(): string
    {
        return serialize([
            $this->id,
            $this->username,
            $this->email,
            $this->password,
            $this->role,
            $this->token,
            $this->otp,
        ]);
    }

    public function unserialize($serialized): void
    {
        list(
            $this->id,
            $this->username,
            $this->email,
            $this->password,
            $this->role,
            $this->token,
            $this->otp,
        ) = unserialize($serialized);
    }

    /**
     * @return Collection<int, Event>
     */
    public function getOrganizedEvents(): Collection
    {
        return $this->organizedEvents;
    }

    public function addOrganizedEvent(Event $organizedEvent): static
    {
        if (!$this->organizedEvents->contains($organizedEvent)) {
            $this->organizedEvents->add($organizedEvent);
            $organizedEvent->setOrganizer($this);
        }

        return $this;
    }

    public function removeOrganizedEvent(Event $organizedEvent): static
    {
        if ($this->organizedEvents->removeElement($organizedEvent)) {
            // set the owning side to null (unless already changed)
            if ($organizedEvent->getOrganizer() === $this) {
                $organizedEvent->setOrganizer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getParticipatedEvents(): Collection
    {
        return $this->participatedEvents;
    }

    public function addParticipatedEvent(Event $participatedEvent): static
    {
        if (!$this->participatedEvents->contains($participatedEvent)) {
            $this->participatedEvents->add($participatedEvent);
            $participatedEvent->addParticipant($this);
        }

        return $this;
    }

    public function removeParticipatedEvent(Event $participatedEvent): static
    {
        if ($this->participatedEvents->removeElement($participatedEvent)) {
            $participatedEvent->removeParticipant($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setOwner($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getOwner() === $this) {
                $ticket->setOwner(null);
            }
        }

        return $this;
    }
}