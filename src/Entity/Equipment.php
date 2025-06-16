<?php

namespace App\Entity;

use App\Repository\EquipmentRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: EquipmentRepository::class)]
class Equipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Municipality::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Municipality $commune = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etiquetage = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $modele = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeroSerie = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $service = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $utilisateur = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $dateGarantie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $os = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $version = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statut = null; // Actif, Inactif, Panne

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommune(): ?Municipality
    {
        return $this->commune;
    }

    public function setCommune(?Municipality $commune): static
    {
        $this->commune = $commune;

        return $this;
    }

    public function getEtiquetage(): ?string
    {
        return $this->etiquetage;
    }

    public function setEtiquetage(?string $etiquetage): static
    {
        $this->etiquetage = $etiquetage;

        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(?string $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getNumeroSerie(): ?string
    {
        return $this->numeroSerie;
    }

    public function setNumeroSerie(?string $numeroSerie): static
    {
        $this->numeroSerie = $numeroSerie;

        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(?string $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getUtilisateur(): ?string
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?string $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getDateGarantie(): ?DateTimeInterface
    {
        return $this->dateGarantie;
    }

    public function setDateGarantie(?DateTimeInterface $dateGarantie): static
    {
        $this->dateGarantie = $dateGarantie;

        return $this;
    }

    public function getOs(): ?string
    {
        return $this->os;
    }

    public function setOs(?string $os): static
    {
        $this->os = $os;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}