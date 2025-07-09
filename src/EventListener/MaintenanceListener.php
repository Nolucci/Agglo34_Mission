<?php

namespace App\EventListener;

use App\Service\MaintenanceService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Listener pour gérer le mode maintenance
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 1000)]
class MaintenanceListener
{
    public function __construct(
        private MaintenanceService $maintenanceService,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Ignorer les routes de développement et les assets
        if (str_starts_with($route, '_') ||
            str_starts_with($request->getPathInfo(), '/css') ||
            str_starts_with($request->getPathInfo(), '/js') ||
            str_starts_with($request->getPathInfo(), '/images')) {
            return;
        }

        // Ignorer la route de login
        if ($route === 'app_login' || $route === 'app_logout') {
            return;
        }

        // Vérifier si l'application est en mode maintenance
        if ($this->maintenanceService->isMaintenanceMode()) {
            // Vérifier si l'utilisateur peut accéder pendant la maintenance
            if (!$this->maintenanceService->canAccessDuringMaintenance()) {
                // Rediriger vers la page de login si pas connecté en tant qu'admin
                if ($route !== 'app_login') {
                    $loginUrl = $this->urlGenerator->generate('app_login');
                    $response = new Response('', 302, ['Location' => $loginUrl]);
                    $event->setResponse($response);
                    return;
                }
            }
        }
    }
}