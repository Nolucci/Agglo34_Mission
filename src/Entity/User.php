<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ldapUsername = null;


    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\ManyToOne(inversedBy: 'account')]
    private ?Settings $settings = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getSettings(): ?Settings
    {
        return $this->settings;
    }

    public function setSettings(?Settings $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) ($this->ldapUsername ?? $this->email);
    }

    public function getLdapUsername(): ?string
    {
        return $this->ldapUsername;
    }

    public function setLdapUsername(?string $ldapUsername): static
    {
        $this->ldapUsername = $ldapUsername;

        return $this;
    }


    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    public function isModifieur(): bool
    {
        return $this->hasRole('ROLE_MODIFIEUR') || $this->isAdmin();
    }

    public function canAccessPhoneLines(): bool
    {
        return $this->hasRole('ROLE_VISITEUR_LIGNES') ||
               $this->hasRole('ROLE_VISITEUR_TOUT') ||
               $this->isModifieur();
    }

    public function canAccessEquipment(): bool
    {
        return $this->hasRole('ROLE_VISITEUR_PARC') ||
               $this->hasRole('ROLE_VISITEUR_TOUT') ||
               $this->isModifieur();
    }

    public function canAccessBoxes(): bool
    {
        return $this->hasRole('ROLE_VISITEUR_BOXS') ||
               $this->hasRole('ROLE_VISITEUR_TOUT') ||
               $this->isModifieur();
    }

    public function canModify(): bool
    {
        return $this->isModifieur();
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function isDisabled(): bool
    {
        return $this->hasRole('ROLE_DISABLED');
    }

}
