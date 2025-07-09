<?php

namespace App\Security;

use App\Entity\User;
use App\Service\SettingsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Authenticator qui authentifie automatiquement un utilisateur anonyme quand LDAP est désactivé
 */
class AnonymousAuthenticator extends AbstractAuthenticator
{
    private SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function supports(Request $request): ?bool
    {
        // Ne supporte pas la page de login pour permettre l'authentification explicite
        if ($request->getPathInfo() === '/login') {
            return false;
        }
        // Supporte toutes les autres requêtes quand LDAP est désactivé
        return !$this->isLdapEnabled();
    }

    public function authenticate(Request $request): Passport
    {
        // Créer un passport pour l'utilisateur anonyme
        return new SelfValidatingPassport(
            new UserBadge('anonymous', function () {
                return $this->createAnonymousUser();
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Laisser la requête continuer
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Ne devrait pas arriver
        return null;
    }

    /**
     * Crée un utilisateur anonyme avec tous les droits
     */
    private function createAnonymousUser(): User
    {
        $user = new User();
        $user->setLdapUsername('anonymous');
        $user->setName('Utilisateur Anonyme');
        $user->setEmail('anonymous@local');
        $user->setRoles(['ROLE_ADMIN']); // Accès complet quand LDAP est désactivé
        $user->setPassword('');
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setLastLoginAt(new \DateTimeImmutable());

        return $user;
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