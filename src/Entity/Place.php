<?php

namespace App\Entity;

use App\Repository\PlaceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlaceRepository::class)]
class Place
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $street = null;

    #[ORM\Column]
    private ?float $gpsLatitude = null;

    #[ORM\Column]
    private ?float $gpsLongitude = null;

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
}
