<?php

namespace App\Entity;

use App\Repository\CarshareRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarshareRepository::class)]
class Carshare
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $start = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $end = null;

    #[ORM\Column(length: 255)]
    private ?string $start_location = null;

    #[ORM\Column(length: 255)]
    private ?string $end_location = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $start_detail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $end_detail = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $tripStatus = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $arrivedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $place = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\ManyToOne(inversedBy: 'carshares')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $driver = null;

    #[ORM\ManyToOne(inversedBy: 'carshares')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Car $car = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'carshare', orphanRemoval: true)]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStart(): ?\DateTimeImmutable
    {
        return $this->start;
    }

    public function setStart(\DateTimeImmutable $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?\DateTimeImmutable
    {
        return $this->end;
    }

    public function setEnd(\DateTimeImmutable $end): static
    {
        $this->end = $end;

        return $this;
    }

    public function getStartLocation(): ?string
    {
        return $this->start_location;
    }

    public function setStartLocation(string $start_location): static
    {
        $this->start_location = $start_location;

        return $this;
    }

    public function getEndLocation(): ?string
    {
        return $this->end_location;
    }

    public function setEndLocation(string $end_location): static
    {
        $this->end_location = $end_location;

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

    public function getTripStatus(): ?string
    {
        return $this->tripStatus;
    }

    public function setTripStatus(?string $tripStatus): static
    {
        $this->tripStatus = $tripStatus;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getArrivedAt(): ?\DateTimeImmutable
    {
        return $this->arrivedAt;
    }

    public function setArrivedAt(?\DateTimeImmutable $arrivedAt): static
    {
        $this->arrivedAt = $arrivedAt;

        return $this;
    }

    public function getPlace(): ?int
    {
        return $this->place;
    }

    public function setPlace(?int $place): static
    {
        $this->place = $place;

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

    public function getDriver(): ?User
    {
        return $this->driver;
    }

    public function setDriver(?User $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(?Car $car): static
    {
        $this->car = $car;

        return $this;
    }

    public function getStartDetail(): ?string
    {
        return $this->start_detail;
    }

    public function setStartDetail(?string $start_detail): static
    {
        $this->start_detail = $start_detail;

        return $this;
    }

    public function getEndDetail(): ?string
    {
        return $this->end_detail;
    }

    public function setEndDetail(?string $end_detail): static
    {
        $this->end_detail = $end_detail;

        return $this;
    }

    /**
     * Update the old location fields based on new city and detail fields
     */
    public function updateLocationFields(): void
    {
        // Cette méthode n'est plus nécessaire car on utilise start_location directement
        // Mais on la garde pour la compatibilité si besoin
    }

    // Méthodes helper pour l'affichage des localisations
    public function getFormattedStartLocation(): string
    {
        $location = $this->getStartLocation();
        if ($this->getStartDetail()) {
            $location .= ', ' . $this->getStartDetail();
        }
        
        return $location;
    }
    
    public function getFormattedEndLocation(): string
    {
        $location = $this->getEndLocation();
        if ($this->getEndDetail()) {
            $location .= ', ' . $this->getEndDetail();
        }
        
        return $location;
    }
    
    public function getFormattedRoute(): string
    {
        return $this->getFormattedStartLocation() . ' → ' . $this->getFormattedEndLocation();
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setCarshare($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getCarshare() === $this) {
                $reservation->setCarshare(null);
            }
        }

        return $this;
    }

    /**
     * Get available places (total places - reserved places)
     */
    public function getAvailablePlaces(): int
    {
        $reservedPlaces = 0;
        foreach ($this->reservations as $reservation) {
            if ($reservation->getStatus() === 'CONFIRMED') {
                $reservedPlaces += $reservation->getPassengersCount();
            }
        }
        
        return max(0, $this->place - $reservedPlaces);
    }

    /**
     * Check if carshare has available places
     */
    public function hasAvailablePlaces(): bool
    {
        return $this->getAvailablePlaces() > 0;
    }

    /**
     * Check if user can reserve this carshare
     */
    public function canBeReservedBy(User $user): bool
    {
        // Cannot reserve own carshare
        if ($this->driver === $user) {
            return false;
        }
        
        // Must have available places
        if (!$this->hasAvailablePlaces()) {
            return false;
        }
        
        // User must be able to be passenger
        if (!$user->canBePassenger()) {
            return false;
        }
        
        // User must not already have a reservation for this carshare
        foreach ($this->reservations as $reservation) {
            if ($reservation->getPassenger() === $user && $reservation->getStatus() === 'CONFIRMED') {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Start the trip
     */
    public function startTrip(): void
    {
        if ($this->tripStatus !== null) {
            throw new \LogicException('Trip is already started or completed');
        }

        $this->tripStatus = 'STARTED';
        $this->startedAt = new \DateTimeImmutable();
    }

    /**
     * Mark trip as arrived (driver confirms arrival)
     */
    public function arriveTrip(): void
    {
        if ($this->tripStatus !== 'STARTED') {
            throw new \LogicException('Trip must be started before arriving');
        }

        $this->tripStatus = 'ARRIVED';
        $this->arrivedAt = new \DateTimeImmutable();
    }

    /**
     * Check if trip can be started
     * Trip can only be started within 15 minutes of departure time (before or after)
     */
    public function canBeStarted(): bool
    {
        // Basic checks: trip not started yet and has reservations
        if ($this->tripStatus !== null || $this->getReservations()->count() === 0) {
            return false;
        }

        // Time constraint: within 15 minutes of departure time
        if ($this->start === null) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $departureTime = $this->start;
        
        // Calculate the time difference in minutes
        $timeDifference = $now->getTimestamp() - $departureTime->getTimestamp();
        $timeDifferenceMinutes = abs($timeDifference) / 60;
        
        // Allow starting 15 minutes before or after scheduled departure
        return $timeDifferenceMinutes <= 15;
    }

    /**
     * Check if carshare is expired (1 hour after departure time without starting)
     */
    public function isExpired(): bool
    {
        // Only check if trip hasn't started yet
        if ($this->tripStatus !== null || $this->start === null) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $departureTime = $this->start;
        
        // Check if it's been more than 1 hour since departure time
        $timeDifference = $now->getTimestamp() - $departureTime->getTimestamp();
        $hoursSinceDeparture = $timeDifference / 3600;
        
        return $hoursSinceDeparture > 1;
    }

    /**
     * Mark carshare as cancelled due to expiration
     */
    public function markAsExpired(): void
    {
        if ($this->tripStatus !== null) {
            throw new \LogicException('Cannot mark an already started trip as expired');
        }

        $this->tripStatus = 'EXPIRED';
        // Note: You might want to add an expiredAt field to track when this happened
    }

    /**
     * Check if trip is waiting for passenger validation
     */
    public function isWaitingForValidation(): bool
    {
        return $this->tripStatus === 'ARRIVED';
    }

    /**
     * Check if trip is completed (all passengers validated)
     */
    public function isCompleted(): bool
    {
        return $this->tripStatus === 'COMPLETED';
    }

    /**
     * Complete the trip (all passengers have validated)
     * Also deducts the platform cost from the driver
     * @return Credit The platform cost credit deducted from driver
     */
    public function completeTrip(): Credit
    {
        if ($this->tripStatus !== 'ARRIVED') {
            throw new \LogicException('Trip must be in ARRIVED status before completion');
        }

        // Check if all reservations are validated
        foreach ($this->reservations as $reservation) {
            if (!$reservation->isPassengerValidated()) {
                throw new \LogicException('All passengers must validate before trip completion');
            }
        }

        $this->tripStatus = 'COMPLETED';

        // Deduct platform cost from driver (2 credits = 2 euros)
        return $this->driver->spendCredits(
            2.0,
            'Frais de plateforme pour covoiturage terminé',
            $this
        );
    }
}
