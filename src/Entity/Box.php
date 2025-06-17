<?php

namespace App\Entity;

use App\Repository\BoxRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoxRepository::class)]
class Box
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Municipality::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Municipality $commune = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $service = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ligneSupport = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attribueA = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statut = null; // Actif, Inactif

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

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(?string $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getLigneSupport(): ?string
    {
        return $this->ligneSupport;
    }

    public function setLigneSupport(?string $ligneSupport): static
    {
        $this->ligneSupport = $ligneSupport;

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

    public function getAttribueA(): ?string
    {
        return $this->attribueA;
    }

    public function setAttribueA(?string $attribueA): static
    {
        $this->attribueA = $attribueA;

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

    /**
     * Méthode d'adaptation pour la compatibilité avec le DashboardController
     */
    public function getLocation(): ?string
    {
        return $this->service;
    }

    /**
     * Méthode d'adaptation pour la compatibilité avec le DashboardController
     */
    public function getMunicipality(): ?Municipality
    {
        return $this->commune;
    }

    /**
     * Méthode d'adaptation pour la compatibilité avec le DashboardController
     */
    public function isActive(): bool
    {
        return $this->statut === 'Actif';
    }

    /**
     * Méthode pour identifier la classe dans les templates Twig
     */
    public function getClass(): string
    {
        return 'Box';
    }
}