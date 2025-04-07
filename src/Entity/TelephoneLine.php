<?php

namespace App\Entity;

use App\Repository\TelephoneLineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TelephoneLineRepository::class)]
class TelephoneLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $numero = null;

    #[ORM\ManyToOne(inversedBy: 'type')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Town $town = null;

    #[ORM\Column(nullable: true)]
    private ?int $speed = null;

    #[ORM\Column(length: 50)]
    private ?string $operator = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $installationDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getTown(): ?Town
    {
        return $this->town;
    }

    public function setTown(?Town $town): static
    {
        $this->town = $town;

        return $this;
    }

    public function getSpeed(): ?int
    {
        return $this->speed;
    }

    public function setSpeed(?int $speed): static
    {
        $this->speed = $speed;

        return $this;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): static
    {
        $this->operator = $operator;

        return $this;
    }

    public function getInstallationDate(): ?\DateTimeInterface
    {
        return $this->installationDate;
    }

    public function setInstallationDate(\DateTimeInterface $installationDate): static
    {
        $this->installationDate = $installationDate;

        return $this;
    }
}
