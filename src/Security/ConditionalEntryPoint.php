<?php

namespace App\Security;

use App\Service\SettingsService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Entry point conditionnel qui gère la redirection selon l'état LDAP
 */
class ConditionalEntryPoint implements AuthenticationEntryPointInterface
{
    private SettingsService $settingsService;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        SettingsService $settingsService,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->settingsService = $settingsService;
        $this->urlGenerator = $urlGenerator;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // Si LDAP est désactivé, ne pas rediriger vers login
        if (!$this->isLdapEnabled()) {
            // Retourner une réponse 403 ou rediriger vers une page d'accueil
            return new Response('Accès refusé', 403);
        }

        // Si LDAP est activé, rediriger vers la page de connexion
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