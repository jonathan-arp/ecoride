<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'receivedReviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $driver = null;

    #[ORM\ManyToOne(inversedBy: 'givenReviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $passenger = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Carshare $carshare = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reservation $reservation = null;

    #[ORM\Column]
    private ?int $rating = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $moderatedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $moderatedBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'EN_ATTENTE'; // En attente de modération
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDriver(): ?User
    {
        return $this->driver;
    }

    public function setDriver(?User $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function getPassenger(): ?User
    {
        return $this->passenger;
    }

    public function setPassenger(?User $passenger): static
    {
        $this->passenger = $passenger;

        return $this;
    }

    public function getCarshare(): ?Carshare
    {
        return $this->carshare;
    }

    public function setCarshare(?Carshare $carshare): static
    {
        $this->carshare = $carshare;

        return $this;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): static
    {
        $this->reservation = $reservation;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }
        
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModeratedAt(): ?\DateTimeImmutable
    {
        return $this->moderatedAt;
    }

    public function setModeratedAt(?\DateTimeImmutable $moderatedAt): static
    {
        $this->moderatedAt = $moderatedAt;

        return $this;
    }

    public function getModeratedBy(): ?User
    {
        return $this->moderatedBy;
    }

    public function setModeratedBy(?User $moderatedBy): static
    {
        $this->moderatedBy = $moderatedBy;

        return $this;
    }

    /**
     * Check if review is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'PUBLIE';
    }

    /**
     * Check if review is pending moderation
     */
    public function isPending(): bool
    {
        return $this->status === 'EN_ATTENTE';
    }

    /**
     * Check if review is eliminated
     */
    public function isEliminated(): bool
    {
        return $this->status === 'ELIMINE';
    }

    /**
     * Publish the review (moderation action)
     */
    public function publish(User $moderator): void
    {
        $this->status = 'PUBLIE';
        $this->moderatedAt = new \DateTimeImmutable();
        $this->moderatedBy = $moderator;
    }

    /**
     * Eliminate the review (moderation action)
     */
    public function eliminate(User $moderator): void
    {
        $this->status = 'ELIMINE';
        $this->moderatedAt = new \DateTimeImmutable();
        $this->moderatedBy = $moderator;
    }

    /**
     * Get status in French
     */
    public function getStatusInFrench(): string
    {
        return match($this->status) {
            'EN_ATTENTE' => 'En attente',
            'PUBLIE' => 'Publié',
            'ELIMINE' => 'Éliminé',
            default => $this->status
        };
    }
}
