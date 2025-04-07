<?php

namespace App\Entity;

use App\Repository\TownRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TownRepository::class)]
class Town
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    /**
     * @var Collection<int, TelephoneLine>
     */
    #[ORM\OneToMany(targetEntity: TelephoneLine::class, mappedBy: 'town')]
    private Collection $telephoneLines;

    public function __construct()
    {
        $this->telephoneLines = new ArrayCollection();
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

    /**
     * @return Collection<int, TelephoneLine>
     */
    public function getType(): Collection
    {
        return $this->type;
    }

    public function addType(TelephoneLine $type): static
    {
        if (!$this->type->contains($type)) {
            $this->type->add($type);
            $type->setTown($this);
        }

        return $this;
    }

    public function removeType(TelephoneLine $type): static
    {
        if ($this->type->removeElement($type)) {
            // set the owning side to null (unless already changed)
            if ($type->getTown() === $this) {
                $type->setTown(null);
            }
        }

        return $this;
    }
}
