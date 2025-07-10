<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Entity\Log;
use App\Repository\EquipmentRepository;
use App\Repository\MunicipalityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/equipment')]
class EquipmentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private EquipmentRepository $equipmentRepository;
    private MunicipalityRepository $municipalityRepository;
    private string $uploadsDirectory;

    public function __construct(
        EntityManagerInterface $entityManager,
        EquipmentRepository $equipmentRepository,
        MunicipalityRepository $municipalityRepository,
        string $uploadsDirectory
    ) {
        $this->entityManager = $entityManager;
        $this->equipmentRepository = $equipmentRepository;
        $this->municipalityRepository = $municipalityRepository;
        $this->uploadsDirectory = $uploadsDirectory;
    }

    #[Route('/stats', name: 'equipment_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $equipments = $this->equipmentRepository->findAll();

        // Statistiques par OS
        $statsByOs = [];
        // Statistiques par modèle
        $statsByModel = [];

        foreach ($equipments as $equipment) {
            // Compter par OS
            $os = $equipment->getOs() ?: 'Non défini';
            if (!isset($statsByOs[$os])) {
                $statsByOs[$os] = 0;
            }
            $statsByOs[$os]++;

            // Compter par modèle
            $model = $equipment->getModele() ?: 'Non défini';
            if (!isset($statsByModel[$model])) {
                $statsByModel[$model] = 0;
            }
            $statsByModel[$model]++;
        }

        // Trier les données par ordre décroissant
        arsort($statsByOs);
        arsort($statsByModel);

        // Limiter le nombre de modèles pour l'histogramme (top 10)
        if (count($statsByModel) > 10) {
            $statsByModel = array_slice($statsByModel, 0, 10, true);
        }

        return new JsonResponse([
            'byOs' => $statsByOs,
            'byModel' => $statsByModel
        ]);
    }

    #[Route('/delete-all', name: 'equipment_delete_all', methods: ['DELETE'])]
    public function deleteAll(): JsonResponse
    {
        $equipments = $this->equipmentRepository->findAll();
        $count = count($equipments);

        foreach ($equipments as $equipment) {
            $equipmentInfo = [
                'id' => $equipment->getId(),
                'commune' => $equipment->getCommune() ? $equipment->getCommune()->getId() : null,
                'etiquetage' => $equipment->getEtiquetage(),
                'modele' => $equipment->getModele(),
                'numeroSerie' => $equipment->getNumeroSerie(),
                'service' => $equipment->getService(),
                'utilisateur' => $equipment->getUtilisateur(),
                'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
                'os' => $equipment->getOs(),
                'version' => $equipment->getVersion(),
                'statut' => $equipment->getStatut(),
                'localisation' => $equipment->getLocalisation(),
            ];

            $log = new Log();
            $log->setAction('DELETE');
            $log->setEntityType('Equipment');
            $log->setEntityId($equipment->getId());
            $log->setDetails('Suppression de l\'équipement (suppression en masse): ' . $equipment->getId() .
                            "\nValeurs: " . json_encode($equipmentInfo, JSON_UNESCAPED_UNICODE));
            $log->setUsername($this->getUser() ? $this->getUser()->getUserIdentifier() : 'Système');
            $log->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($log);

            $archive = new \App\Entity\Archive();
            $archive->setEntityType('Equipment');
            $archive->setEntityId($equipment->getId());
            $archive->setArchivedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
            $archive->setDeletedBy($this->getUser() ? $this->getUser()->getUserIdentifier() : 'Système');
            $archive->setData($equipmentInfo);

            $this->entityManager->persist($archive);

            $this->entityManager->remove($equipment);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $count . ' équipements supprimés avec succès'
        ]);
    }

    #[Route('/create', name: 'equipment_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier les champs requis
        $requiredFields = ['commune', 'modele', 'service'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '') || $data[$field] === null) {
                return new JsonResponse(['success' => false, 'message' => "Le champ $field est requis"], Response::HTTP_BAD_REQUEST);
            }
        }

        $equipment = new Equipment();

        $municipality = null;
        if (isset($data['commune']) && $data['commune'] !== null) {
            $municipality = $this->municipalityRepository->find($data['commune']);
            if (!$municipality) {
                return new JsonResponse(['success' => false, 'message' => 'Commune non trouvée'], Response::HTTP_BAD_REQUEST);
            }
        }
        $equipment->setCommune($municipality);

        $equipment->setEtiquetage($data['etiquetage'] ?? null);
        $equipment->setModele($data['modele'] ?? null);
        $equipment->setNumeroSerie($data['numeroSerie'] ?? null);
        $equipment->setService($data['service'] ?? null);
        $equipment->setUtilisateur($data['utilisateur'] ?? null);
        $equipment->setLocalisation($data['localisation'] ?? null);

        if (isset($data['dateGarantie']) && $data['dateGarantie'] !== null) {
            try {
                $equipment->setDateGarantie(new \DateTimeImmutable($data['dateGarantie']));
            } catch (\Exception $e) {
                return new JsonResponse(['success' => false, 'message' => 'Format de date de garantie invalide'], Response::HTTP_BAD_REQUEST);
            }
        }

        $equipment->setOs($data['os'] ?? null);
        $equipment->setVersion($data['version'] ?? null);
        $equipment->setStatut($data['statut'] ?? null);


        $this->entityManager->persist($equipment);

        $log = new Log();
        $log->setAction('CREATE');
        $log->setEntityType('Equipment');
        $log->setEntityId($equipment->getId() ?? 0);
        $log->setDetails('Création d\'un équipement');
        $log->setUsername($data['username'] ?? 'Système');
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        if ($log->getEntityId() === 0) {
            $log->setEntityId($equipment->getId());
            $this->entityManager->flush();
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Équipement créé avec succès',
            'equipment' => [
                'id' => $equipment->getId(),
                'commune' => $equipment->getCommune() ? [
                    'id' => $equipment->getCommune()->getId(),
                    'name' => $equipment->getCommune()->getName()
                ] : null,
                'etiquetage' => $equipment->getEtiquetage(),
                'modele' => $equipment->getModele(),
                'numeroSerie' => $equipment->getNumeroSerie(),
                'service' => $equipment->getService(),
                'utilisateur' => $equipment->getUtilisateur(),
                'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
                'os' => $equipment->getOs(),
                'version' => $equipment->getVersion(),
                'statut' => $equipment->getStatut(),
                'localisation' => $equipment->getLocalisation(),
            ]
        ]);
    }

    #[Route('/update/{id}', name: 'equipment_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $equipment = $this->equipmentRepository->find($id);

        if (!$equipment) {
            return new JsonResponse(['success' => false, 'message' => 'Équipement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier les champs requis
        $requiredFields = ['commune', 'modele', 'service'];
        foreach ($requiredFields as $field) {
            if (isset($data[$field]) && ((is_string($data[$field]) && trim($data[$field]) === '') || $data[$field] === null)) {
                return new JsonResponse(['success' => false, 'message' => "Le champ $field est requis"], Response::HTTP_BAD_REQUEST);
            }
        }

        $oldValues = [
            'commune' => $equipment->getCommune() ? $equipment->getCommune()->getId() : null,
            'etiquetage' => $equipment->getEtiquetage(),
            'modele' => $equipment->getModele(),
            'numeroSerie' => $equipment->getNumeroSerie(),
            'service' => $equipment->getService(),
            'utilisateur' => $equipment->getUtilisateur(),
            'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
            'os' => $equipment->getOs(),
            'version' => $equipment->getVersion(),
            'statut' => $equipment->getStatut(),
            'localisation' => $equipment->getLocalisation(),
        ];

        $municipality = null;
        if (isset($data['commune']) && $data['commune'] !== null) {
            $municipality = $this->municipalityRepository->find($data['commune']);
            if (!$municipality) {
                return new JsonResponse(['success' => false, 'message' => 'Commune non trouvée'], Response::HTTP_BAD_REQUEST);
            }
        }
        $equipment->setCommune($municipality ?? $equipment->getCommune());

        $equipment->setEtiquetage($data['etiquetage'] ?? $equipment->getEtiquetage());
        $equipment->setModele($data['modele'] ?? $equipment->getModele());
        $equipment->setNumeroSerie($data['numeroSerie'] ?? $equipment->getNumeroSerie());
        $equipment->setService($data['service'] ?? $equipment->getService());
        $equipment->setUtilisateur($data['utilisateur'] ?? $equipment->getUtilisateur());
        $equipment->setLocalisation($data['localisation'] ?? $equipment->getLocalisation());

        if (isset($data['dateGarantie'])) {
             if ($data['dateGarantie'] !== null) {
                try {
                    $equipment->setDateGarantie(new \DateTimeImmutable($data['dateGarantie']));
                } catch (\Exception $e) {
                    return new JsonResponse(['success' => false, 'message' => 'Format de date de garantie invalide'], Response::HTTP_BAD_REQUEST);
                }
            } else {
                $equipment->setDateGarantie(null);
            }
        }

        $equipment->setOs($data['os'] ?? $equipment->getOs());
        $equipment->setVersion($data['version'] ?? $equipment->getVersion());
        $equipment->setStatut($data['statut'] ?? $equipment->getStatut());

        $this->entityManager->flush();

        $log = new Log();
        $log->setAction('UPDATE');
        $log->setEntityType('Equipment');
        $log->setEntityId($equipment->getId());
        $log->setDetails('Mise à jour de l\'équipement: ' . $equipment->getId() .
                        "\nAncienne valeur: " . json_encode($oldValues, JSON_UNESCAPED_UNICODE) .
                        "\nNouvelle valeur: " . json_encode([
                            'commune' => $equipment->getCommune() ? $equipment->getCommune()->getId() : null,
                            'etiquetage' => $equipment->getEtiquetage(),
                            'modele' => $equipment->getModele(),
                            'numeroSerie' => $equipment->getNumeroSerie(),
                            'service' => $equipment->getService(),
                            'utilisateur' => $equipment->getUtilisateur(),
                            'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
                            'os' => $equipment->getOs(),
                            'version' => $equipment->getVersion(),
                            'statut' => $equipment->getStatut(),
                            'localisation' => $equipment->getLocalisation(),
                        ], JSON_UNESCAPED_UNICODE));
        $log->setUsername($data['username'] ?? 'Système');
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Équipement mis à jour avec succès',
            'equipment' => [
                'id' => $equipment->getId(),
                'commune' => $equipment->getCommune() ? [
                    'id' => $equipment->getCommune()->getId(),
                    'name' => $equipment->getCommune()->getName()
                ] : null,
                'etiquetage' => $equipment->getEtiquetage(),
                'modele' => $equipment->getModele(),
                'numeroSerie' => $equipment->getNumeroSerie(),
                'service' => $equipment->getService(),
                'utilisateur' => $equipment->getUtilisateur(),
                'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
                'os' => $equipment->getOs(),
                'version' => $equipment->getVersion(),
                'statut' => $equipment->getStatut(),
                'localisation' => $equipment->getLocalisation(),
            ]
        ]);
    }

    #[Route('/delete/{id}', name: 'equipment_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $equipment = $this->equipmentRepository->find($id);

        if (!$equipment) {
            return new JsonResponse(['success' => false, 'message' => 'Équipement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $equipmentInfo = [
            'id' => $equipment->getId(),
            'commune' => $equipment->getCommune() ? $equipment->getCommune()->getId() : null,
            'etiquetage' => $equipment->getEtiquetage(),
            'modele' => $equipment->getModele(),
            'numeroSerie' => $equipment->getNumeroSerie(),
            'service' => $equipment->getService(),
            'utilisateur' => $equipment->getUtilisateur(),
            'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
            'os' => $equipment->getOs(),
            'version' => $equipment->getVersion(),
            'statut' => $equipment->getStatut(),
            'localisation' => $equipment->getLocalisation(),
        ];

        $log = new Log();
        $log->setAction('DELETE');
        $log->setEntityType('Equipment');
        $log->setEntityId($equipment->getId());
        $log->setDetails('Suppression de l\'équipement: ' . $equipment->getId() .
                        "\nValeurs: " . json_encode($equipmentInfo, JSON_UNESCAPED_UNICODE));
        $log->setUsername($this->getUser() ? $this->getUser()->getUserIdentifier() : 'Système');
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($log);

        $archive = new \App\Entity\Archive();
        $archive->setEntityType('Equipment');
        $archive->setEntityId($equipment->getId());
        $archive->setArchivedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
        $archive->setDeletedBy($this->getUser() ? $this->getUser()->getUserIdentifier() : 'Système');
        $archive->setData($equipmentInfo);

        $this->entityManager->persist($archive);

        $this->entityManager->remove($equipment);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Équipement supprimé avec succès'
        ]);
    }

    #[Route('/list', name: 'equipment_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 50);
        $search = $request->query->get('search', '');
        $offset = ($page - 1) * $limit;

        $filters = $this->getFiltersFromRequest($request);

        // Si un filtre de municipalité est présent, récupérer le nom de la municipalité
        if (isset($filters['commune']) && !empty($filters['commune'])) {
            $municipalityId = $filters['commune'];
            $municipality = $this->municipalityRepository->find($municipalityId);
            if ($municipality) {
                $filters['communeName'] = $municipality->getName();
            } else {
                // Si la municipalité n'est pas trouvée, invalider le filtre pour éviter des résultats inattendus
                unset($filters['commune']);
            }
        }

        // Si une recherche est effectuée, utiliser la nouvelle méthode de recherche
        if (!empty($search)) {
            $result = $this->equipmentRepository->searchWithPagination($search, $page, $limit, $filters);
            $equipments = $result['data'];
            $totalEquipments = $result['total'];
        } else {
            // Utiliser la méthode de filtrage existante
            $result = $this->equipmentRepository->findFilteredEquipments($filters, $page, $limit);
            $equipments = $result['data'];
            $totalEquipments = $result['total'];
        }

        $result = [];
        foreach ($equipments as $equipment) {
            $result[] = [
                'id' => $equipment->getId(),
                'commune' => $equipment->getCommune() ? [
                    'id' => $equipment->getCommune()->getId(),
                    'name' => $equipment->getCommune()->getName()
                ] : null,
                'etiquetage' => $equipment->getEtiquetage(),
                'modele' => $equipment->getModele(),
                'numeroSerie' => $equipment->getNumeroSerie(),
                'service' => $equipment->getService(),
                'utilisateur' => $equipment->getUtilisateur(),
                'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
                'os' => $equipment->getOs(),
                'version' => $equipment->getVersion(),
                'statut' => $equipment->getStatut(),
                'localisation' => $equipment->getLocalisation(),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'equipments' => $result,
            'total' => $totalEquipments,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($totalEquipments / $limit),
            'search' => $search
        ]);
    }

    #[Route('/import-progress', name: 'equipment_import_progress', methods: ['GET'])]
    public function getImportProgress(Request $request): JsonResponse
    {
        $sessionId = $request->query->get('sessionId');
        if (!$sessionId) {
            return new JsonResponse(['error' => 'Session ID requis'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer les données de progression depuis la session ou un cache
        $session = $request->getSession();
        $progressData = $session->get("import_progress_{$sessionId}", [
            'current' => 0,
            'total' => 0,
            'status' => 'idle',
            'message' => ''
        ]);

        return new JsonResponse($progressData);
    }

    #[Route('/import-csv', name: 'equipment_import_csv', methods: ['POST'])]
    public function importCsv(Request $request): JsonResponse
    {
        // Log pour déboguer
        error_log('Début de l\'importation CSV');

        // Générer un ID de session unique pour le suivi de progression
        $sessionId = uniqid('import_', true);
        $session = $request->getSession();

        // Initialiser les données de progression
        $session->set("import_progress_{$sessionId}", [
            'current' => 0,
            'total' => 0,
            'status' => 'starting',
            'message' => 'Initialisation de l\'import...'
        ]);

        // Récupérer le fichier ou les fichiers
        $files = $request->files->get('file');

        // Si aucun fichier n'est trouvé avec la clé 'file', essayer avec 'file[]'
        if (!$files) {
            $files = $request->files->get('file[]');
            error_log('Tentative de récupération avec file[]: ' . ($files ? (is_array($files) ? count($files) : '1') : '0'));
        }

        // Log pour vérifier si des fichiers ont été reçus
        error_log('Fichiers reçus: ' . ($files ? (is_array($files) ? count($files) : '1') : '0'));

        // Vérifier toutes les clés disponibles dans la requête
        $allKeys = [];
        foreach ($request->files as $key => $value) {
            $allKeys[] = $key;
        }
        error_log('Toutes les clés disponibles: ' . implode(', ', $allKeys));

        // Si un seul fichier est envoyé, le mettre dans un tableau pour traitement uniforme
        if ($files && !is_array($files)) {
            $files = [$files];
        }

        if (!$files || count($files) === 0) {
            error_log('Aucun fichier fourni');
            $session->set("import_progress_{$sessionId}", [
                'current' => 0,
                'total' => 0,
                'status' => 'error',
                'message' => 'Aucun fichier fourni'
            ]);
            return new JsonResponse(['status' => 'error', 'message' => 'Aucun fichier fourni', 'sessionId' => $sessionId], Response::HTTP_BAD_REQUEST);
        }

        $totalImportedCount = 0;
        $totalSkippedCount = 0;
        $allErrors = [];
        $processedFiles = [];
        $skippedLines = [];

        // Compter le nombre total de lignes pour la progression
        $totalLines = 0;
        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
            if ($extension === 'csv') {
                $tempHandle = fopen($file->getPathname(), 'r');
                if ($tempHandle) {
                    while (fgetcsv($tempHandle, 0, ',', '"', '\\') !== FALSE) {
                        $totalLines++;
                    }
                    fclose($tempHandle);
                    $totalLines--; // Soustraire la ligne d'en-tête
                }
            }
        }

        $session->set("import_progress_{$sessionId}", [
            'current' => 0,
            'total' => $totalLines,
            'status' => 'processing',
            'message' => 'Traitement en cours...'
        ]);

        $currentLine = 0;

        foreach ($files as $file) {
            // Vérifier que c'est un fichier CSV en utilisant l'extension du fichier
            $extension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
            if ($extension !== 'csv') {
                $allErrors[] = "Le fichier {$file->getClientOriginalName()} n'est pas au format CSV";
                continue;
            }

            $filePath = $file->getPathname();
            $fileName = $file->getClientOriginalName();

            error_log("Traitement du fichier: {$fileName}, chemin: {$filePath}");

            // Ouvrir le fichier CSV
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                error_log("Impossible d'ouvrir le fichier {$fileName}");
                $allErrors[] = "Impossible d'ouvrir le fichier {$fileName}";
                continue;
            }

            // Lire la première ligne pour obtenir les en-têtes
            $headers = fgetcsv($handle, 0, ',', '"', '\\');
            if (!$headers) {
                fclose($handle);
                error_log("Le fichier CSV {$fileName} est vide ou mal formaté");
                $allErrors[] = "Le fichier CSV {$fileName} est vide ou mal formaté";
                continue;
            }

            error_log("En-têtes trouvés: " . implode(', ', $headers));

            // Normaliser les en-têtes (supprimer les espaces, mettre en minuscules)
            $headers = array_map(function($header) {
                return trim(strtolower($header));
            }, $headers);

            // Définir le mapping entre les en-têtes CSV et les attributs de l'entité
            $mapping = [
                'etiquetage' => 'etiquetage',
                'modele' => 'modele',
                'modèle' => 'modele',
                'numéro de série' => 'numeroSerie',
                'numero de serie' => 'numeroSerie',
                'service' => 'service',
                'utilisateur' => 'utilisateur',
                'date de garantie' => 'dateGarantie',
                'os' => 'os',
                'version' => 'version',
                'commune' => 'commune',
                'lieu' => 'localisation',
                'localisation' => 'localisation',
                'emplacement' => 'localisation'
            ];

            $importedCount = 0;
            $skippedCount = 0;
            $errors = [];
            $rowNumber = 1; // Commencer à 1 car la ligne 0 est l'en-tête

            // Lire les données ligne par ligne
            while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== FALSE) {
                $rowNumber++;
                $currentLine++;

                // Mettre à jour la progression
                $session->set("import_progress_{$sessionId}", [
                    'current' => $currentLine,
                    'total' => $totalLines,
                    'status' => 'processing',
                    'message' => "Traitement ligne {$currentLine} sur {$totalLines}..."
                ]);

                // Ignorer les lignes vides
                if (count($data) <= 1 && empty($data[0])) {
                    error_log("Ligne {$rowNumber} ignorée (vide)");
                    continue;
                }

                error_log("Traitement de la ligne {$rowNumber}: " . implode(', ', $data));

                // Extraire l'étiquetage pour vérifier les doublons
                $etiquetage = null;
                foreach ($headers as $index => $header) {
                    if (isset($data[$index]) && isset($mapping[strtolower($header)]) && $mapping[strtolower($header)] === 'etiquetage') {
                        $etiquetage = trim($data[$index]);
                        break;
                    }
                }

                // Vérifier si l'équipement existe déjà par étiquetage (seulement si l'étiquetage n'est pas vide)
                if ($etiquetage && !empty($etiquetage)) {
                    $existingEquipment = $this->equipmentRepository->findOneBy(['etiquetage' => $etiquetage]);
                    if ($existingEquipment) {
                        $skippedCount++;
                        $skippedLines[] = "Ligne {$rowNumber}: Équipement avec étiquetage '{$etiquetage}' déjà existant";
                        error_log("Ligne {$rowNumber}: Équipement avec étiquetage '{$etiquetage}' déjà existant - ignoré");
                        continue;
                    }
                }

                // Créer un nouvel équipement
                $equipment = new Equipment();
                $equipment->setStatut('Actif'); // Statut par défaut

                // Associer les données aux attributs de l'équipement
                foreach ($headers as $index => $header) {
                    if (!isset($data[$index])) {
                        continue;
                    }

                    $value = trim($data[$index]);
                    if (empty($value)) {
                        $value = null; // Utiliser null au lieu de 'Non défini'
                    }

                    // Trouver l'attribut correspondant dans le mapping
                    $attribute = $mapping[strtolower($header)] ?? null;

                    if ($attribute === 'commune') {
                        // Rechercher la commune par nom seulement si la valeur n'est pas vide
                        if ($value) {
                            $municipality = $this->municipalityRepository->findOneBy(['name' => $value]);

                            // Si la commune n'existe pas, la créer
                            if (!$municipality) {
                                $municipality = new \App\Entity\Municipality();
                                $municipality->setName($value);
                                $this->entityManager->persist($municipality);
                                error_log("Nouvelle commune créée: " . $value);
                            }

                            $equipment->setCommune($municipality);
                        }
                    } elseif ($attribute) {
                        // Utiliser le setter approprié pour les autres attributs
                        $setterMethod = 'set' . ucfirst($attribute);
                        if (method_exists($equipment, $setterMethod)) {
                            // Gérer le cas spécifique de la date de garantie
                            if ($attribute === 'dateGarantie' && $value) {
                                try {
                                    // Essayer de parser la date (format français JJ/MM/AAAA)
                                    $date = \DateTime::createFromFormat('d/m/Y', $value);
                                    if (!$date) {
                                        // Essayer d'autres formats courants
                                        $date = \DateTime::createFromFormat('Y-m-d', $value);
                                    }

                                    if ($date) {
                                        $equipment->setDateGarantie($date);
                                    } else {
                                        $equipment->setDateGarantie(null);
                                        $errors[] = "Ligne {$rowNumber}: Format de date invalide: $value";
                                    }
                                } catch (\Exception $e) {
                                    $equipment->setDateGarantie(null);
                                    $errors[] = "Ligne {$rowNumber}: Erreur de date: " . $e->getMessage();
                                }
                            } else if ($attribute !== 'dateGarantie') {
                                $equipment->$setterMethod($value);
                            }
                        } else {
                            $errors[] = "Ligne {$rowNumber}: Setter inconnu pour l'attribut '{$attribute}'.";
                            error_log("Ligne {$rowNumber}: Setter inconnu pour l'attribut '{$attribute}'.");
                        }
                    }
                }

                // Persister l'équipement
                $this->entityManager->persist($equipment);
                $importedCount++;
                error_log("Équipement persisté: " . $equipment->getEtiquetage() . " - " . $equipment->getModele());
            }

            fclose($handle);
            error_log("Fichier {$fileName} traité: {$importedCount} équipements importés, {$skippedCount} ignorés, " . count($errors) . " erreurs");

            $totalImportedCount += $importedCount;
            $totalSkippedCount += $skippedCount;
            $allErrors = array_merge($allErrors, $errors);
            $skippedLines = array_merge($skippedLines, array_slice($skippedLines, -$skippedCount));
            $processedFiles[] = [
                'name' => $fileName,
                'imported' => $importedCount,
                'skipped' => $skippedCount,
                'errors' => count($errors)
            ];
        }

        // Mettre à jour la progression - finalisation
        $session->set("import_progress_{$sessionId}", [
            'current' => $totalLines,
            'total' => $totalLines,
            'status' => 'saving',
            'message' => 'Enregistrement en base de données...'
        ]);

        // Enregistrer les modifications en base de données
        error_log("Enregistrement en base de données de {$totalImportedCount} équipements");
        $this->entityManager->flush();
        error_log("Enregistrement terminé avec succès");

        // Finaliser la progression
        $session->set("import_progress_{$sessionId}", [
            'current' => $totalLines,
            'total' => $totalLines,
            'status' => 'completed',
            'message' => 'Import terminé avec succès'
        ]);

        // Créer le log
        $log = new Log();
        $log->setAction('IMPORT_CSV');
        $log->setEntityType('Equipment');
        $log->setEntityId(0);
        $log->setDetails("Importation CSV: $totalImportedCount équipements importés. " .
                        (count($allErrors) > 0 ? "Erreurs: " . implode(', ', $allErrors) : ""));
        $log->setUsername($this->getUser() ? $this->getUser()->getUserIdentifier() : 'Système');
        $log->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        // Préparer la réponse
        $response = [
            'status' => 'success',
            'message' => "{$totalImportedCount} équipements importés avec succès" .
                        ($totalSkippedCount > 0 ? ", {$totalSkippedCount} lignes ignorées (doublons)" : ""),
            'destination' => $this->uploadsDirectory,
            'processedFiles' => $processedFiles,
            'errors' => $allErrors,
            'skippedLines' => $skippedLines,
            'totalImported' => $totalImportedCount,
            'totalSkipped' => $totalSkippedCount,
            'sessionId' => $sessionId
        ];

        // Log de la réponse pour déboguer
        error_log("Réponse JSON: " . json_encode($response));

        // Renvoyer une réponse JSON avec les informations sur l'importation
        return new JsonResponse($response);
    }

    /**
     * Supprime les accents d'une chaîne de caractères
     */
    private function removeAccents(string $string): string
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        $chars = [
            // Decompositions for Latin-1 Supplement
            chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
            chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
            chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
            chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
            chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
            chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
            chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
            chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
            chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
            chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
            chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
            chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
            chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
            chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
            chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
            chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
            chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
            chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
            chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
            chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
            chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
            chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
            chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
            chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
            chr(195).chr(185) => 'u', chr(195).chr(186) => 'u',
            chr(195).chr(187) => 'u', chr(195).chr(188) => 'u',
            chr(195).chr(189) => 'y', chr(195).chr(191) => 'y',
            // Decompositions for Latin Extended-A
            chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
            chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
            chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
            chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
            chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
            chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
            chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
            chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
            chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
            chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
            chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
            chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
            chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
            chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
            chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
            chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
            chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
            chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
            chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
            chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
            chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
            chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
            chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
            chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
            chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
            chr(196).chr(178) => 'IJ', chr(196).chr(179) => 'ij',
            chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
            chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
            chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
            chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
            chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
            chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
        ];

        return strtr($string, $chars);
    }

    #[Route('/statistics/{municipalityId}', name: 'equipment_statistics_by_municipality', methods: ['GET'])]
    public function getStatisticsByMunicipality(int $municipalityId): JsonResponse
    {
        $municipality = $this->municipalityRepository->find($municipalityId);

        if (!$municipality) {
            return new JsonResponse(['success' => false, 'message' => 'Commune non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Nombre d'équipements reliés
        $equipmentCount = $this->equipmentRepository->countByMunicipality($municipalityId);

        // Nombre d'incidents (panne)
        // On considère les logs de type 'Equipment' avec action 'INCIDENT' liés aux équipements de cette commune
        $incidentCount = 0;
        $equipments = $this->equipmentRepository->findBy(['commune' => $municipalityId]);
        foreach ($equipments as $equipment) {
            // Compter les incidents pour chaque équipement
            // Note: Cette logique peut être adaptée selon la façon dont les incidents sont enregistrés
            $incidents = $this->entityManager->getRepository(Log::class)->findBy([
                'entityType' => 'Equipment',
                'entityId' => $equipment->getId(),
                'action' => 'INCIDENT'
            ]);
            $incidentCount += count($incidents);
        }

        // Différents types de version des équipements
        $equipmentVersions = $this->equipmentRepository->findDistinctVersionsByMunicipality($municipalityId);
        $versions = array_column($equipmentVersions, 'version');

        return new JsonResponse([
            'success' => true,
            'municipalityName' => $municipality->getName(),
            'equipmentCount' => $equipmentCount,
            'incidentCount' => $incidentCount,
            'equipmentVersions' => $versions,
        ]);
    }

    /**
     * Exporte les données des équipements au format CSV
     */
    #[Route('/export/csv', name: 'equipment_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        // Récupérer les filtres depuis la requête
        $filters = $this->getFiltersFromRequest($request);

        // Récupérer les équipements en fonction des filtres
        $equipments = $this->getFilteredEquipments($filters);

        $response = new StreamedResponse(function() use ($equipments) {
            $handle = fopen('php://output', 'w+');

            // Écrire l'en-tête UTF-8 BOM pour Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Écrire les en-têtes des colonnes
            fputcsv($handle, [
                'ID',
                'Commune',
                'Étiquetage',
                'Modèle',
                'Numéro de série',
                'Service',
                'Utilisateur',
                'Date de garantie',
                'OS',
                'Version',
                'Statut'
            ], ';');

            // Écrire les données
            foreach ($equipments as $equipment) {
                fputcsv($handle, [
                    $equipment->getId(),
                    $equipment->getCommune() ? $equipment->getCommune()->getName() : 'Non défini',
                    $equipment->getEtiquetage() ?: 'Non défini',
                    $equipment->getModele(),
                    $equipment->getNumeroSerie() ?: 'Non défini',
                    $equipment->getService(),
                    $equipment->getUtilisateur() ?: 'Non défini',
                    $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : 'Non défini',
                    $equipment->getOs() ?: 'Non défini',
                    $equipment->getVersion() ?: 'Non défini',
                    $equipment->getStatut() ?: 'Non défini'
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'equipments_export_' . date('Y-m-d_His') . '.csv'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * Prépare les données pour l'export PDF (rendu HTML qui sera converti en PDF côté client)
     */
    #[Route('/export/pdf', name: 'equipment_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request): Response
    {
        // Récupérer les filtres depuis la requête
        $filters = $this->getFiltersFromRequest($request);

        // Récupérer les équipements en fonction des filtres
        $equipments = $this->getFilteredEquipments($filters);

        // Rendre la vue HTML qui sera convertie en PDF côté client
        return $this->render('exports/equipments_pdf.html.twig', [
            'equipments' => $equipments,
            'date_export' => new \DateTime('now', new \DateTimeZone('Europe/Paris')),
            'titre' => 'Export du parc informatique'
        ]);
    }

    /**
     * Récupère les filtres depuis la requête
     */
    private function getFiltersFromRequest(Request $request): array
    {
        $filters = [];

        // Récupérer les filtres depuis les paramètres de la requête
        if ($request->query->has('commune')) {
            $filters['commune'] = $request->query->get('commune');
        }

        if ($request->query->has('service')) {
            $filters['service'] = $request->query->get('service');
        }

        if ($request->query->has('modele')) {
            $filters['modele'] = $request->query->get('modele');
        }

        if ($request->query->has('statut')) {
            $filters['statut'] = $request->query->get('statut');
        }

        if ($request->query->has('os')) {
            $filters['os'] = $request->query->get('os');
        }

        if ($request->query->has('localisation')) {
            $filters['localisation'] = $request->query->get('localisation');
        }

        if ($request->query->has('etiquetage')) {
            $filters['etiquetage'] = $request->query->get('etiquetage');
        }

        if ($request->query->has('numeroSerie')) {
            $filters['numeroSerie'] = $request->query->get('numeroSerie');
        }

        if ($request->query->has('utilisateur')) {
            $filters['utilisateur'] = $request->query->get('utilisateur');
        }

        if ($request->query->has('version')) {
            $filters['version'] = $request->query->get('version');
        }

        return $filters;
    }

    /**
     * Récupère les équipements filtrés
     */
    private function getFilteredEquipments(array $filters): array
    {
        $criteria = [];

        // Construire les critères de recherche en fonction des filtres
        if (isset($filters['commune']) && !empty($filters['commune'])) {
            $commune = $this->municipalityRepository->find($filters['commune']);
            if ($commune) {
                $criteria['commune'] = $commune;
            }
        }

        if (isset($filters['service']) && !empty($filters['service'])) {
            $criteria['service'] = $filters['service'];
        }

        if (isset($filters['modele']) && !empty($filters['modele'])) {
            $criteria['modele'] = $filters['modele'];
        }

        if (isset($filters['statut']) && !empty($filters['statut'])) {
            $criteria['statut'] = $filters['statut'];
        }

        if (isset($filters['os']) && !empty($filters['os'])) {
            $criteria['os'] = $filters['os'];
        }

        // Si aucun filtre n'est appliqué, récupérer tous les équipements
        if (empty($criteria)) {
            return $this->equipmentRepository->findAll();
        }

        // Sinon, récupérer les équipements filtrés
        return $this->equipmentRepository->findBy($criteria);
    }
}