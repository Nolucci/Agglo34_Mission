<?php

namespace App\Controller;

use App\Service\MaintenanceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour la page de maintenance
 */
class MaintenanceController extends AbstractController
{
    public function __construct(
        private MaintenanceService $maintenanceService
    ) {
    }

    #[Route('/maintenance', name: 'app_maintenance')]
    public function maintenance(): Response
    {
        // Si l'utilisateur peut accéder pendant la maintenance (admin connecté),
        // le rediriger vers le dashboard
        if ($this->maintenanceService->canAccessDuringMaintenance()) {
            return $this->redirectToRoute('dashboard');
        }

        // Sinon, afficher la page de maintenance
        return $this->render('pages/maintenance.html.twig', [
            'maintenance_message' => $this->maintenanceService->getMaintenanceMessage(),
        ]);
    }
}