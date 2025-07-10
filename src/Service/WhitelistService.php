<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Whitelist;
use App\Repository\WhitelistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour la gestion de la whitelist des utilisateurs LDAP
 */
class WhitelistService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WhitelistRepository $whitelistRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Ajoute un utilisateur à la whitelist
     */
    public function addUserToWhitelist(string $ldapUsername, ?string $name = null, ?string $email = null, ?User $createdBy = null, ?string $role = null): Whitelist
    {
        // Vérifier si l'utilisateur existe déjà
        $existingEntry = $this->whitelistRepository->findByLdapUsername($ldapUsername);

        if ($existingEntry) {
            // Réactiver l'entrée si elle était désactivée
            if (!$existingEntry->isActive()) {
                $existingEntry->setIsActive(true);
                $this->entityManager->flush();
                $this->logger->info('Utilisateur réactivé dans la whitelist', ['ldap_username' => $ldapUsername]);
            }
            return $existingEntry;
        }

        // Créer une nouvelle entrée
        $whitelistEntry = new Whitelist();
        $whitelistEntry->setLdapUsername($ldapUsername);
        $whitelistEntry->setName($name);
        $whitelistEntry->setEmail($email);
        $whitelistEntry->setCreatedBy($createdBy);
        $whitelistEntry->setIsActive(true);

        $this->entityManager->persist($whitelistEntry);
        $this->entityManager->flush();

        $this->logger->info('Utilisateur ajouté à la whitelist', [
            'ldap_username' => $ldapUsername,
            'name' => $name,
            'email' => $email
        ]);

        return $whitelistEntry;
    }

    /**
     * Supprime un utilisateur de la whitelist (désactivation)
     */
    public function removeUserFromWhitelist(string $ldapUsername): bool
    {
        $whitelistEntry = $this->whitelistRepository->findByLdapUsername($ldapUsername);

        if (!$whitelistEntry) {
            return false;
        }

        $whitelistEntry->setIsActive(false);
        $this->entityManager->flush();

        $this->logger->info('Utilisateur désactivé dans la whitelist', ['ldap_username' => $ldapUsername]);

        return true;
    }

    /**
     * Vérifie si un utilisateur est autorisé (dans la whitelist active)
     */
    public function isUserAuthorized(string $ldapUsername): bool
    {
        return $this->whitelistRepository->isUserWhitelisted($ldapUsername);
    }

    /**
     * Récupère toutes les entrées actives de la whitelist
     */
    public function getActiveUsers(): array
    {
        return $this->whitelistRepository->findActiveEntries();
    }

    /**
     * Récupère toutes les entrées de la whitelist (actives et inactives)
     */
    public function getAllUsers(): array
    {
        $whitelistEntries = $this->whitelistRepository->findAll();
        $users = [];

        foreach ($whitelistEntries as $entry) {
            // Récupérer l'utilisateur correspondant s'il existe
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['ldapUsername' => $entry->getLdapUsername()]);

            $users[] = [
                'ldap_username' => $entry->getLdapUsername(),
                'name' => $entry->getName() ?? ($user ? $user->getName() : null),
                'email' => $entry->getEmail() ?? ($user ? $user->getEmail() : null),
                'is_disabled' => $user ? $user->isDisabled() : false,
                'last_login_at' => $user ? $user->getLastLoginAt() : null,
                'is_active' => $entry->isActive(),
                'createdAt' => $entry->getCreatedAt(),
                'createdBy' => $entry->getCreatedBy()
            ];
        }

        return $users;
    }

    /**
     * Met à jour les informations d'un utilisateur dans la whitelist
     */
    public function updateWhitelistUser(string $ldapUsername, ?string $name = null, ?string $email = null): bool
    {
        $whitelistEntry = $this->whitelistRepository->findByLdapUsername($ldapUsername);

        if (!$whitelistEntry) {
            return false;
        }

        if ($name !== null) {
            $whitelistEntry->setName($name);
        }

        if ($email !== null) {
            $whitelistEntry->setEmail($email);
        }

        $this->entityManager->flush();

        $this->logger->info('Informations utilisateur mises à jour dans la whitelist', [
            'ldap_username' => $ldapUsername,
            'name' => $name,
            'email' => $email
        ]);

        return true;
    }

    /**
     * Synchronise les informations LDAP avec la whitelist
     */
    public function syncWithLdap(string $ldapUsername, array $ldapAttributes): void
    {
        $whitelistEntry = $this->whitelistRepository->findByLdapUsername($ldapUsername);

        if ($whitelistEntry) {
            $name = $ldapAttributes['displayname'] ?? $ldapAttributes['cn'] ?? null;
            $email = $ldapAttributes['mail'] ?? null;

            $updated = false;

            if ($name && $whitelistEntry->getName() !== $name) {
                $whitelistEntry->setName($name);
                $updated = true;
            }

            if ($email && $whitelistEntry->getEmail() !== $email) {
                $whitelistEntry->setEmail($email);
                $updated = true;
            }

            if ($updated) {
                $this->entityManager->flush();
                $this->logger->info('Informations LDAP synchronisées avec la whitelist', [
                    'ldap_username' => $ldapUsername,
                    'name' => $name,
                    'email' => $email
                ]);
            }
        }
    }

    /**
     * Désactive un utilisateur spécifiquement (différent de removeUserFromWhitelist)
     */
    public function disableUser(string $ldapUsername): bool
    {
        $whitelistEntry = $this->whitelistRepository->findByLdapUsername($ldapUsername);

        if (!$whitelistEntry) {
            return false;
        }

        $whitelistEntry->setIsActive(false);
        $this->entityManager->flush();

        $this->logger->info('Utilisateur désactivé dans la whitelist', ['ldap_username' => $ldapUsername]);

        return true;
    }

    /**
     * Réactive un utilisateur spécifiquement
     */
    public function reactivateUser(string $ldapUsername): bool
    {
        $whitelistEntry = $this->whitelistRepository->findByLdapUsername($ldapUsername);

        if (!$whitelistEntry) {
            return false;
        }

        $whitelistEntry->setIsActive(true);
        $this->entityManager->flush();

        $this->logger->info('Utilisateur réactivé dans la whitelist', ['ldap_username' => $ldapUsername]);

        return true;
    }

    /**
     * Supprime définitivement un utilisateur de la whitelist
     */
    public function removeUserPermanently(string $ldapUsername): bool
    {
        $whitelistEntry = $this->whitelistRepository->findByLdapUsername($ldapUsername);

        if (!$whitelistEntry) {
            return false;
        }

        $this->entityManager->remove($whitelistEntry);
        $this->entityManager->flush();

        $this->logger->info('Utilisateur supprimé définitivement de la whitelist', ['ldap_username' => $ldapUsername]);

        return true;
    }
}