<?php

namespace App\Entity;

use App\Enum\EnergyType;
use App\Repository\CarRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarRepository::class)]
class Car
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $model = null;

    #[ORM\Column(length: 255)]
    private ?string $matriculation = null;

    #[ORM\Column(enumType: EnergyType::class)]
    private ?EnergyType $energyType = null;

    #[ORM\Column(length: 255)]
    private ?string $color = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date_first_matricule = null;

    #[ORM\ManyToOne(inversedBy: 'cars')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, Carshare>
     */
    #[ORM\OneToMany(targetEntity: Carshare::class, mappedBy: 'car')]
    private Collection $carshares;

    #[ORM\ManyToOne(inversedBy: 'cars')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Brand $brand = null;

    public function __construct()
    {
        $this->carshares = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getMatriculation(): ?string
    {
        return $this->matriculation;
    }

    public function setMatriculation(string $matriculation): static
    {
        $this->matriculation = $matriculation;

        return $this;
    }

    public function getEnergyType(): ?EnergyType
    {
        return $this->energyType;
    }

    public function setEnergyType(?EnergyType $energyType): static
    {
        $this->energyType = $energyType;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getDateFirstMatricule(): ?\DateTimeImmutable
    {
        return $this->date_first_matricule;
    }

    public function setDateFirstMatricule(\DateTimeImmutable $date_first_matricule): static
    {
        $this->date_first_matricule = $date_first_matricule;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Carshare>
     */
    public function getCarshares(): Collection
    {
        return $this->carshares;
    }

    public function addCarshare(Carshare $carshare): static
    {
        if (!$this->carshares->contains($carshare)) {
            $this->carshares->add($carshare);
            $carshare->setCar($this);
        }

        return $this;
    }

    public function removeCarshare(Carshare $carshare): static
    {
        if ($this->carshares->removeElement($carshare)) {
            // set the owning side to null (unless already changed)
            if ($carshare->getCar() === $this) {
                $carshare->setCar(null);
            }
        }

        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;

        return $this;
    }
}
