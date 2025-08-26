<?php

namespace App\Entity;

use App\Repository\RegistrationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegistrationRepository::class)]
class Registration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $registrationDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $cancellationDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegistrationDate(): ?\DateTime
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTime $registrationDate): static
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    public function getCancellationDate(): ?\DateTime
    {
        return $this->cancellationDate;
    }

    public function setCancellationDate(?\DateTime $cancellationDate): static
    {
        $this->cancellationDate = $cancellationDate;

        return $this;
    }
}
