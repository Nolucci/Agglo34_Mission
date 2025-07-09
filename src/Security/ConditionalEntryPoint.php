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
 * Entry point qui redirige toujours vers la page de login
 */
class ConditionalEntryPoint implements AuthenticationEntryPointInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private SettingsService $settingsService;

    public function __construct(UrlGeneratorInterface $urlGenerator, SettingsService $settingsService)
    {
        $this->urlGenerator = $urlGenerator;
        $this->settingsService = $settingsService;
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        // Rediriger vers la page de login
        // Le SimpleMaintenanceListener gère déjà le mode maintenance
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}