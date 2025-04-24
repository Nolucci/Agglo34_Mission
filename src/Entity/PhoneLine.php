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

    // LIEU (e.g. building or office location)
    #[ORM\Column(length: 255)]
    private ?string $location = null;

    // SERVICE (e.g. IT, HR, etc.)
    #[ORM\Column(length: 100)]
    private ?string $service = null;

    // ATTRIBUTION (Full name)
    #[ORM\Column(length: 255)]
    private ?string $assignedTo = null;

    // MARQUE DU TELEPHONE (Phone brand)
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $phoneBrand = null;

    // MODELE
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $model = null;

    // Opérateur
    #[ORM\Column(length: 50)]
    private ?string $operator = null;

    // Type de ligne (e.g. mobile, landline)
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $lineType = null;

    // Municipalité (relation vers entité Municipality)
    #[ORM\ManyToOne(inversedBy: 'phoneLines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Municipality $municipality = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isGlobal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(string $service): static
    {
        $this->service = $service;
        return $this;
    }

    public function getAssignedTo(): ?string
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(string $assignedTo): static
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    public function getPhoneBrand(): ?string
    {
        return $this->phoneBrand;
    }

    public function setPhoneBrand(?string $phoneBrand): static
    {
        $this->phoneBrand = $phoneBrand;
        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): static
    {
        $this->model = $model;
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

    public function getLineType(): ?string
    {
        return $this->lineType;
    }

    public function setLineType(?string $lineType): static
    {
        $this->lineType = $lineType;
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

    public function isGlobal(): ?bool
    {
        return $this->isGlobal;
    }

    public function setIsGlobal(?bool $isGlobal): static
    {
        $this->isGlobal = $isGlobal;

        return $this;
    }
}
