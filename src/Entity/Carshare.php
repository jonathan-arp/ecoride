<?php

namespace App\Entity;

use App\Repository\CarshareRepository;
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
}
