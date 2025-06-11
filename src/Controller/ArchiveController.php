<?php

namespace App\Controller;

use App\Repository\ArchiveRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArchiveController extends AbstractController
{
    #[Route('/archives', name: 'app_archive')]
    public function index(Request $request, ArchiveRepository $archiveRepository): Response
    {
        // Récupération des filtres depuis la requête
        $filters = [
            'entityType' => $request->query->get('entityType'),
            'startDate' => $request->query->get('startDate'),
            'endDate' => $request->query->get('endDate'),
        ];

        // Récupération des archives filtrées
        $archives = $archiveRepository->findByFilters($filters);

        // Récupération des types d'entités distincts pour le filtre
        $entityTypes = $archiveRepository->createQueryBuilder('a')
            ->select('DISTINCT a.entityType')
            ->getQuery()
            ->getResult();

        // Transformation du résultat en tableau simple
        $entityTypesList = array_map(function($item) {
            return $item['entityType'];
        }, $entityTypes);

        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('pages/archives.html.twig', [
            'page_title' => 'Archives',
            'archives' => $archives,
            'entityTypes' => $entityTypesList,
            'filters' => $filters,
            'user' => $user,
        ]);
    }
}