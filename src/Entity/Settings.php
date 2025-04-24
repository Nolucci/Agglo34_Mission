<?php

namespace App\Entity;

use App\Repository\SettingsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingsRepository::class)]
class Settings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'settings')]
    private Collection $account;

    #[ORM\Column]
    private ?bool $DarkTheme = null;

    public function __construct()
    {
        $this->account = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAccount(): Collection
    {
        return $this->account;
    }

    public function addAccount(User $account): static
    {
        if (!$this->account->contains($account)) {
            $this->account->add($account);
            $account->setSettings($this);
        }

        return $this;
    }

    public function removeAccount(User $account): static
    {
        if ($this->account->removeElement($account)) {
            // set the owning side to null (unless already changed)
            if ($account->getSettings() === $this) {
                $account->setSettings(null);
            }
        }

        return $this;
    }

    public function isDarkTheme(): ?bool
    {
        return $this->DarkTheme;
    }

    public function setDarkTheme(bool $DarkTheme): static
    {
        $this->DarkTheme = $DarkTheme;

        return $this;
    }
}
