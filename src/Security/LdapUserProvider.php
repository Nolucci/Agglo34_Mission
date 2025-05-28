<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LdapUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private LdapInterface $ldap;
    private string $baseDn;
    private ?string $searchDn;
    private ?string $searchPassword;
    private array $defaultRoles;
    private string $uidKey;
    private UserRepository $userRepository;

    public function __construct(
        LdapInterface $ldap,
        string $baseDn,
        ?string $searchDn,
        ?string $searchPassword,
        array $defaultRoles,
        string $uidKey,
        UserRepository $userRepository
    ) {
        $this->ldap = $ldap;
        $this->baseDn = $baseDn;
        $this->searchDn = $searchDn;
        $this->searchPassword = $searchPassword;
        $this->defaultRoles = $defaultRoles;
        $this->uidKey = $uidKey;
        $this->userRepository = $userRepository;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            // Recherche de l'utilisateur dans la base de données locale
            $user = $this->userRepository->findOneBy(['email' => $identifier]);
            
            if (!$user) {
                // Si l'utilisateur n'existe pas en local, on le crée à partir des informations LDAP
                $ldapUser = $this->findLdapUser($identifier);
                
                if (!$ldapUser) {
                    throw new UserNotFoundException(sprintf('User "%s" not found in LDAP.', $identifier));
                }
                
                $user = new User();
                $user->setEmail($identifier);
                $user->setLdapUsername($identifier);
                $user->setName($this->getLdapUserAttribute($ldapUser, 'displayname') ?? $identifier);
                $user->setRoles($this->defaultRoles);
                
                // Le mot de passe est géré par LDAP, on met une valeur aléatoire
                $user->setPassword(bin2hex(random_bytes(20)));
                
                $this->userRepository->save($user, true);
            }
            
            return $user;
        } catch (ConnectionException $e) {
            throw new UserNotFoundException(sprintf('User "%s" not found in LDAP: %s', $identifier, $e->getMessage()));
        }
    }

    private function findLdapUser(string $identifier): ?Entry
    {
        $this->ldap->bind($this->searchDn, $this->searchPassword);
        
        $username = $this->ldap->escape($identifier, '', LdapInterface::ESCAPE_FILTER);
        $query = sprintf('(&(objectClass=person)(%s=%s))', $this->uidKey, $username);
        
        $search = $this->ldap->query($this->baseDn, $query);
        $results = $search->execute();
        
        return $results[0] ?? null;
    }

    private function getLdapUserAttribute(Entry $entry, string $attribute): ?string
    {
        $attributes = $entry->getAttributes();
        
        if (isset($attributes[$attribute]) && isset($attributes[$attribute][0])) {
            return $attributes[$attribute][0];
        }
        
        return null;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $identifier = $user->getUserIdentifier();
        
        return $this->loadUserByIdentifier($identifier);
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // LDAP gère les mots de passe, donc cette méthode n'est pas utilisée
    }
}