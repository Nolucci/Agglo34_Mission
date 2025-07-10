<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Provider d'utilisateur pour l'authentification avec un compte admin par défaut
 */
class AdminUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private const ADMIN_USERNAME = 'admin';
    private const ADMIN_EMAIL = 'admin@beziers-mediterranee.fr';
    private const ADMIN_NAME = 'Administrateur';

    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Rechercher l'utilisateur par email ou par ldapUsername
        $user = $this->userRepository->findOneBy(['email' => $identifier]);

        if (!$user) {
            $user = $this->userRepository->findOneBy(['ldapUsername' => $identifier]);
        }

        if (!$user) {
            throw new UserNotFoundException(sprintf('Utilisateur "%s" non trouvé.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            return;
        }

        $user->setPassword($newHashedPassword);
        $this->userRepository->save($user, true);
    }

    /**
     * Crée l'utilisateur admin par défaut s'il n'existe pas
     */
    public function createDefaultAdminUser(string $password): User
    {
        $existingUser = $this->userRepository->findOneBy(['email' => self::ADMIN_EMAIL]);

        if ($existingUser) {
            return $existingUser;
        }

        $user = new User();
        $user->setEmail(self::ADMIN_EMAIL);
        $user->setName(self::ADMIN_NAME);
        $user->setLdapUsername(self::ADMIN_USERNAME);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setCreatedAt(new \DateTimeImmutable());

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user, true);

        return $user;
    }

    /**
     * Vérifie si l'utilisateur admin par défaut existe
     */
    public function defaultAdminExists(): bool
    {
        return $this->userRepository->findOneBy(['email' => self::ADMIN_EMAIL]) !== null;
    }

    /**
     * Vérifie si l'identifiant correspond à l'utilisateur admin
     */
    public function isAdminUser(string $identifier): bool
    {
        return $identifier === self::ADMIN_EMAIL || $identifier === self::ADMIN_USERNAME;
    }

    /**
     * Récupère les constantes pour l'utilisateur admin
     */
    public static function getAdminCredentials(): array
    {
        return [
            'username' => self::ADMIN_USERNAME,
            'email' => self::ADMIN_EMAIL,
            'name' => self::ADMIN_NAME
        ];
    }
}