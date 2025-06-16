<?php

namespace App\Controller;

use App\Repository\LogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LogController extends AbstractController
{
    #[Route('/logs', name: 'app_logs')]
    public function index(Request $request, LogRepository $logRepository): Response
    {
        // Récupération des filtres depuis la requête
        $filters = [
            'entityType' => $request->query->get('entityType'),
            'action' => $request->query->get('action'),
            'username' => $request->query->get('username'),
            'startDate' => $request->query->get('startDate'),
            'endDate' => $request->query->get('endDate'),
        ];

        // Récupération des types d'entités distincts pour le filtre
        $entityTypes = $logRepository->createQueryBuilder('l')
            ->select('DISTINCT l.entityType')
            ->getQuery()
            ->getResult();

        // Transformation du résultat en tableau simple
        $entityTypesList = array_map(function($item) {
            return $item['entityType'];
        }, $entityTypes);

        // Récupération des actions distinctes pour le filtre
        $actions = $logRepository->createQueryBuilder('l')
            ->select('DISTINCT l.action')
            ->getQuery()
            ->getResult();

        // Transformation du résultat en tableau simple
        $actionsList = array_map(function($item) {
            return $item['action'];
        }, $actions);

        // Récupération des utilisateurs distincts pour le filtre
        $usernames = $logRepository->createQueryBuilder('l')
            ->select('DISTINCT l.username')
            ->getQuery()
            ->getResult();

        // Transformation du résultat en tableau simple
        $usernamesList = array_map(function($item) {
            return $item['username'];
        }, $usernames);

        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('log/index.html.twig', [
            'page_title' => 'Logs de l\'Application',
            'entityTypes' => $entityTypesList,
            'actions' => $actionsList,
            'usernames' => $usernamesList,
            'filters' => $filters,
            'user' => $user,
        ]);
    }

    #[Route('/logs/load', name: 'app_logs_load', methods: ['GET'])]
    public function loadLogs(Request $request, LogRepository $logRepository): JsonResponse
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 20; // Nombre de logs par page

        // Récupération des filtres depuis la requête
        $filters = [
            'entityType' => $request->query->get('entityType'),
            'action' => $request->query->get('action'),
            'username' => $request->query->get('username'),
            'startDate' => $request->query->get('startDate'),
            'endDate' => $request->query->get('endDate'),
        ];

        // Récupération des logs filtrés avec pagination
        $logs = $logRepository->findByFiltersWithPagination($filters, $page, $limit);
        $total = $logRepository->countByFilters($filters);

        $hasMore = ($page * $limit) < $total;

        $logsData = [];
        foreach ($logs as $log) {
            $logsData[] = [
                'id' => $log->getId(),
                'action' => $log->getAction(),
                'entityType' => $log->getEntityType(),
                'entityId' => $log->getEntityId(),
                'details' => $log->getDetails(),
                'username' => $log->getUsername(),
                'createdAt' => $log->getCreatedAt()->format('c'),
            ];
        }

        return new JsonResponse([
            'logs' => $logsData,
            'hasMore' => $hasMore,
            'total' => $total
        ]);
    }

    #[Route('/logs/delete-all', name: 'app_logs_delete_all', methods: ['POST'])]
    public function deleteAll(LogRepository $logRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $logRepository->deleteAll();
            return new JsonResponse(['success' => true, 'message' => 'Tous les logs ont été supprimés avec succès.']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Une erreur est survenue lors de la suppression des logs: ' . $e->getMessage()], 500);
        }
    }
}