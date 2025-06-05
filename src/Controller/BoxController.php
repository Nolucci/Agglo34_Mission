<?php

namespace App\Controller;

use App\Entity\Box;
use App\Entity\Municipality;
use App\Repository\BoxRepository;
use App\Repository\MunicipalityRepository;
use App\Repository\PhoneLineRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class BoxController extends AbstractController
{
    private $boxRepository;
    private $entityManager;

    public function __construct(BoxRepository $boxRepository, EntityManagerInterface $entityManager)
    {
        $this->boxRepository = $boxRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/box/{id}', name: 'box_index')]
    public function index(int $id, MunicipalityRepository $municipalityRepository, PhoneLineRepository $phoneLineRepository): Response
    {
        $municipality = $municipalityRepository->find($id);

        if (!$municipality) {
            throw new NotFoundHttpException('Commune non trouvée.');
        }

        $phoneLines = $phoneLineRepository->findBy(['municipality' => $municipality]);

        $parkItems = [];

        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('pages/box.html.twig', [
            'page_title' => 'Détails de la boîte',
            'municipality' => $municipality,
            'phoneLines' => $phoneLines,
            'parkItems' => $parkItems,
            'user' => $user,
        ]);
    }

    #[Route('/box', name: 'box_list')]
    public function list(MunicipalityRepository $municipalityRepository): Response
    {
        $boxes = $this->boxRepository->findAll();

        // Calcul des statistiques des box
        $totalBoxes = count($boxes);
        $activeBoxes = count(array_filter($boxes, function(Box $box) {
            return $box->isActive();
        }));

        $uniqueMunicipalities = [];
        $boxTypes = [];
        $boxStatuses = ['Actif' => 0, 'Inactif' => 0];

        foreach ($boxes as $box) {
            if ($box->getMunicipality()) {
                $uniqueMunicipalities[$box->getMunicipality()] = $box->getMunicipality();
            }
            if ($box->getType()) {
                $boxTypes[$box->getType()] = ($boxTypes[$box->getType()] ?? 0) + 1;
            }
            if ($box->isActive()) {
                $boxStatuses['Actif']++;
            } else {
                $boxStatuses['Inactif']++;
            }
        }

        $uniqueMunicipalitiesCount = count($uniqueMunicipalities);
        // Pour les services uniques, on pourrait avoir besoin d'une logique spécifique liée aux lignes téléphoniques si une box peut être associée à plusieurs services via les lignes.
        // Pour l'instant, je vais laisser unique_services à 0 ou le calculer différemment si nécessaire.
        $uniqueServicesCount = 0; // À adapter si nécessaire

        $boxStats = [
            'total_boxes' => $totalBoxes,
            'unique_services' => $uniqueServicesCount, // À adapter
            'unique_municipalities' => $uniqueMunicipalitiesCount,
            'active_boxes' => $activeBoxes,
        ];

        // Données pour le graphique par type de box
        $boxTypeChartData = [
            'labels' => array_keys($boxTypes),
            'data' => array_values($boxTypes),
        ];

        // Données pour le graphique par statut de box
        $boxStatusChartData = [
            'labels' => array_keys($boxStatuses),
            'data' => array_values($boxStatuses),
        ];


        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('pages/box_list.html.twig', [
            'boxes' => $boxes,
            'page_title' => 'Liste des boîtes',
            'user' => $user,
            'boxStats' => $boxStats,
            'boxTypeChartData' => $boxTypeChartData,
            'boxStatusChartData' => $boxStatusChartData,
        ]);
    }

    #[Route('/api/box', name: 'api_box_create', methods: ['POST'])]
    public function createBox(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['commune']) || empty($data['localisation']) || empty($data['adresse'])) {
            return new JsonResponse(['success' => false, 'error' => 'Les champs commune, localisation et adresse sont obligatoires.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $box = new Box();
        $box->setCommune($data['commune']);
        $box->setLocalisation($data['localisation']);
        $box->setAdresse($data['adresse']);
        $box->setLigneSupport($data['ligne_support'] ?? null);
        $box->setType($data['type'] ?? null);

        $this->entityManager->persist($box);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Box créée avec succès.', 'boxId' => $box->getId()], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/box/{id}', name: 'api_box_show', methods: ['GET'])]
    public function showBox(int $id): JsonResponse
    {
        $box = $this->boxRepository->find($id);

        if (!$box) {
            return new JsonResponse(['success' => false, 'error' => 'Box non trouvée.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $box->getId(),
            'commune' => $box->getCommune(),
            'localisation' => $box->getLocalisation(),
            'adresse' => $box->getAdresse(),
            'ligne_support' => $box->getLigneSupport(),
            'type' => $box->getType(),
        ];

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/api/box/update/{id}', name: 'api_box_update', methods: ['PUT'])]
    public function updateBox(int $id, Request $request): JsonResponse
    {
        $box = $this->boxRepository->find($id);

        if (!$box) {
            return new JsonResponse(['success' => false, 'error' => 'Box non trouvée.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

         if (empty($data['commune']) || empty($data['localisation']) || empty($data['adresse'])) {
            return new JsonResponse(['success' => false, 'error' => 'Les champs commune, localisation et adresse sont obligatoires.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $box->setCommune($data['commune']);
        $box->setLocalisation($data['localisation']);
        $box->setAdresse($data['adresse']);
        $box->setLigneSupport($data['ligne_support'] ?? null);
        $box->setType($data['type'] ?? null);

        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Box mise à jour avec succès.'], JsonResponse::HTTP_OK);
    }

    #[Route('/api/box/delete/{id}', name: 'api_box_delete', methods: ['DELETE'])]
    public function deleteBox(int $id): JsonResponse
    {
        $box = $this->boxRepository->find($id);

        if (!$box) {
            return new JsonResponse(['success' => false, 'error' => 'Box non trouvée.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($box);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Box supprimée avec succès.'], JsonResponse::HTTP_OK);
    }
}