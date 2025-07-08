<?php

namespace App\EventListener;

use App\Repository\SettingsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;

/**
 * Listener pour gérer le mode maintenance
 */
class MaintenanceListener
{
    private SettingsRepository $settingsRepository;
    private Security $security;
    private Environment $twig;

    public function __construct(
        SettingsRepository $settingsRepository,
        Security $security,
        Environment $twig
    ) {
        $this->settingsRepository = $settingsRepository;
        $this->security = $security;
        $this->twig = $twig;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Ignorer certaines routes (login, logout, assets, etc.)
        $ignoredRoutes = [
            'app_login',
            'app_logout',
            '_wdt',
            '_profiler',
            '_profiler_search',
            '_profiler_search_bar',
            '_profiler_search_results',
            '_profiler_open_file'
        ];

        if (in_array($route, $ignoredRoutes) ||
            str_starts_with($request->getPathInfo(), '/_') ||
            str_starts_with($request->getPathInfo(), '/css') ||
            str_starts_with($request->getPathInfo(), '/js') ||
            str_starts_with($request->getPathInfo(), '/images') ||
            str_starts_with($request->getPathInfo(), '/login')) {
            return;
        }

        // Vérifier si le mode maintenance est activé
        $settings = $this->settingsRepository->findOneBy([]);

        if ($settings && $settings->isMaintenanceMode()) {
            // Vérifier si l'utilisateur est connecté ET est un administrateur
            if (!$this->security->getUser() || !$this->security->isGranted('ROLE_ADMIN')) {
                // Afficher la page de maintenance
                $maintenanceMessage = $settings->getMaintenanceMessage() ?: 'Application en maintenance. Veuillez réessayer plus tard.';

                $content = $this->twig->render('maintenance.html.twig', [
                    'message' => $maintenanceMessage,
                    'app_name' => $settings->getAppName() ?: 'Agglo34 Mission'
                ]);

                $response = new Response($content, 503);
                $response->headers->set('Retry-After', '3600'); // Réessayer dans 1 heure
                $event->setResponse($response);
            }
        }
    }
}