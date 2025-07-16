<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $surname = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $birthday = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    /**
     * @var Collection<int, Parameter>
     */
    #[ORM\ManyToMany(targetEntity: Parameter::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_parameters')]
    private Collection $parameters;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     */
    #[Vich\UploadableField(mapping: 'user_photos', fileNameProperty: 'photo')]
    private ?File $photoFile = null;

    /**
     * @var Collection<int, Car>
     */
    #[ORM\OneToMany(targetEntity: Car::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $cars;

    /**
     * @var Collection<int, Carshare>
     */
    #[ORM\OneToMany(targetEntity: Carshare::class, mappedBy: 'driver', orphanRemoval: true)]
    private Collection $carshares;

    /**
     * @var Collection<int, Credit>
     */
    #[ORM\OneToMany(targetEntity: Credit::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $credits;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'passenger', orphanRemoval: true)]
    private Collection $reservations;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'driver', orphanRemoval: true)]
    private Collection $receivedReviews;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'passenger', orphanRemoval: true)]
    private Collection $givenReviews;

    #[ORM\ManyToOne(targetEntity: Fonction::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Fonction $fonction = null;

    public function __construct()
    {
        $this->cars = new ArrayCollection();
        $this->carshares = new ArrayCollection();
        $this->credits = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->parameters = new ArrayCollection();
        $this->receivedReviews = new ArrayCollection();
        $this->givenReviews = new ArrayCollection();
        
        // Set default photo for new users
        $this->photo = 'default.jpg';
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getBirthday(): ?\DateTimeImmutable
    {
        return $this->birthday;
    }

    public function setBirthday(\DateTimeImmutable $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        // If photo is null or empty, use default photo
        $this->photo = $photo ?: 'default.jpg';

        return $this;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     */
    public function setPhotoFile(?File $photoFile = null): void
    {
        $this->photoFile = $photoFile;

        // Note: VichUploader will handle file changes automatically
    }

    public function getPhotoFile(): ?File
    {
        return $this->photoFile;
    }

    /**
     * @return Collection<int, Car>
     */
    public function getCars(): Collection
    {
        return $this->cars;
    }

    public function addCar(Car $car): static
    {
        if (!$this->cars->contains($car)) {
            $this->cars->add($car);
            $car->setUser($this);
        }

        return $this;
    }

    public function removeCar(Car $car): static
    {
        if ($this->cars->removeElement($car)) {
            // set the owning side to null (unless already changed)
            if ($car->getUser() === $this) {
                $car->setUser(null);
            }
        }

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
            $carshare->setDriver($this);
        }

        return $this;
    }

    public function removeCarshare(Carshare $carshare): static
    {
        if ($this->carshares->removeElement($carshare)) {
            // set the owning side to null (unless already changed)
            if ($carshare->getDriver() === $this) {
                $carshare->setDriver(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Parameter>
     */
    public function getParameters(): Collection
    {
        return $this->parameters;
    }

    public function addParameter(Parameter $parameter): static
    {
        if (!$this->parameters->contains($parameter)) {
            $this->parameters->add($parameter);
        }

        return $this;
    }

    public function removeParameter(Parameter $parameter): static
    {
        $this->parameters->removeElement($parameter);

        return $this;
    }

    /**
     * Ensures that the photoFile property is not serialized (which would cause issues)
     */
    public function __sleep(): array
    {
        // Get all properties except photoFile
        $properties = array_keys(get_object_vars($this));
        
        // Remove photoFile as it cannot be serialized
        return array_filter($properties, function($property) {
            return $property !== 'photoFile';
        });
    }

    public function getFonction(): ?Fonction
    {
        return $this->fonction;
    }

    public function setFonction(?Fonction $fonction): static
    {
        $this->fonction = $fonction;

        return $this;
    }

    /**
     * Check if user can drive (has conductor or conductor/passenger role)
     */
    public function canDrive(): bool
    {
        return $this->fonction && $this->fonction->canDrive();
    }

    /**
     * Check if user can be passenger
     */
    public function canBePassenger(): bool
    {
        return $this->fonction && $this->fonction->canBePassenger();
    }

    /**
     * @return Collection<int, Credit>
     */
    public function getCredits(): Collection
    {
        return $this->credits;
    }

    public function addCredit(Credit $credit): static
    {
        if (!$this->credits->contains($credit)) {
            $this->credits->add($credit);
            $credit->setUser($this);
        }

        return $this;
    }

    public function removeCredit(Credit $credit): static
    {
        if ($this->credits->removeElement($credit)) {
            // set the owning side to null (unless already changed)
            if ($credit->getUser() === $this) {
                $credit->setUser(null);
            }
        }

        return $this;
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
            $reservation->setPassenger($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getPassenger() === $this) {
                $reservation->setPassenger(null);
            }
        }

        return $this;
    }

    /**
     * Calculate total credit balance
     */
    public function getCreditBalance(): float
    {
        $balance = 0.0;
        foreach ($this->credits as $credit) {
            $balance += $credit->getAmount();
        }
        return $balance;
    }

    /**
     * Add credits to user account
     */
    public function addCredits(float $amount, string $type, string $description, ?Carshare $carshare = null): Credit
    {
        $credit = new Credit();
        $credit->setUser($this);
        $credit->setAmount($amount);
        $credit->setType($type);
        $credit->setDescription($description);
        $credit->setCarshare($carshare);
        
        $this->addCredit($credit);
        
        return $credit;
    }

    /**
     * Spend credits (debit)
     */
    public function spendCredits(float $amount, string $description, ?Carshare $carshare = null): Credit
    {
        return $this->addCredits(-$amount, 'SPENT', $description, $carshare);
    }

    /**
     * Check if user can afford a certain amount
     */
    public function canAfford(float $amount): bool
    {
        return $this->getCreditBalance() >= $amount;
    }

    /**
     * String representation of the user for EasyAdmin and other contexts
     */
    public function __toString(): string
    {
        return $this->firstname . ' ' . $this->lastname . ' (' . $this->email . ')';
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReceivedReviews(): Collection
    {
        return $this->receivedReviews;
    }

    public function addReceivedReview(Review $review): static
    {
        if (!$this->receivedReviews->contains($review)) {
            $this->receivedReviews->add($review);
            $review->setDriver($this);
        }

        return $this;
    }

    public function removeReceivedReview(Review $review): static
    {
        if ($this->receivedReviews->removeElement($review)) {
            if ($review->getDriver() === $this) {
                $review->setDriver(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getGivenReviews(): Collection
    {
        return $this->givenReviews;
    }

    public function addGivenReview(Review $review): static
    {
        if (!$this->givenReviews->contains($review)) {
            $this->givenReviews->add($review);
            $review->setPassenger($this);
        }

        return $this;
    }

    public function removeGivenReview(Review $review): static
    {
        if ($this->givenReviews->removeElement($review)) {
            if ($review->getPassenger() === $this) {
                $review->setPassenger(null);
            }
        }

        return $this;
    }

    /**
     * Get average rating as driver (published reviews only)
     */
    public function getAverageRating(): ?float
    {
        $publishedReviews = $this->receivedReviews->filter(fn(Review $review) => $review->isPublished());
        
        if ($publishedReviews->isEmpty()) {
            return null;
        }

        $total = 0;
        foreach ($publishedReviews as $review) {
            $total += $review->getRating();
        }

        return round($total / $publishedReviews->count(), 1);
    }

    /**
     * Count published reviews received as driver
     */
    public function getPublishedReviewsCount(): int
    {
        return $this->receivedReviews->filter(fn(Review $review) => $review->isPublished())->count();
    }
}
