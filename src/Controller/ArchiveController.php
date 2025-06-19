<?php

namespace App\Controller;

use App\Entity\Archive;
use App\Repository\ArchiveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
            'entityTypes' => $entityTypesList,
            'filters' => $filters,
            'user' => $user,
        ]);
    }

    #[Route('/archives/load', name: 'app_archive_load', methods: ['GET'])]
    public function loadArchives(Request $request, ArchiveRepository $archiveRepository): JsonResponse
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 20; // Nombre d'archives par page

        // Récupération des filtres depuis la requête
        $filters = [
            'entityType' => $request->query->get('entityType'),
            'startDate' => $request->query->get('startDate'),
            'endDate' => $request->query->get('endDate'),
        ];

        // Récupération des archives filtrées avec pagination
        $archives = $archiveRepository->findByFiltersWithPagination($filters, $page, $limit);
        $total = $archiveRepository->countByFilters($filters);

        $hasMore = ($page * $limit) < $total;

        $archivesData = [];
        foreach ($archives as $archive) {
            $archivesData[] = [
                'id' => $archive->getId(),
                'entityType' => $archive->getEntityType(),
                'entityId' => $archive->getEntityId(),
                'archivedAt' => $archive->getArchivedAt()->format('c'),
                'deletedBy' => $archive->getDeletedBy(),
                'data' => $archive->getData(),
            ];
        }

        return new JsonResponse([
            'archives' => $archivesData,
            'hasMore' => $hasMore,
            'total' => $total
        ]);
    }

    #[Route('/archives/delete-all', name: 'app_archive_delete_all', methods: ['POST'])]
    public function deleteAll(ArchiveRepository $archiveRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $archiveRepository->deleteAll();
            return new JsonResponse(['success' => true, 'message' => 'Toutes les archives ont été supprimées avec succès.']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Une erreur est survenue lors de la suppression des archives: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/archives/restore/{id}', name: 'app_archive_restore', methods: ['POST'])]
    public function restore(Archive $archive, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $entityType = $archive->getEntityType();
            $data = $archive->getData();

            // Déterminer la classe d'entité complète basée sur le type d'entité
            $entityClass = 'App\\Entity\\' . $entityType;

            if (!class_exists($entityClass)) {
                throw new \Exception("La classe d'entité $entityClass n'existe pas.");
            }

            // Créer une nouvelle instance de l'entité
            $entity = new $entityClass();

            // Traiter d'abord les relations avec d'autres entités
            $relationProperties = [];

            // Identifier les propriétés qui sont des relations
            if ($entityType === 'Box' || $entityType === 'Equipment') {
                $relationProperties['commune'] = 'Municipality';
            } elseif ($entityType === 'PhoneLine') {
                $relationProperties['municipality'] = 'Municipality';
            }

            // Remplir l'entité avec les données archivées
            foreach ($data as $property => $value) {
                // Ignorer l'ID car il sera généré automatiquement
                if ($property === 'id') {
                    continue;
                }

                // Construire le nom de la méthode setter
                $setter = 'set' . ucfirst($property);

                // Vérifier si la propriété est une relation
                if (array_key_exists($property, $relationProperties)) {
                    if ($value !== null) {
                        // Récupérer l'entité liée depuis la base de données
                        $relatedEntityClass = 'App\\Entity\\' . $relationProperties[$property];
                        $relatedEntity = $entityManager->getRepository($relatedEntityClass)->find($value);

                        if ($relatedEntity) {
                            $entity->$setter($relatedEntity);
                        }
                    }
                    continue;
                }

                // Vérifier si la méthode setter existe
                if (method_exists($entity, $setter)) {
                    // Gérer les dates
                    if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                        try {
                            $value = new \DateTime($value);
                        } catch (\Exception $e) {
                            // Si la conversion échoue, utiliser la valeur telle quelle
                        }
                    }

                    // Appeler le setter
                    $entity->$setter($value);
                }
            }

            // Persister la nouvelle entité
            $entityManager->persist($entity);

            // Supprimer l'archive
            $entityManager->remove($archive);

            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => "L'entité $entityType a été restaurée avec succès."
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => "Une erreur est survenue lors de la restauration de l'archive: " . $e->getMessage()
            ], 500);
        }
    }
}