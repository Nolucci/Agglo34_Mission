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

    #[ORM\Column(nullable: true)]
    private ?bool $ldap_enabled = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ldap_host = null;

    #[ORM\Column(nullable: true)]
    private ?int $ldap_port = 389;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $ldap_encryption = 'none';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $ldap_base_dn = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $ldap_search_dn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ldap_search_password = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ldap_uid_key = 'nomcompte';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $database_url = null;

    #[ORM\Column(nullable: true)]
    private ?bool $maintenance_mode = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $maintenance_message = 'Application en maintenance. Veuillez réessayer plus tard.';

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

    public function isLdapEnabled(): ?bool
    {
        return $this->ldap_enabled;
    }

    public function setLdapEnabled(?bool $ldap_enabled): static
    {
        $this->ldap_enabled = $ldap_enabled;
        return $this;
    }

    public function getLdapHost(): ?string
    {
        return $this->ldap_host;
    }

    public function setLdapHost(?string $ldap_host): static
    {
        $this->ldap_host = $ldap_host;
        return $this;
    }

    public function getLdapPort(): ?int
    {
        return $this->ldap_port;
    }

    public function setLdapPort(?int $ldap_port): static
    {
        $this->ldap_port = $ldap_port;
        return $this;
    }

    public function getLdapEncryption(): ?string
    {
        return $this->ldap_encryption;
    }

    public function setLdapEncryption(?string $ldap_encryption): static
    {
        $this->ldap_encryption = $ldap_encryption;
        return $this;
    }

    public function getLdapBaseDn(): ?string
    {
        return $this->ldap_base_dn;
    }

    public function setLdapBaseDn(?string $ldap_base_dn): static
    {
        $this->ldap_base_dn = $ldap_base_dn;
        return $this;
    }

    public function getLdapSearchDn(): ?string
    {
        return $this->ldap_search_dn;
    }

    public function setLdapSearchDn(?string $ldap_search_dn): static
    {
        $this->ldap_search_dn = $ldap_search_dn;
        return $this;
    }

    public function getLdapSearchPassword(): ?string
    {
        return $this->ldap_search_password;
    }

    public function setLdapSearchPassword(?string $ldap_search_password): static
    {
        $this->ldap_search_password = $ldap_search_password;
        return $this;
    }

    public function getLdapUidKey(): ?string
    {
        return $this->ldap_uid_key;
    }

    public function setLdapUidKey(?string $ldap_uid_key): static
    {
        $this->ldap_uid_key = $ldap_uid_key;
        return $this;
    }

    public function getDatabaseUrl(): ?string
    {
        return $this->database_url;
    }

    public function setDatabaseUrl(?string $database_url): static
    {
        $this->database_url = $database_url;
        return $this;
    }

    public function isMaintenanceMode(): ?bool
    {
        return $this->maintenance_mode;
    }

    public function setMaintenanceMode(?bool $maintenance_mode): static
    {
        $this->maintenance_mode = $maintenance_mode;
        return $this;
    }

    public function getMaintenanceMessage(): ?string
    {
        return $this->maintenance_message;
    }

    public function setMaintenanceMessage(?string $maintenance_message): static
    {
        $this->maintenance_message = $maintenance_message;
        return $this;
    }
}
