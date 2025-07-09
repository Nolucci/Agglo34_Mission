<?php

namespace App\EventListener;

use App\Service\SettingsService;
use App\Service\MaintenanceService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;
use Psr\Log\LoggerInterface;

/**
 * Listener pour gérer le mode maintenance
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: -10)]
class SimpleMaintenanceListener
{
    public function __construct(
        private SettingsService $settingsService,
        private MaintenanceService $maintenanceService,
        private Environment $twig,
        private Security $security,
        private LoggerInterface $logger
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Ignorer les assets et routes système
        if (str_starts_with($path, '/_') ||
            str_starts_with($path, '/css') ||
            str_starts_with($path, '/js') ||
            str_starts_with($path, '/images')) {
            return;
        }

        // Ignorer les routes de login, logout et maintenance
        if ($path === '/login' || $path === '/logout' || $path === '/maintenance') {
            return;
        }

        // Vérifier si l'application est en mode maintenance
        if (!$this->maintenanceService->isMaintenanceMode()) {
            return;
        }

        // Vérifier si l'utilisateur peut accéder pendant la maintenance
        $user = $this->security->getUser();
        $canAccess = $this->maintenanceService->canAccessDuringMaintenance();

        $this->logger->info('SimpleMaintenanceListener: Checking access', [
            'path' => $path,
            'user_exists' => $user !== null,
            'user_class' => $user ? get_class($user) : 'null',
            'user_identifier' => $user ? $user->getUserIdentifier() : 'null',
            'user_roles' => $user && method_exists($user, 'getRoles') ? $user->getRoles() : [],
            'can_access' => $canAccess
        ]);

        if ($canAccess) {
            $this->logger->info('SimpleMaintenanceListener: Admin access granted');
            return; // Admin connecté, laisser passer
        }

        // Afficher directement la page de maintenance
        $maintenanceMessage = $this->maintenanceService->getMaintenanceMessage();

        $content = $this->twig->render('pages/maintenance.html.twig', [
            'maintenance_message' => $maintenanceMessage,
        ]);

        $response = new Response($content, 503); // Service Unavailable
        $event->setResponse($response);
    }
}