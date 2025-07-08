<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\SettingsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Listener qui gère l'authentification automatique quand LDAP est désactivé
 */
class ConditionalAuthenticationListener implements EventSubscriberInterface
{
    private SettingsService $settingsService;
    private TokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;

    public function __construct(
        SettingsService $settingsService,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger
    ) {
        $this->settingsService = $settingsService;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Ne traiter que la requête principale
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Ignorer les routes de profiler et assets
        if (str_starts_with($request->getPathInfo(), '/_')) {
            return;
        }

        // Ignorer les routes de login si LDAP est activé
        if ($this->isLdapEnabled() && str_starts_with($request->getPathInfo(), '/login')) {
            return;
        }

        // Si LDAP est désactivé, authentifier automatiquement un utilisateur anonyme
        if (!$this->isLdapEnabled()) {
            $token = $this->tokenStorage->getToken();
            $user = $token ? $token->getUser() : null;

            $this->logger->debug('ConditionalAuth: LDAP disabled, checking auth status', [
                'has_token' => $token !== null,
                'user_type' => $user ? get_class($user) : 'null',
                'user_ldap_username' => $user instanceof \App\Entity\User ? $user->getLdapUsername() : 'N/A'
            ]);

            // Vérifier si on a déjà un utilisateur anonyme authentifié
            $needsAuth = true;
            if ($user instanceof \App\Entity\User && $user->getLdapUsername() === 'anonymous') {
                $needsAuth = false;
                $this->logger->debug('ConditionalAuth: Anonymous user already authenticated');
            }

            // Si pas de token valide ou pas d'utilisateur anonyme, authentifier
            if ($needsAuth) {
                $this->logger->debug('ConditionalAuth: Authenticating anonymous user');
                $this->authenticateAnonymousUser();
            }
        }
    }

    /**
     * Authentifie automatiquement un utilisateur anonyme
     */
    private function authenticateAnonymousUser(): void
    {
        $user = new User();
        $user->setLdapUsername('anonymous');
        $user->setName('Utilisateur Anonyme');
        $user->setEmail('anonymous@local');
        $user->setRoles(['ROLE_ADMIN']); // Accès complet quand LDAP est désactivé
        $user->setPassword('');
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setLastLoginAt(new \DateTimeImmutable());

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }

    /**
     * Vérifie si LDAP est activé dans les paramètres
     */
    private function isLdapEnabled(): bool
    {
        $settings = $this->settingsService->getSettings();
        return $settings && $settings->isLdapEnabled();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100], // Priorité très élevée
        ];
    }
}