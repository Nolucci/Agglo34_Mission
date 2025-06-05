<?php

namespace App\Controller;

use App\Entity\Log;
use App\Entity\PhoneLine;
use App\Repository\MunicipalityRepository;
use App\Repository\PhoneLineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PhoneLineController extends AbstractController
{
    private $entityManager;
    private $phoneLineRepository;
    private $municipalityRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        PhoneLineRepository $phoneLineRepository,
        MunicipalityRepository $municipalityRepository
    ) {
        $this->entityManager = $entityManager;
        $this->phoneLineRepository = $phoneLineRepository;
        $this->municipalityRepository = $municipalityRepository;
    }

    #[Route('/api/phone-line/create', name: 'phone_line_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier les champs requis
        $requiredFields = ['location', 'service', 'assignedTo', 'operator', 'municipality'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '') || $data[$field] === null) {
                return $this->json(['error' => "Le champ $field est requis"], Response::HTTP_BAD_REQUEST);
            }
        }

        // Récupérer la municipalité
        $municipality = $this->municipalityRepository->find($data['municipality']);
        if (!$municipality) {
            return $this->json(['error' => 'Municipalité non trouvée'], Response::HTTP_BAD_REQUEST);
        }

        // Créer une nouvelle ligne téléphonique
        $phoneLine = new PhoneLine();
        $phoneLine->setLocation($data['location']);
        $phoneLine->setService($data['service']);
        $phoneLine->setAssignedTo($data['assignedTo']);
        $phoneLine->setOperator($data['operator']);
        $phoneLine->setMunicipality($municipality);

        // Champs optionnels
        if (isset($data['phoneBrand'])) {
            $phoneLine->setPhoneBrand($data['phoneBrand']);
        }
        if (isset($data['model'])) {
            $phoneLine->setModel($data['model']);
        }
        if (isset($data['lineType'])) {
            $phoneLine->setLineType($data['lineType']);
        }
        if (isset($data['isGlobal'])) {
            $phoneLine->setIsGlobal($data['isGlobal']);
        }

        // Persister la ligne téléphonique
        $this->entityManager->persist($phoneLine);
        $this->entityManager->flush();

        // Créer un log
        $this->createLog('Création d\'une ligne téléphonique', $phoneLine->getId());

        return $this->json([
            'success' => true,
            'message' => 'Ligne téléphonique créée avec succès',
            'id' => $phoneLine->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/phone-line/update/{id}', name: 'phone_line_update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $phoneLine = $this->phoneLineRepository->find($id);
        if (!$phoneLine) {
            return $this->json(['error' => 'Ligne téléphonique non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        // Mettre à jour les champs
        if (isset($data['location'])) {
            $phoneLine->setLocation($data['location']);
        }
        if (isset($data['service'])) {
            $phoneLine->setService($data['service']);
        }
        if (isset($data['assignedTo'])) {
            $phoneLine->setAssignedTo($data['assignedTo']);
        }
        if (isset($data['phoneBrand'])) {
            $phoneLine->setPhoneBrand($data['phoneBrand']);
        }
        if (isset($data['model'])) {
            $phoneLine->setModel($data['model']);
        }
        if (isset($data['operator'])) {
            $phoneLine->setOperator($data['operator']);
        }
        if (isset($data['lineType'])) {
            $phoneLine->setLineType($data['lineType']);
        }
        if (isset($data['isGlobal'])) {
            $phoneLine->setIsGlobal($data['isGlobal']);
        }

        // Mettre à jour la municipalité si fournie
        if (isset($data['municipality'])) {
            $municipality = $this->municipalityRepository->find($data['municipality']);
            if ($municipality) {
                $phoneLine->setMunicipality($municipality);
            }
        }

        // Persister les modifications
        $this->entityManager->flush();

        // Créer un log
        $this->createLog('Modification d\'une ligne téléphonique', $id);

        return $this->json([
            'success' => true,
            'message' => 'Ligne téléphonique mise à jour avec succès'
        ]);
    }

    #[Route('/api/phone-line/delete/{id}', name: 'phone_line_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $phoneLine = $this->phoneLineRepository->find($id);
        if (!$phoneLine) {
            return $this->json(['error' => 'Ligne téléphonique non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Supprimer la ligne téléphonique
        $this->entityManager->remove($phoneLine);
        $this->entityManager->flush();

        // Créer un log
        $this->createLog('Suppression d\'une ligne téléphonique', $id);

        return $this->json([
            'success' => true,
            'message' => 'Ligne téléphonique supprimée avec succès'
        ]);
    }

    #[Route('/api/phone-line/list', name: 'phone_line_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $phoneLines = $this->phoneLineRepository->findAll();
        $data = [];

        foreach ($phoneLines as $phoneLine) {
            $data[] = [
                'id' => $phoneLine->getId(),
                'location' => $phoneLine->getLocation(),
                'service' => $phoneLine->getService(),
                'assignedTo' => $phoneLine->getAssignedTo(),
                'phoneBrand' => $phoneLine->getPhoneBrand(),
                'model' => $phoneLine->getModel(),
                'operator' => $phoneLine->getOperator(),
                'lineType' => $phoneLine->getLineType(),
                'isGlobal' => $phoneLine->isGlobal(),
                'municipality' => [
                    'id' => $phoneLine->getMunicipality()->getId(),
                    'name' => $phoneLine->getMunicipality()->getName()
                ]
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/phone-line/{id}', name: 'phone_line_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $phoneLine = $this->phoneLineRepository->find($id);
        if (!$phoneLine) {
            return $this->json(['error' => 'Ligne téléphonique non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $phoneLine->getId(),
            'location' => $phoneLine->getLocation(),
            'service' => $phoneLine->getService(),
            'assignedTo' => $phoneLine->getAssignedTo(),
            'phoneBrand' => $phoneLine->getPhoneBrand(),
            'model' => $phoneLine->getModel(),
            'operator' => $phoneLine->getOperator(),
            'lineType' => $phoneLine->getLineType(),
            'isGlobal' => $phoneLine->isGlobal(),
            'municipality' => [
                'id' => $phoneLine->getMunicipality()->getId(),
                'name' => $phoneLine->getMunicipality()->getName()
            ]
        ];

        return $this->json($data);
    }

    /**
     * Crée un log pour une action sur une ligne téléphonique
     */
    private function createLog(string $action, int $phoneLineId): void
    {
        $log = new Log();
        $log->setUser($this->getUser() ? $this->getUser()->getUsername() : 'Système');
        $log->setAction($action . ' (ID: ' . $phoneLineId . ')');
        $log->setTimestamp(new \DateTimeImmutable());

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}