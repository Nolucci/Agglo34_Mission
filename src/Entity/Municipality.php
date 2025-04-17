<?php

namespace App\Entity;

use App\Repository\MunicipalityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MunicipalityRepository::class)]
class Municipality
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $contactName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $contactPhone = null;

    /**
     * @var Collection<int, PhoneLine>
     */
    #[ORM\OneToMany(targetEntity: PhoneLine::class, mappedBy: 'municipality', orphanRemoval: true)]
    private Collection $phoneLines;

    public function __construct()
    {
        $this->phoneLines = new ArrayCollection();
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): static
    {
        $this->contactName = $contactName;
        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): static
    {
        $this->contactPhone = $contactPhone;
        return $this;
    }

    /**
     * @return Collection<int, PhoneLine>
     */
    public function getPhoneLines(): Collection
    {
        return $this->phoneLines;
    }

    public function addPhoneLine(PhoneLine $line): static
    {
        if (!$this->phoneLines->contains($line)) {
            $this->phoneLines->add($line);
            $line->setMunicipality($this);
        }

        return $this;
    }

    public function removePhoneLine(PhoneLine $line): static
    {
        if ($this->phoneLines->removeElement($line)) {
            if ($line->getMunicipality() === $this) {
                $line->setMunicipality(null);
            }
        }

        return $this;
    }
}
