<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArchiveController extends AbstractController
{
    #[Route('/archives', name: 'app_archive')]
    public function index(): Response
    {
        // Données archivées fictives
        $archives = [
            [
                'entityType' => 'PhoneLine',
                'entityId' => 123,
                'archivedAt' => new \DateTime('2023-10-26 10:00:00'),
                'data' => ['number' => '0123456789', 'status' => 'inactive'],
            ],
            [
                'entityType' => 'User',
                'entityId' => 456,
                'archivedAt' => new \DateTime('2023-11-15 14:30:00'),
                'data' => ['username' => 'john.doe', 'email' => 'john.doe@example.com'],
            ],
            [
                'entityType' => 'Box',
                'entityId' => 789,
                'archivedAt' => new \DateTime('2023-12-01 09:00:00'),
                'data' => ['serial' => 'BOX-XYZ', 'location' => 'Warehouse A'],
            ],
        ];

        return $this->render('pages/archives.html.twig', [
            'page_title' => 'Archives',
            'archives' => $archives,
        ]);
    }
}