<?php

namespace App\Security;

use App\Entity\User;
use App\Service\SettingsService;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * User Provider conditionnel qui gère l'accès anonyme quand LDAP est désactivé
 */
class ConditionalUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private SettingsService $settingsService;
    private LdapUserProvider $ldapUserProvider;
    private AdminUserProvider $adminUserProvider;

    public function __construct(
        SettingsService $settingsService,
        LdapUserProvider $ldapUserProvider,
        AdminUserProvider $adminUserProvider
    ) {
        $this->settingsService = $settingsService;
        $this->ldapUserProvider = $ldapUserProvider;
        $this->adminUserProvider = $adminUserProvider;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // En mode maintenance, seul l'admin peut se connecter
        if ($this->isMaintenanceMode()) {
            return $this->adminUserProvider->loadUserByIdentifier($identifier);
        }

        // Vérifier d'abord si c'est l'utilisateur admin
        if ($this->adminUserProvider->isAdminUser($identifier)) {
            return $this->adminUserProvider->loadUserByIdentifier($identifier);
        }

        // Si LDAP est activé, on délègue au LdapUserProvider pour les autres utilisateurs
        if ($this->isLdapEnabled()) {
            return $this->ldapUserProvider->loadUserByIdentifier($identifier);
        }

        // Si LDAP est désactivé, seul l'admin peut se connecter
        throw new UserNotFoundException(sprintf('LDAP is disabled. Only admin user can access the application.'));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        // En mode maintenance, utiliser l'AdminUserProvider
        if ($this->isMaintenanceMode()) {
            return $this->adminUserProvider->refreshUser($user);
        }

        // Si LDAP est activé, on délègue au LdapUserProvider
        if ($this->isLdapEnabled()) {
            return $this->ldapUserProvider->refreshUser($user);
        }

        // Sinon, utiliser l'AdminUserProvider
        return $this->adminUserProvider->refreshUser($user);
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // En mode maintenance, utiliser l'AdminUserProvider
        if ($this->isMaintenanceMode()) {
            $this->adminUserProvider->upgradePassword($user, $newHashedPassword);
            return;
        }

        // Si LDAP est activé, on délègue au LdapUserProvider
        if ($this->isLdapEnabled()) {
            $this->ldapUserProvider->upgradePassword($user, $newHashedPassword);
            return;
        }

        // Sinon, utiliser l'AdminUserProvider
        $this->adminUserProvider->upgradePassword($user, $newHashedPassword);
    }

    /**
     * Vérifie si LDAP est activé dans les paramètres
     */
    private function isLdapEnabled(): bool
    {
        $settings = $this->settingsService->getSettings();
        return $settings && $settings->isLdapEnabled();
    }

    /**
     * Vérifie si l'application est en mode maintenance
     */
    private function isMaintenanceMode(): bool
    {
        return $this->settingsService->isMaintenanceMode();
    }
}