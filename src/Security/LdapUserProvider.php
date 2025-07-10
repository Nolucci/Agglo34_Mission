<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\WhitelistRepository;
use App\Service\SettingsService;
use App\Service\UserPermissionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LdapUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private array $defaultRoles;
    private UserRepository $userRepository;
    private UserPermissionService $permissionService;
    private WhitelistRepository $whitelistRepository;
    private LoggerInterface $logger;
    private SettingsService $settingsService;

    public function __construct(
        UserRepository $userRepository,
        UserPermissionService $permissionService,
        WhitelistRepository $whitelistRepository,
        LoggerInterface $logger,
        SettingsService $settingsService
    ) {
        $this->userRepository = $userRepository;
        $this->permissionService = $permissionService;
        $this->whitelistRepository = $whitelistRepository;
        $this->logger = $logger;
        $this->settingsService = $settingsService;
        $this->defaultRoles = ['ROLE_USER'];
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $this->logger->info(sprintf('loadUserByIdentifier appelé pour l\'identifiant: %s', $identifier));
        try {
            // Recherche de l'utilisateur dans la base de données locale
            $user = $this->userRepository->findOneBy(['ldapUsername' => $identifier]);

            if ($user) {
                $this->logger->info(sprintf('Utilisateur trouvé en base de données locale: %s', $identifier));

            if ($user) {
                // Vérifier si l'utilisateur existant est toujours dans la whitelist
                if (!$this->whitelistRepository->isUserWhitelisted($identifier)) {
                    $this->logger->warning(sprintf('Utilisateur "%s" n\'est plus autorisé (whitelist).', $identifier));
                    throw new UserNotFoundException(sprintf('User "%s" is no longer authorized to access this application.', $identifier));
                }
                $this->logger->info(sprintf('Utilisateur "%s" est dans la whitelist.', $identifier));

                // Vérifier si l'utilisateur est désactivé
                if ($user->isDisabled()) {
                    $this->logger->warning(sprintf('Compte utilisateur "%s" est désactivé.', $identifier));
                    throw new UserNotFoundException(sprintf('User "%s" account is disabled.', $identifier));
                }
                $this->logger->info(sprintf('Compte utilisateur "%s" est actif.', $identifier));
            }
            // Fin du bloc if ($user) de la ligne 64
            } else {
                $this->logger->info(sprintf('Utilisateur "%s" non trouvé en base de données locale, recherche dans LDAP.', $identifier));
                // Si l'utilisateur n'existe pas en local, on le crée à partir des informations LDAP
                $ldapUser = $this->findLdapUser($identifier);

                if (!$ldapUser) {
                    $this->logger->error(sprintf('Utilisateur "%s" non trouvé dans LDAP.', $identifier));
                    throw new UserNotFoundException(sprintf('User "%s" not found in LDAP.', $identifier));
                }
                $this->logger->info(sprintf('Utilisateur "%s" trouvé dans LDAP.', $identifier));

                // Vérifier si l'utilisateur est dans la whitelist
                if (!$this->whitelistRepository->isUserWhitelisted($identifier)) {
                    $this->logger->warning(sprintf('Utilisateur "%s" non autorisé (whitelist).', $identifier));
                    throw new UserNotFoundException(sprintf('User "%s" is not authorized to access this application.', $identifier));
                }
                $this->logger->info(sprintf('Utilisateur "%s" est dans la whitelist.', $identifier));

                $user = new User();
                $user->setLdapUsername($identifier);
                $user->setCreatedAt(new \DateTimeImmutable());

                // Récupération des attributs LDAP
                $user->setName($this->getLdapUserAttribute($ldapUser, 'displayname') ?? $identifier);
                $user->setEmail($this->getLdapUserAttribute($ldapUser, 'mail') ?? $identifier);

                // Assigner les rôles par défaut
                $user->setRoles($this->defaultRoles);

                // Le mot de passe est géré par LDAP, on met une valeur aléatoire
                $user->setPassword(bin2hex(random_bytes(20)));

                // Log des informations récupérées
                $this->logger->info(sprintf('Création utilisateur LDAP: %s', $identifier));
                $this->logger->info(sprintf('Attributs: %s', json_encode([
                    'name' => $user->getName(),
                    'email' => $user->getEmail()
                ])));

                // Persister l'utilisateur
                $this->userRepository->save($user, true);
            }

            // Mettre à jour la dernière connexion
            $user->setLastLoginAt(new \DateTimeImmutable());
            $this->userRepository->save($user, true);

            return $user;
        } catch (UserNotFoundException $e) {
            $this->logger->error(sprintf('Erreur d\'utilisateur non trouvé pour "%s": %s', $identifier, $e->getMessage()));
            throw $e;
        } catch (ConnectionException $e) {
            $this->logger->error(sprintf('Erreur de connexion LDAP pour "%s": %s', $identifier, $e->getMessage()));
            throw new UserNotFoundException(sprintf('User "%s" not found in LDAP: %s', $identifier, $e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->critical(sprintf('Erreur inattendue lors du chargement de l\'utilisateur "%s": %s', $identifier, $e->getMessage()));
            throw $e;
        }
    }

    private function findLdapUser(string $identifier): ?Entry
    {
        // Récupérer les paramètres LDAP depuis la base de données
        $settings = $this->settingsService->getSettings();
        if (!$settings || !$settings->isLdapEnabled()) {
            throw new ConnectionException('LDAP is not enabled');
        }

        // Créer la connexion LDAP avec les paramètres de la base de données
        $ldap = Ldap::create('ext_ldap', [
            'host' => $settings->getLdapHost(),
            'port' => $settings->getLdapPort(),
            'encryption' => $settings->getLdapEncryption(),
            'options' => [
                'protocol_version' => 3,
                'referrals' => false
            ]
        ]);

        $searchDn = $settings->getLdapSearchDn();
        $searchPassword = $settings->getLdapSearchPassword();
        $this->logger->info("Tentative de liaison LDAP avec DN: " . $searchDn);
        $ldap->bind($searchDn, $searchPassword);
        $this->logger->info("Liaison LDAP réussie");

        $uidKey = $settings->getLdapUidKey();
        $baseDn = $settings->getLdapBaseDn();
        $username = $ldap->escape($identifier, '', LdapInterface::ESCAPE_FILTER);
        $query = sprintf('(&(objectClass=user)(objectCategory=person)(%s=%s))', $uidKey, $username);

        $this->logger->info("Recherche LDAP - Base DN: " . $baseDn);
        $this->logger->info("Recherche LDAP - Filtre: " . $query);

        $search = $ldap->query($baseDn, $query);
        $results = $search->execute();

        $this->logger->info(sprintf("Nombre de résultats trouvés: %d", count($results)));

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