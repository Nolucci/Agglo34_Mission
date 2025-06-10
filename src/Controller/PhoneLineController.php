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
        if (isset($data['directLine'])) {
            $phoneLine->setDirectLine($data['directLine']);
        }
        if (isset($data['shortNumber'])) {
            $phoneLine->setShortNumber($data['shortNumber']);
        }
        if (isset($data['isWorking'])) {
            $phoneLine->setIsWorking($data['isWorking']);
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
        try {
            // Log the start of the update process and the received data
            $this->createLog('Début de la mise à jour de la ligne téléphonique', $id);
            $data = json_decode($request->getContent(), true);
            $this->createLog('Données reçues pour la mise à jour (ID: ' . $id . '): ' . json_encode($data), $id);

            $phoneLine = $this->phoneLineRepository->find($id);
            if (!$phoneLine) {
                $this->createLog('Ligne téléphonique non trouvée pour la mise à jour', $id);
                return $this->json(['error' => 'Ligne téléphonique non trouvée'], Response::HTTP_NOT_FOUND);
            }

            if (!$data) {
                $this->createLog('Données invalides pour la mise à jour', $id);
                return $this->json(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
            }

            // Mettre à jour les champs
            if (array_key_exists('location', $data)) {
                $phoneLine->setLocation($data['location']);
                $this->createLog('Mise à jour de la localisation: ' . $data['location'], $id);
            }
            if (array_key_exists('service', $data)) {
                $phoneLine->setService($data['service']);
                $this->createLog('Mise à jour du service: ' . $data['service'], $id);
            }
            if (array_key_exists('assignedTo', $data)) {
                $phoneLine->setAssignedTo($data['assignedTo']);
                $this->createLog('Mise à jour de l\'attribution: ' . $data['assignedTo'], $id);
            }
            if (array_key_exists('phoneBrand', $data)) {
                $phoneLine->setPhoneBrand($data['phoneBrand']);
                $this->createLog('Mise à jour de la marque du téléphone: ' . $data['phoneBrand'], $id);
            }
            if (array_key_exists('model', $data)) {
                $phoneLine->setModel($data['model']);
                $this->createLog('Mise à jour du modèle: ' . $data['model'], $id);
            }
            if (array_key_exists('operator', $data)) {
                $phoneLine->setOperator($data['operator']);
                $this->createLog('Mise à jour de l\'opérateur: ' . $data['operator'], $id);
            }
            if (array_key_exists('lineType', $data)) {
                $phoneLine->setLineType($data['lineType']);
                $this->createLog('Mise à jour du type de ligne: ' . $data['lineType'], $id);
            }
            if (array_key_exists('directLine', $data)) {
                $phoneLine->setDirectLine($data['directLine']);
                $this->createLog('Mise à jour de la ligne directe: ' . $data['directLine'], $id);
            }
            if (array_key_exists('shortNumber', $data)) {
                $phoneLine->setShortNumber($data['shortNumber']);
                $this->createLog('Mise à jour du numéro court: ' . $data['shortNumber'], $id);
            }
            if (array_key_exists('isWorking', $data)) {
                $isWorking = filter_var($data['isWorking'], FILTER_VALIDATE_BOOLEAN);
                $phoneLine->setIsWorking($isWorking);
                $this->createLog('Mise à jour du statut de fonctionnement: ' . ($isWorking ? 'Fonctionne' : 'Ne fonctionne pas'), $id);
            }

            // Mettre à jour la municipalité si fournie
            if (array_key_exists('municipality', $data) && $data['municipality'] !== null) {
                // Cast municipality ID to integer
                $municipalityId = (int) $data['municipality'];
                $this->createLog('Recherche de la municipalité avec ID: ' . $municipalityId, $id);
                
                $municipality = $this->municipalityRepository->find($municipalityId);
                if (!$municipality) {
                    $this->createLog('Municipalité non trouvée pour la mise à jour (ID: ' . $municipalityId . ')', $id);
                    return $this->json(['error' => 'Municipalité non trouvée'], Response::HTTP_BAD_REQUEST);
                }
                $phoneLine->setMunicipality($municipality);
                $this->createLog('Mise à jour de la municipalité: ' . $municipality->getName(), $id);
            }

            // Log before persisting changes
            $this->createLog('Tentative de persistance des modifications pour la ligne téléphonique', $id);

            // Persister les modifications
            $this->entityManager->flush();

            // Créer un log
            $this->createLog('Modification d\'une ligne téléphonique réussie', $id);

            return $this->json([
                'success' => true,
                'message' => 'Ligne téléphonique mise à jour avec succès'
            ]);
        } catch (\Exception $e) {
            $this->createLog('Erreur lors de la mise à jour: ' . $e->getMessage(), $id);
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue lors de la mise à jour: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
                'directLine' => $phoneLine->getDirectLine(),
                'shortNumber' => $phoneLine->getShortNumber(),
                'isWorking' => $phoneLine->isWorking(),
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
            'directLine' => $phoneLine->getDirectLine(),
            'shortNumber' => $phoneLine->getShortNumber(),
            'isWorking' => $phoneLine->isWorking(),
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
        // Use setUsername instead of setUser
        $log->setUsername($this->getUser() ? $this->getUser()->getUsername() : 'Système');
        $log->setAction($action . ' (ID: ' . $phoneLineId . ')');
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}