<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Whitelist;
use App\Repository\UserRepository;
use App\Repository\WhitelistRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserPermissionService
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_MODIFIEUR = 'ROLE_MODIFIEUR';
    public const ROLE_VISITEUR_TOUT = 'ROLE_VISITEUR_TOUT';
    public const ROLE_VISITEUR_LIGNES = 'ROLE_VISITEUR_LIGNES';
    public const ROLE_VISITEUR_PARC = 'ROLE_VISITEUR_PARC';
    public const ROLE_VISITEUR_BOXS = 'ROLE_VISITEUR_BOXS';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private WhitelistRepository $whitelistRepository
    ) {
    }

    public function isFirstUser(): bool
    {
        $userCount = $this->userRepository->count([]);
        return $userCount === 0;
    }

    public function makeFirstUserAdmin(User $user): void
    {
        if ($this->isFirstUser()) {
            $user->setIsFirstUser(true);
            $user->setRoles([self::ROLE_ADMIN]);
        }
    }

    public function addFirstUserToWhitelist(User $user): void
    {
        // Ajouter automatiquement le premier utilisateur à la whitelist après qu'il soit persisté
        $this->addToWhitelist($user->getLdapUsername(), $user->getEmail(), $user->getName(), $user);
    }

    public function isUserWhitelisted(string $ldapUsername): bool
    {
        return $this->whitelistRepository->isUserWhitelisted($ldapUsername);
    }

    public function addToWhitelist(string $ldapUsername, ?string $email = null, ?string $name = null, ?User $createdBy = null): Whitelist
    {
        $whitelist = new Whitelist();
        $whitelist->setLdapUsername($ldapUsername);
        $whitelist->setEmail($email);
        $whitelist->setName($name);
        $whitelist->setCreatedBy($createdBy);

        $this->whitelistRepository->save($whitelist, true);

        return $whitelist;
    }

    public function removeFromWhitelist(string $ldapUsername): bool
    {
        $whitelistEntry = $this->whitelistRepository->findOneBy(['ldapUsername' => $ldapUsername]);

        if ($whitelistEntry) {
            $whitelistEntry->setIsActive(false);
            $this->whitelistRepository->save($whitelistEntry, true);
            return true;
        }

        return false;
    }

    public function assignRole(User $user, string $role): void
    {
        $currentRoles = $user->getRoles();

        // Supprimer ROLE_USER temporairement pour éviter les doublons
        $currentRoles = array_filter($currentRoles, fn($r) => $r !== 'ROLE_USER');

        if (!in_array($role, $currentRoles)) {
            $currentRoles[] = $role;
        }

        // Gérer la hiérarchie des rôles
        $currentRoles = $this->normalizeRoles($currentRoles);

        $user->setRoles($currentRoles);
        $this->userRepository->save($user, true);
    }

    public function removeRole(User $user, string $role): void
    {
        $currentRoles = $user->getRoles();
        $currentRoles = array_filter($currentRoles, fn($r) => $r !== $role && $r !== 'ROLE_USER');

        $user->setRoles($currentRoles);
        $this->userRepository->save($user, true);
    }

    private function normalizeRoles(array $roles): array
    {
        // Si l'utilisateur a ROLE_ADMIN, il n'a besoin d'aucun autre rôle
        if (in_array(self::ROLE_ADMIN, $roles)) {
            return [self::ROLE_ADMIN];
        }

        // Si l'utilisateur a ROLE_MODIFIEUR, ajouter automatiquement tous les rôles visiteur
        if (in_array(self::ROLE_MODIFIEUR, $roles)) {
            return [
                self::ROLE_MODIFIEUR,
                self::ROLE_VISITEUR_TOUT,
                self::ROLE_VISITEUR_LIGNES,
                self::ROLE_VISITEUR_PARC,
                self::ROLE_VISITEUR_BOXS
            ];
        }

        // Si l'utilisateur a ROLE_VISITEUR_TOUT, ajouter automatiquement tous les autres rôles visiteur
        if (in_array(self::ROLE_VISITEUR_TOUT, $roles)) {
            $roles = array_merge($roles, [
                self::ROLE_VISITEUR_LIGNES,
                self::ROLE_VISITEUR_PARC,
                self::ROLE_VISITEUR_BOXS
            ]);
        }

        return array_unique($roles);
    }

    public function getAvailableRoles(): array
    {
        return [
            self::ROLE_ADMIN => 'Administrateur',
            self::ROLE_MODIFIEUR => 'Modifieur',
            self::ROLE_VISITEUR_TOUT => 'Visiteur - Tout',
            self::ROLE_VISITEUR_LIGNES => 'Visiteur - Lignes Téléphoniques',
            self::ROLE_VISITEUR_PARC => 'Visiteur - Parc Informatique',
            self::ROLE_VISITEUR_BOXS => 'Visiteur - Boxs'
        ];
    }

    public function getRoleHierarchy(): array
    {
        return [
            self::ROLE_ADMIN => [
                self::ROLE_MODIFIEUR,
                self::ROLE_VISITEUR_TOUT,
                self::ROLE_VISITEUR_LIGNES,
                self::ROLE_VISITEUR_PARC,
                self::ROLE_VISITEUR_BOXS
            ],
            self::ROLE_MODIFIEUR => [
                self::ROLE_VISITEUR_TOUT,
                self::ROLE_VISITEUR_LIGNES,
                self::ROLE_VISITEUR_PARC,
                self::ROLE_VISITEUR_BOXS
            ],
            self::ROLE_VISITEUR_TOUT => [
                self::ROLE_VISITEUR_LIGNES,
                self::ROLE_VISITEUR_PARC,
                self::ROLE_VISITEUR_BOXS
            ]
        ];
    }
}