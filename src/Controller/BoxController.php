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

    private $municipalityRepository;

    public function __construct(BoxRepository $boxRepository, EntityManagerInterface $entityManager, MunicipalityRepository $municipalityRepository)
    {
        $this->boxRepository = $boxRepository;
        $this->entityManager = $entityManager;
        $this->municipalityRepository = $municipalityRepository;
    }

    #[Route('/api/box/delete-all', name: 'api_box_delete_all', methods: ['DELETE'])]
    public function deleteAll(): JsonResponse
    {
        $boxes = $this->boxRepository->findAll();
        $count = count($boxes);

        foreach ($boxes as $box) {
            $archive = new \App\Entity\Archive();
            $archive->setEntityType('Box');
            $archive->setEntityId($box->getId());
            $archive->setArchivedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
            $archive->setData([
                'commune_id' => $box->getCommune() ? $box->getCommune()->getId() : null,
                'commune_name' => $box->getCommune() ? $box->getCommune()->getName() : null,
                'service' => $box->getService(),
                'adresse' => $box->getAdresse(),
                'ligneSupport' => $box->getLigneSupport(),
                'type' => $box->getType(),
                'attribueA' => $box->getAttribueA(),
                'statut' => $box->getStatut(),
            ]);

            $this->entityManager->persist($archive);

            $log = new \App\Entity\Log();
            $log->setAction('DELETE');
            $log->setEntityType('Box');
            $log->setEntityId($box->getId());
            $log->setDetails('Suppression de la box (suppression en masse): ' . $box->getId());
            $log->setUsername($this->getUser() ? $this->getUser()->getUsername() : 'Système');
            $log->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($log);

            $this->entityManager->remove($box);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $count . ' boxs supprimées et archivées avec succès.'
        ], JsonResponse::HTTP_OK);
    }

    #[Route('/api/box/list', name: 'api_box_list', methods: ['GET'])]
    public function apiList(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 50);
        $offset = ($page - 1) * $limit;

        $totalBoxes = $this->boxRepository->count([]);
        $boxes = $this->boxRepository->findBy([], null, $limit, $offset);

        $boxData = [];
        foreach ($boxes as $box) {
            $boxData[] = [
                'id' => $box->getId(),
                'commune' => $box->getCommune() ? $box->getCommune()->getName() : 'Non défini',
                'service' => $box->getService(),
                'adresse' => $box->getAdresse(),
                'ligne_support' => $box->getLigneSupport() ? ($box->getLigneSupport()) : 'Non défini',
                'type' => $box->getType(),
                'attribueA' => $box->getAttribueA(),
                'statut' => $box->getStatut(),
            ];
        }

        return new JsonResponse([
            'boxes' => $boxData,
            'total' => $totalBoxes,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($totalBoxes / $limit)
        ]);
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
            'page_title' => 'Détails de la box',
            'municipality' => $municipality,
            'phoneLines' => $phoneLines,
            'parkItems' => $parkItems,
            'user' => $user,
        ]);
    }

    #[Route('/box', name: 'box_list')]
    public function list(MunicipalityRepository $municipalityRepository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 50);
        $offset = ($page - 1) * $limit;

        $totalBoxes = $this->boxRepository->count([]);
        $boxes = $this->boxRepository->findBy([], null, $limit, $offset);

        // Récupérer toutes les communes
        $allMunicipalities = $municipalityRepository->findAll();

        // Créer un tableau associatif pour éliminer les doublons par nom (insensible à la casse)
        $uniqueMunicipalitiesByName = [];
        foreach ($allMunicipalities as $municipality) {
            $lowerName = strtolower($municipality->getName());
            if (!isset($uniqueMunicipalitiesByName[$lowerName])) {
                $uniqueMunicipalitiesByName[$lowerName] = $municipality;
            }
        }

        // Convertir en tableau simple et trier par nom
        $municipalities = array_values($uniqueMunicipalitiesByName);
        usort($municipalities, function($a, $b) {
            return strcasecmp($a->getName(), $b->getName());
        });

        $totalBoxes = count($boxes);
        $activeBoxes = count(array_filter($boxes, function(Box $box) {
            return $box->getStatut() === 'Actif';
        }));

        $uniqueMunicipalities = [];
        $boxTypes = [];
        $boxStatuses = ['Actif' => 0, 'Inactif' => 0];

        foreach ($boxes as $box) {
            if ($box->getCommune()) {
                $municipality = $box->getCommune();
                $uniqueMunicipalities[$municipality->getId()] = $municipality->getName();
            }
            if ($box->getType()) {
                $boxTypes[$box->getType()] = ($boxTypes[$box->getType()] ?? 0) + 1;
            }
            if ($box->getStatut() === 'Actif') {
                $boxStatuses['Actif']++;
            } else {
                $boxStatuses['Inactif']++;
            }
        }

        $uniqueMunicipalitiesCount = count($uniqueMunicipalities);
        $uniqueServicesCount = 0;

        $boxStats = [
            'total_boxes' => $totalBoxes,
            'unique_services' => $uniqueServicesCount,
            'unique_municipalities' => $uniqueMunicipalitiesCount,
            'active_boxes' => $activeBoxes,
        ];

        $boxTypeChartData = [
            'labels' => array_keys($boxTypes),
            'data' => array_values($boxTypes),
        ];

        $boxStatusChartData = [
            'labels' => array_keys($boxStatuses),
            'data' => array_values($boxStatuses),
        ];

        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        $boxData = [];
        foreach ($boxes as $box) {
            $boxData[] = [
                'id' => $box->getId(),
                'commune' => $box->getCommune() ? $box->getCommune()->getName() : 'Non défini',
                'service' => $box->getService(),
                'adresse' => $box->getAdresse(),
                'ligne_support' => $box->getLigneSupport() ? ($box->getLigneSupport()) : 'Non défini',
                'type' => $box->getType(),
                'attribueA' => $box->getAttribueA(),
                'statut' => $box->getStatut(),
            ];
        }

        return $this->render('pages/box_list.html.twig', [
            'boxes' => $boxData,
            'page_title' => 'Liste des boxs',
            'user' => $user,
            'boxStats' => $boxStats,
            'boxTypeChartData' => $boxTypeChartData,
            'boxStatusChartData' => $boxStatusChartData,
            'municipalities' => $municipalities,
        ]);
    }

    #[Route('/api/box', name: 'api_box_create', methods: ['POST'])]
    public function createBox(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation et préparation de l'ID de la commune
        $communeData = $data['commune'] ?? null;
        $municipalityId = null;

        // Vérifier si les champs obligatoires sont présents
        if (empty($data['service']) || empty($data['adresse']) || empty($communeData)) {
             return new JsonResponse(['success' => false, 'error' => 'Les champs commune, service et adresse sont obligatoires.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($communeData !== null && $communeData !== '') {
            // Tenter de valider et convertir en entier
            $municipalityId = filter_var($communeData, FILTER_VALIDATE_INT);

            // Si la validation échoue ou si l'ID est invalide (par exemple, 0 si 0 n'est pas un ID valide)
            if ($municipalityId === false || $municipalityId <= 0) { // Assumer que les IDs sont positifs
                return new JsonResponse(['success' => false, 'error' => 'Format d\'ID de commune invalide.'], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Rechercher la municipalité par ID
        $municipality = null;
        if ($municipalityId !== null) {
            $municipality = $this->municipalityRepository->find($municipalityId);

            if (!$municipality) {
                return new JsonResponse(['success' => false, 'error' => 'Commune non trouvée.'], JsonResponse::HTTP_BAD_REQUEST);
            }
        } else {
            return new JsonResponse(['success' => false, 'error' => 'La commune est obligatoire.'], JsonResponse::HTTP_BAD_REQUEST);
        }


        $box = new Box();
        $box->setCommune($municipality); // $municipality est maintenant soit une entité Municipality, soit null
        $box->setService($data['service']);
        $box->setAdresse($data['adresse']);
        $box->setLigneSupport($data['ligne_support'] ?? null);
        $box->setType($data['type'] ?? null);
        $box->setAttribueA($data['attribueA'] ?? null);
        $box->setStatut($data['statut'] ?? 'Inactif');


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
            'commune' => $box->getCommune() ? $box->getCommune()->getId() : null,
            'service' => $box->getService(),
            'adresse' => $box->getAdresse(),
            'ligne_support' => $box->getLigneSupport(),
            'type' => $box->getType(),
            'attribueA' => $box->getAttribueA(),
            'statut' => $box->getStatut(),
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

        // Validation et préparation de l'ID de la commune
        $communeData = $data['commune'] ?? null;
        $municipalityId = null;

        // Vérifier si les champs obligatoires sont présents
        if (empty($data['service']) || empty($data['adresse']) || empty($communeData)) {
             return new JsonResponse(['success' => false, 'error' => 'Les champs commune, service et adresse sont obligatoires.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($communeData !== null && $communeData !== '') {
            // Tenter de valider et convertir en entier
            $municipalityId = filter_var($communeData, FILTER_VALIDATE_INT);

            // Si la validation échoue ou si l'ID est invalide (par exemple, 0 si 0 n'est pas un ID valide)
            if ($municipalityId === false || $municipalityId <= 0) { // Assumer que les IDs sont positifs
                return new JsonResponse(['success' => false, 'error' => 'Format d\'ID de commune invalide.'], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Rechercher la municipalité par ID
        $municipality = null;
        if ($municipalityId !== null) {
            $municipality = $this->municipalityRepository->find($municipalityId);

            if (!$municipality) {
                return new JsonResponse(['success' => false, 'error' => 'Commune non trouvée.'], JsonResponse::HTTP_BAD_REQUEST);
            }
        } else {
            return new JsonResponse(['success' => false, 'error' => 'La commune est obligatoire.'], JsonResponse::HTTP_BAD_REQUEST);
        }


        $box->setCommune($municipality); // $municipality est maintenant soit une entité Municipality, soit null
        $box->setService($data['service']);
        $box->setAdresse($data['adresse']);
        $box->setLigneSupport($data['ligne_support'] ?? $box->getLigneSupport());
        $box->setType($data['type'] ?? $box->getType());
        $box->setAttribueA($data['attribueA'] ?? $box->getAttribueA());
        $box->setStatut($data['statut'] ?? $box->getStatut());

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

        $archive = new \App\Entity\Archive();
        $archive->setEntityType('Box');
        $archive->setEntityId($box->getId());
        $archive->setArchivedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
        $archive->setData([
            'commune_id' => $box->getCommune() ? $box->getCommune()->getId() : null,
            'commune_name' => $box->getCommune() ? $box->getCommune()->getName() : null,
            'service' => $box->getService(),
            'adresse' => $box->getAdresse(),
            'ligneSupport' => $box->getLigneSupport(),
            'type' => $box->getType(),
            'attribueA' => $box->getAttribueA(),
            'statut' => $box->getStatut(),
        ]);

        $this->entityManager->persist($archive);

        $log = new \App\Entity\Log();
        $log->setAction('DELETE');
        $log->setEntityType('Box');
        $log->setEntityId($box->getId());
        $log->setDetails('Suppression de la box: ' . $box->getId());
        $log->setUsername($this->getUser() ? $this->getUser()->getUsername() : 'Système');
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($log);

        $this->entityManager->remove($box);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Box supprimée et archivée avec succès.'], JsonResponse::HTTP_OK);
    }
}