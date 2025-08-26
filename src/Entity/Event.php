<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTime $startDateTime = null;

    #[ORM\Column]
    private ?float $duration = null;

    #[ORM\Column]
    private ?\DateTime $registrationDeadline = null;

    #[ORM\Column]
    private ?int $maxParticipant = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updatedAt = null;

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

    public function setStartDateTime(\DateTime $startDateTime): static
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

    public function setRegistrationDeadline(\DateTime $registrationDeadline): static
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
}
