<?php

namespace App\Controller;

use App\Entity\Log;
use App\Entity\PhoneLine;
use App\Repository\MunicipalityRepository;
use App\Repository\PhoneLineRepository;
use Doctrine\ORM\EntityManagerInterface;
use \App\Repository\ArchiveRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class PhoneLineController extends AbstractController
{
    private $entityManager;
    private $phoneLineRepository;
    private $municipalityRepository;
    private $archiveRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        PhoneLineRepository $phoneLineRepository,
        MunicipalityRepository $municipalityRepository,
        ArchiveRepository $archiveRepository
    ) {
        $this->entityManager = $entityManager;
        $this->phoneLineRepository = $phoneLineRepository;
        $this->municipalityRepository = $municipalityRepository;
        $this->archiveRepository = $archiveRepository;
    }

    #[Route('/api/phone-line/delete-all', name: 'phone_line_delete_all', methods: ['DELETE'])]
    public function deleteAll(): JsonResponse
    {
        $phoneLines = $this->phoneLineRepository->findAll();
        $count = count($phoneLines);

        foreach ($phoneLines as $phoneLine) {
            $archive = new \App\Entity\Archive();
            $archive->setEntityType('PhoneLine');
            $archive->setEntityId($phoneLine->getId());

            $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $archive->setArchivedAt($now);

            $archiveData = [
                'location' => $this->ensureUtf8($phoneLine->getLocation()),
                'service' => $this->ensureUtf8($phoneLine->getService()),
                'assignedTo' => $this->ensureUtf8($phoneLine->getAssignedTo()),
                'phoneBrand' => $this->ensureUtf8($phoneLine->getPhoneBrand()),
                'model' => $this->ensureUtf8($phoneLine->getModel()),
                'operator' => $this->ensureUtf8($phoneLine->getOperator()),
                'lineType' => $this->ensureUtf8($phoneLine->getLineType()),
                'directLine' => $this->ensureUtf8($phoneLine->getDirectLine()),
                'shortNumber' => $this->ensureUtf8($phoneLine->getShortNumber()),
                'isWorking' => $phoneLine->isWorking(),
                'municipality_id' => $phoneLine->getMunicipality() ? $phoneLine->getMunicipality()->getId() : null,
                'municipality_name' => $phoneLine->getMunicipality() ? $this->ensureUtf8($phoneLine->getMunicipality()->getName()) : null,
            ];

            $archive->setData($archiveData);
            $this->entityManager->persist($archive);

            $this->createLog('Suppression et archivage d\'une ligne téléphonique (suppression en masse)', $phoneLine->getId());
            $this->entityManager->remove($phoneLine);
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => $count . ' lignes téléphoniques supprimées et archivées avec succès'
        ]);
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
            // Log at the very beginning of the method
            $this->createLog('Méthode update appelée pour ID: ' . $id, $id);

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

            // Log the state of the PhoneLine entity before flushing
            $this->createLog('État de l\'entité PhoneLine avant flush (ID: ' . $id . '): ' . json_encode($phoneLine), $id);

            // Vérifier si l'EntityManager est ouvert avant de flusher
            if (!$this->entityManager->isOpen()) {
                $this->createLog('EntityManager est fermé avant flush', $id);
                return $this->json([
                    'success' => false,
                    'error' => 'Une erreur interne a rendu l\'EntityManager inutilisable.'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

             // Persister les modifications
             try {
                 $this->createLog('Tentative de flush des modifications', $id);
                 $this->entityManager->flush();
                 $this->createLog('Flush des modifications réussi', $id);
             } catch (\Exception $e) {
                 error_log('Erreur lors du flush: ' . $e->getMessage());
                 throw $e; // Relancer l'exception pour qu'elle soit traitée par le bloc catch principal
             }

            // Créer un log
            $this->createLog('Modification d\'une ligne téléphonique réussie', $id);

            return $this->json([
                'success' => true,
                'message' => 'Ligne téléphonique mise à jour avec succès'
            ]);
        } catch (\Exception $e) {
            $errorMessage = 'Erreur lors de la mise à jour (ID: ' . $id . '): ' . $e->getMessage();
            try {
                $this->createLog($errorMessage, $id);
                $this->createLog('Stack Trace: ' . $e->getTraceAsString(), $id);
            } catch (\Exception $logException) {
                error_log('Erreur critique lors de la tentative de log de l\'erreur de mise à jour: ' . $logException->getMessage());
                error_log('Erreur originale de mise à jour: ' . $errorMessage);
                error_log('Stack Trace originale: ' . $e->getTraceAsString());
            }
            throw $e;
        }
    }

    #[Route('/api/phone-line/delete/{id}', name: 'phone_line_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $phoneLine = $this->phoneLineRepository->find($id);
        if (!$phoneLine) {
            return $this->json(['error' => 'Ligne téléphonique non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $archive = new \App\Entity\Archive();
        $archive->setEntityType('PhoneLine');
        $archive->setEntityId($phoneLine->getId());

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $archive->setArchivedAt($now);

        $archiveData = [
            'location' => $this->ensureUtf8($phoneLine->getLocation()),
            'service' => $this->ensureUtf8($phoneLine->getService()),
            'assignedTo' => $this->ensureUtf8($phoneLine->getAssignedTo()),
            'phoneBrand' => $this->ensureUtf8($phoneLine->getPhoneBrand()),
            'model' => $this->ensureUtf8($phoneLine->getModel()),
            'operator' => $this->ensureUtf8($phoneLine->getOperator()),
            'lineType' => $this->ensureUtf8($phoneLine->getLineType()),
            'directLine' => $this->ensureUtf8($phoneLine->getDirectLine()),
            'shortNumber' => $this->ensureUtf8($phoneLine->getShortNumber()),
            'isWorking' => $phoneLine->isWorking(),
            'municipality_id' => $phoneLine->getMunicipality() ? $phoneLine->getMunicipality()->getId() : null,
            'municipality_name' => $phoneLine->getMunicipality() ? $this->ensureUtf8($phoneLine->getMunicipality()->getName()) : null,
        ];

        $archive->setData($archiveData);

        $this->entityManager->persist($archive);

        $this->createLog('Suppression et archivage d\'une ligne téléphonique', $id);

        $this->entityManager->remove($phoneLine);

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Ligne téléphonique supprimée et archivée avec succès'
        ]);
    }

    #[Route('/api/phone-line/list', name: 'phone_line_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 50);
        $offset = ($page - 1) * $limit;

        $totalPhoneLines = $this->phoneLineRepository->count([]);
        $phoneLines = $this->phoneLineRepository->findBy([], null, $limit, $offset);

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

        return $this->json([
            'data' => $data,
            'total' => $totalPhoneLines,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($totalPhoneLines / $limit)
        ]);
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
        try {
            // Vérifier si l'EntityManager est ouvert avant de l'utiliser
            if (!$this->entityManager->isOpen()) {
                // Si l'EntityManager est fermé, on ne peut pas créer de log
                error_log('EntityManager fermé - Impossible de créer un log: ' . $action . ' (ID: ' . $phoneLineId . ')');
                return;
            }

            $log = new Log();
            // Définir tous les champs obligatoires
            $log->setEntityType('PhoneLine'); // Type d'entité
            $log->setEntityId($phoneLineId);  // ID de l'entité
            $log->setUsername($this->getUser() ? $this->getUser()->getUsername() : 'Système');
            $log->setAction($action);
            $log->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($log);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // En cas d'erreur lors de la création du log, on l'écrit dans les logs système
            error_log('Erreur lors de la création du log: ' . $e->getMessage());
        }
    }

    /**
     * Assure que les chaînes de caractères sont correctement encodées en UTF-8
     * pour préserver les accents et caractères spéciaux
     */
    private function ensureUtf8(?string $str): ?string
    {
        if ($str === null) {
            return null;
        }

        // Détecter l'encodage actuel
        $encoding = mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

        // Si l'encodage n'est pas UTF-8, convertir la chaîne
        if ($encoding && $encoding !== 'UTF-8') {
            return mb_convert_encoding($str, 'UTF-8', $encoding);
        }

        // Si l'encodage est déjà UTF-8 ou n'a pas pu être détecté, retourner la chaîne telle quelle
        return $str;
    }

    /**
     * Exporte les données des lignes téléphoniques au format CSV
     */
    #[Route('/api/phone-line/export/csv', name: 'phone_line_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        // Récupérer les filtres depuis la requête
        $filters = $this->getFiltersFromRequest($request);

        // Récupérer les lignes téléphoniques en fonction des filtres
        $phoneLines = $this->getFilteredPhoneLines($filters);

        $response = new StreamedResponse(function() use ($phoneLines) {
            $handle = fopen('php://output', 'w+');

            // Écrire l'en-tête UTF-8 BOM pour Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Écrire les en-têtes des colonnes
            fputcsv($handle, [
                'ID',
                'Commune',
                'Localisation',
                'Service',
                'Attribué à',
                'Marque du téléphone',
                'Modèle',
                'Opérateur',
                'Type de ligne',
                'Ligne directe (SDA)',
                'Numéro court',
                'Statut'
            ], ';');

            // Écrire les données
            foreach ($phoneLines as $phoneLine) {
                fputcsv($handle, [
                    $phoneLine->getId(),
                    $phoneLine->getMunicipality() ? $phoneLine->getMunicipality()->getName() : 'Non défini',
                    $phoneLine->getLocation(),
                    $phoneLine->getService(),
                    $phoneLine->getAssignedTo(),
                    $phoneLine->getPhoneBrand() ?: 'Non défini',
                    $phoneLine->getModel() ?: 'Non défini',
                    $phoneLine->getOperator(),
                    $phoneLine->getLineType() ?: 'Non défini',
                    $phoneLine->getDirectLine() ?: 'Non défini',
                    $phoneLine->getShortNumber() ?: 'Non défini',
                    $phoneLine->isWorking() ? 'Fonctionne' : 'Ne fonctionne pas'
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'phone_lines_export_' . date('Y-m-d_His') . '.csv'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * Prépare les données pour l'export PDF (rendu HTML qui sera converti en PDF côté client)
     */
    #[Route('/api/phone-line/export/pdf', name: 'phone_line_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request): Response
    {
        // Récupérer les filtres depuis la requête
        $filters = $this->getFiltersFromRequest($request);

        // Récupérer les lignes téléphoniques en fonction des filtres
        $phoneLines = $this->getFilteredPhoneLines($filters);

        // Rendre la vue HTML qui sera convertie en PDF côté client
        return $this->render('exports/phone_lines_pdf.html.twig', [
            'phoneLines' => $phoneLines,
            'date_export' => new \DateTime(),
            'titre' => 'Export des lignes téléphoniques'
        ]);
    }

    /**
     * Récupère les filtres depuis la requête
     */
    private function getFiltersFromRequest(Request $request): array
    {
        $filters = [];

        // Récupérer les filtres depuis les paramètres de la requête
        if ($request->query->has('municipality')) {
            $filters['municipality'] = $request->query->get('municipality');
        }

        if ($request->query->has('service')) {
            $filters['service'] = $request->query->get('service');
        }

        if ($request->query->has('operator')) {
            $filters['operator'] = $request->query->get('operator');
        }

        if ($request->query->has('lineType')) {
            $filters['lineType'] = $request->query->get('lineType');
        }

        if ($request->query->has('isWorking')) {
            $filters['isWorking'] = $request->query->get('isWorking') === 'true';
        }

        return $filters;
    }

    /**
     * Récupère les lignes téléphoniques filtrées
     */
    private function getFilteredPhoneLines(array $filters): array
    {
        $criteria = [];

        // Construire les critères de recherche en fonction des filtres
        if (isset($filters['municipality']) && !empty($filters['municipality'])) {
            $municipality = $this->municipalityRepository->find($filters['municipality']);
            if ($municipality) {
                $criteria['municipality'] = $municipality;
            }
        }

        if (isset($filters['service']) && !empty($filters['service'])) {
            $criteria['service'] = $filters['service'];
        }

        if (isset($filters['operator']) && !empty($filters['operator'])) {
            $criteria['operator'] = $filters['operator'];
        }

        if (isset($filters['lineType']) && !empty($filters['lineType'])) {
            $criteria['lineType'] = $filters['lineType'];
        }

        if (isset($filters['isWorking'])) {
            $criteria['isWorking'] = $filters['isWorking'];
        }

        // Si aucun filtre n'est appliqué, récupérer toutes les lignes téléphoniques
        if (empty($criteria)) {
            return $this->phoneLineRepository->findAll();
        }

        // Sinon, récupérer les lignes téléphoniques filtrées
        return $this->phoneLineRepository->findBy($criteria);
    }
}