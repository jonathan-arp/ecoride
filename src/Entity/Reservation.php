<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $passenger = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Carshare $carshare = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column]
    private ?int $passengersCount = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: 'boolean')]
    private bool $passengerValidated = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'CONFIRMED'; // Par défaut confirmé directement
        $this->passengersCount = 1; // Par défaut 1 passager
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getPassengersCount(): ?int
    {
        return $this->passengersCount;
    }

    public function setPassengersCount(int $passengersCount): static
    {
        $this->passengersCount = $passengersCount;

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

    public function isPassengerValidated(): bool
    {
        return $this->passengerValidated;
    }

    public function setPassengerValidated(bool $passengerValidated): static
    {
        $this->passengerValidated = $passengerValidated;

        return $this;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): static
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    /**
     * Validate the trip for this passenger
     */
    public function validateTrip(): void
    {
        $this->passengerValidated = true;
        $this->validatedAt = new \DateTimeImmutable();
    }

    /**
     * Check if this reservation can be validated
     */
    public function canBeValidated(): bool
    {
        return !$this->passengerValidated && 
               $this->status === 'CONFIRMED' && 
               $this->getCarshare()->getTripStatus() === 'ARRIVED';
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
}
