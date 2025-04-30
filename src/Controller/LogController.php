<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LogController extends AbstractController
{
    #[Route('/logs', name: 'app_logs')]
    public function index(): Response
    {
        // Generate fake log data for presentation
        $logs = [
            [
                'user' => 'Sophie Leroy',
                'action' => 'Modification de la ligne téléphonique 4 : Désactivation',
                'timestamp' => new \DateTimeImmutable('2025-04-30 10:45:00'),
            ],
            [
                'user' => 'Pierre Dubois',
                'action' => 'Suppression de l\'équipement 13',
                'timestamp' => new \DateTimeImmutable('2025-04-30 10:30:45'),
            ],
            [
                'user' => 'Marie Martin',
                'action' => 'Ajout d\'une nouvelle ligne (4) à la commune : Servian',
                'timestamp' => new \DateTimeImmutable('2025-04-30 10:15:30'),
            ],
            [
                'user' => 'Jean Dupont',
                'action' => 'Importation de données',
                'timestamp' => new \DateTimeImmutable('2025-04-30 10:00:00'),
            ],
        ];

        return $this->render('log/index.html.twig', [
            'page_title' => 'Logs de l\'Application',
            'logs' => $logs,
        ]);
    }
}