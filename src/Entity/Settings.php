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

    #[ORM\Column(nullable: true)]
    private ?bool $crud_enabled = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $display_mode = 'liste';

    #[ORM\Column(nullable: true)]
    private ?int $items_per_page = 10;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $app_name = 'Agglo34 Mission';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $welcome_message = 'Bienvenue sur l\'application Agglo34 Mission';

    #[ORM\Column(nullable: true)]
    private ?int $alert_threshold = 5;

    #[ORM\Column(nullable: true)]
    private ?bool $feature_enabled = false;

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

    public function isCrudEnabled(): ?bool
    {
        return $this->crud_enabled;
    }

    public function setCrudEnabled(?bool $crud_enabled): static
    {
        $this->crud_enabled = $crud_enabled;

        return $this;
    }

    public function getDisplayMode(): ?string
    {
        return $this->display_mode;
    }

    public function setDisplayMode(?string $display_mode): static
    {
        $this->display_mode = $display_mode;

        return $this;
    }

    public function getItemsPerPage(): ?int
    {
        return $this->items_per_page;
    }

    public function setItemsPerPage(?int $items_per_page): static
    {
        $this->items_per_page = $items_per_page;

        return $this;
    }

    public function getAppName(): ?string
    {
        return $this->app_name;
    }

    public function setAppName(?string $app_name): static
    {
        $this->app_name = $app_name;

        return $this;
    }

    public function getWelcomeMessage(): ?string
    {
        return $this->welcome_message;
    }

    public function setWelcomeMessage(?string $welcome_message): static
    {
        $this->welcome_message = $welcome_message;

        return $this;
    }

    public function getAlertThreshold(): ?int
    {
        return $this->alert_threshold;
    }

    public function setAlertThreshold(?int $alert_threshold): static
    {
        $this->alert_threshold = $alert_threshold;

        return $this;
    }

    public function isFeatureEnabled(): ?bool
    {
        return $this->feature_enabled;
    }

    public function setFeatureEnabled(?bool $feature_enabled): static
    {
        $this->feature_enabled = $feature_enabled;

        return $this;
    }
}
