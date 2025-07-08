<?php

namespace App\Security;

use App\Service\SettingsService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authenticator conditionnel qui délègue à LDAP seulement si activé
 */
class ConditionalLdapAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private SettingsService $settingsService;
    private LdapAuthenticator $ldapAuthenticator;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        SettingsService $settingsService,
        LdapAuthenticator $ldapAuthenticator,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->settingsService = $settingsService;
        $this->ldapAuthenticator = $ldapAuthenticator;
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): ?bool
    {
        // Si LDAP n'est pas activé, on ne supporte que les routes de login pour redirection
        if (!$this->isLdapEnabled()) {
            // On supporte seulement si c'est une tentative de login pour rediriger
            return $request->getPathInfo() === '/login' && $request->isMethod('POST');
        }

        // Si LDAP est activé, on délègue au LdapAuthenticator
        return $this->ldapAuthenticator->supports($request);
    }

    public function authenticate(Request $request): Passport
    {
        // Si LDAP est activé, on délègue au LdapAuthenticator
        if ($this->isLdapEnabled()) {
            return $this->ldapAuthenticator->authenticate($request);
        }

        // Si LDAP n'est pas activé, on crée un passport pour l'utilisateur anonyme
        // Ceci ne devrait normalement pas être appelé car l'EventListener gère l'auth automatique
        throw new AuthenticationException('LDAP authentication is disabled. Access should be automatic.');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($this->isLdapEnabled()) {
            return $this->ldapAuthenticator->onAuthenticationSuccess($request, $token, $firewallName);
        }

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($this->isLdapEnabled()) {
            return $this->ldapAuthenticator->onAuthenticationFailure($request, $exception);
        }

        return null;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // Redirige toujours vers la page de connexion, que LDAP soit activé ou non.
        // Si LDAP est désactivé, l'authentificateur ne supportera pas la requête,
        // et le système de sécurité Symfony passera à d'autres authentificateurs
        // ou affichera le formulaire de connexion par défaut.
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
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