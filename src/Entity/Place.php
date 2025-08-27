<?php

namespace App\Entity;

use App\Repository\PlaceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlaceRepository::class)]
#[ORM\Table(name: 'place')]
class Place
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank()]
    #[Assert\Length( max: 150)]
    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[Assert\NotBlank()]
    #[Assert\Length( max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $street = null;

    #[Assert\Range(min: -90, max: 90)]
    #[ORM\Column(nullable: true)]
    private ?float $gpsLatitude = null;

    #[Assert\Range(min: -180, max: 180)]
    #[ORM\Column(nullable: true)]
    private ?float $gpsLongitude = null;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'place')]
    private Collection $events;

    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'places')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?City $city = null;

    public function __toString(): string
    {
        return $this->name ?? 'lieu';
    }


    public function __construct()
    {
        $this->events = new ArrayCollection();
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

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getGpsLatitude(): ?float
    {
        return $this->gpsLatitude;
    }

    public function setGpsLatitude(float $gpsLatitude): static
    {
        $this->gpsLatitude = $gpsLatitude;

        return $this;
    }

    public function getGpsLongitude(): ?float
    {
        return $this->gpsLongitude;
    }

    public function setGpsLongitude(float $gpsLongitude): static
    {
        $this->gpsLongitude = $gpsLongitude;

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setPlace($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getPlace() === $this) {
                $event->setPlace(null);
            }
        }

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): static
    {
        $this->city = $city;

        return $this;
    }
}
