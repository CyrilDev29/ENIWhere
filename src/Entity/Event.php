<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Assert\Callback('validateStartDate')]

class Event
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l’événement est obligatoire.')]
    private ?string $name = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Assert\NotNull(message: 'La date de début est obligatoire.')]
    private ?\DateTime $startDateTime = null;


    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: 'La durée est obligatoire.')]
    private ?float $duration = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La date limite d’inscription est obligatoire.')]
    #[Assert\LessThan(propertyPath: 'startDateTime', message: 'La date limite doit être avant la date de début.')]
    private ?\DateTime $registrationDeadline = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: 'Le nombre maximum de participants est obligatoire.')]
    private ?int $maxParticipant = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description de l’événement est obligatoire.')]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'organizedEvents')]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?User $organizer = null;


    //#[ORM\ManyToOne(inversedBy: 'events')]
    //#[ORM\JoinColumn(nullable: true)]
    //private ?State $state = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Place $place = null;

    /**
     * @var Collection<int, Registration>
     */
    #[ORM\OneToMany(targetEntity: Registration::class, mappedBy: 'event', orphanRemoval: true)]
    private Collection $registrations;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: true)]
    #[Assert\NotBlank(message: 'Le site de l’événement est obligatoire.')]
    private ?Site $site = null;

    #[ORM\Column(length: 20)]
    private ?string $state = null;



    public function __construct()
    {
        $this->registrations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStartDateTime(): ?\DateTime
    {
        return $this->startDateTime;
    }

    public function setStartDateTime(?\DateTime $startDateTime): static
    {
        $this->startDateTime = $startDateTime;

        return $this;
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function setDuration(float $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getRegistrationDeadline(): ?\DateTime
    {
        return $this->registrationDeadline;
    }

    public function setRegistrationDeadline(?\DateTime $registrationDeadline): static
    {
        $this->registrationDeadline = $registrationDeadline;

        return $this;
    }

    public function getMaxParticipant(): ?int
    {
        return $this->maxParticipant;
    }

    public function setMaxParticipant(int $maxParticipant): static
    {
        $this->maxParticipant = $maxParticipant;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): static
    {
        $this->cancellationReason = $cancellationReason;

        return $this;
    }

    public function getCreatedDate(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedDate(\DateTime $createdDate): static
    {
        $this->createdAt = $createdDate;

        return $this;
    }

    public function getUpdatedDate(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedDate(?\DateTime $updatedDate): static
    {
        $this->updatedAt = $updatedDate;

        return $this;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        $this->organizer = $organizer;

        return $this;
    }



    public function getPlace(): ?Place
    {
        return $this->place;
    }

    public function setPlace(?Place $place): static
    {
        $this->place = $place;

        return $this;
    }

    /**
     * @return Collection<int, Registration>
     */
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    public function addRegistration(Registration $registration): static
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations->add($registration);
            $registration->setEvent($this);
        }

        return $this;
    }

    public function removeRegistration(Registration $registration): static
    {
        if ($this->registrations->removeElement($registration)) {
            // set the owning side to null (unless already changed)
            if ($registration->getEvent() === $this) {
                $registration->setEvent(null);
            }
        }

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }

    public function getEndDateTime(): ?\DateTime
    {
        if (!$this->startDateTime || $this->duration === null) {
            return null;
        }
        $end = clone $this->startDateTime;

        $end->modify('+' . (int)$this->duration . ' minutes');
        return $end;
    }

    /** Date d’ouverture des inscriptions  */
    public function getRegistrationOpenAt(): ?\DateTime
    {
        // pas de champ dédié -> on considère l'ouverture immédiate
        return $this->createdAt ?: $this->startDateTime;
    }

    /** Date de fermeture des inscriptions  */
    public function getRegistrationCloseAt(): ?\DateTime
    {
        return $this->registrationDeadline;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;
        return $this;
    }

    public function validateStartDate(ExecutionContextInterface $context): void
    {
        $now = new \DateTime();

        if ($this->startDateTime !== null && $this->startDateTime <= $now) {
            $context->buildViolation('La date de début doit être postérieure à aujourd’hui.')
                ->atPath('startDateTime')
                ->addViolation();
        }

        if ($this->registrationDeadline !== null && $this->startDateTime !== null) {
            if ($this->registrationDeadline >= $this->startDateTime) {
                $context->buildViolation('La date limite d’inscription doit être avant la date de début.')
                    ->atPath('registrationDeadline')
                    ->addViolation();
            }
        }
    }

    public function isEditable(): bool
    {
        return !in_array($this->state, ['ONGOING', 'FINISHED', 'CANCELED', 'ARCHIVED'], true);
    }


}
