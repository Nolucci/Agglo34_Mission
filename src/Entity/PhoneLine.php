<?php

namespace App\Entity;

use App\Repository\PhoneLineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhoneLineRepository::class)]
class PhoneLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $numero = null;

    #[ORM\ManyToOne(inversedBy: 'phoneLines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Municipality $municipality = null;

    #[ORM\Column(nullable: true)]
    private ?int $speed = null;

    #[ORM\Column(length: 50)]
    private ?string $operator = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $installationDate = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $type = null; // enum simulé avec string

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $monthlyFee = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $contractId = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

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

    public function getMunicipality(): ?Municipality
    {
        return $this->municipality;
    }

    public function setMunicipality(?Municipality $municipality): static
    {
        $this->municipality = $municipality;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getMonthlyFee(): ?float
    {
        return $this->monthlyFee;
    }

    public function setMonthlyFee(?float $monthlyFee): static
    {
        $this->monthlyFee = $monthlyFee;
        return $this;
    }

    public function getContractId(): ?string
    {
        return $this->contractId;
    }

    public function setContractId(?string $contractId): static
    {
        $this->contractId = $contractId;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }
}
