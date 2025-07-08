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

    public function __construct(
        SettingsService $settingsService,
        LdapUserProvider $ldapUserProvider
    ) {
        $this->settingsService = $settingsService;
        $this->ldapUserProvider = $ldapUserProvider;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Si LDAP est activé, on délègue au LdapUserProvider
        if ($this->isLdapEnabled()) {
            return $this->ldapUserProvider->loadUserByIdentifier($identifier);
        }

        // Si LDAP est désactivé, on crée un utilisateur anonyme avec tous les droits
        $user = new User();
        $user->setLdapUsername('anonymous');
        $user->setName('Utilisateur Anonyme');
        $user->setEmail('anonymous@local');
        $user->setRoles(['ROLE_ADMIN']); // Accès complet quand LDAP est désactivé
        $user->setPassword(''); // Pas de mot de passe nécessaire
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setLastLoginAt(new \DateTimeImmutable());

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        // Si LDAP est activé, on délègue au LdapUserProvider
        if ($this->isLdapEnabled()) {
            return $this->ldapUserProvider->refreshUser($user);
        }

        // Si LDAP est désactivé, on retourne l'utilisateur anonyme
        return $this->loadUserByIdentifier('anonymous');
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // Si LDAP est activé, on délègue au LdapUserProvider
        if ($this->isLdapEnabled()) {
            $this->ldapUserProvider->upgradePassword($user, $newHashedPassword);
        }
        // Sinon, on ne fait rien car pas de gestion de mot de passe en mode anonyme
    }

    /**
     * Vérifie si LDAP est activé dans les paramètres
     */
    private function isLdapEnabled(): bool
    {
        $settings = $this->settingsService->getSettings();
        return $settings && $settings->isLdapEnabled();
    }
}