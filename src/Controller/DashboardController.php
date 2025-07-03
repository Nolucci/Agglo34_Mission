<?php
namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\PhoneLineRepository;
use App\Repository\MunicipalityRepository;
use App\Repository\SettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

class DashboardController extends AbstractController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function index(PhoneLineRepository $phoneLineRepository, MunicipalityRepository $municipalityRepository, \App\Repository\BoxRepository $boxRepository, \App\Repository\EquipmentRepository $equipmentRepository, UserRepository $userRepository): Response
    {
        // Récupérer les données de la base de données
        $lines = $phoneLineRepository->findAll();

        // Récupérer tous les agents
        $agents = $userRepository->findAll();

        // Récupérer toutes les communes
        $allMunicipalities = $municipalityRepository->findAll();

        // Créer un tableau associatif pour éliminer les doublons par nom (insensible à la casse)
        $uniqueMunicipalities = [];
        foreach ($allMunicipalities as $municipality) {
            $lowerName = strtolower($municipality->getName());
            if (!isset($uniqueMunicipalities[$lowerName])) {
                $uniqueMunicipalities[$lowerName] = $municipality;
            }
        }

        // Convertir en tableau simple et trier par nom
        $municipalities = array_values($uniqueMunicipalities);
        usort($municipalities, function($a, $b) {
            return strcasecmp($a->getName(), $b->getName());
        });

        // Récupérer les équipements des boîtes
        $boxEquipments = $boxRepository->findAll();

        // Récupérer les équipements informatiques
        $parkEquipments = $equipmentRepository->findAll();

        // Combiner tous les équipements pour les statistiques
        $equipments = array_merge($boxEquipments, $parkEquipments);

        // Calculer les statistiques des lignes téléphoniques
        $uniqueOperators = [];
        $uniqueServices = [];
        $globalLines = 0; // Cette variable ne sera plus utilisée pour les stats de fonctionnement
        $localLines = 0; // Cette variable ne sera plus utilisée pour les stats de fonctionnement
        $workingLines = 0; // Initialisation de la nouvelle variable
        $notWorkingLines = 0; // Initialisation de la nouvelle variable

        foreach ($lines as $line) {
            $operator = $line->getOperator();
            $service = $line->getService();

            if ($operator && !in_array($operator, $uniqueOperators)) {
                $uniqueOperators[] = $operator;
            }

            if ($service && !in_array($service, $uniqueServices)) {
                $uniqueServices[] = $service;
            }

            if ($line->isWorking()) {
                $workingLines++;
            } else {
                $notWorkingLines++;
            }
        }

        $phoneLineStats = [
            'total_lines' => count($lines),
            'unique_operators' => count($uniqueOperators),
            'unique_services' => count($uniqueServices),
            'working_lines' => $workingLines,
            'not_working_lines' => $notWorkingLines,
        ];

        // Calculer les statistiques du parc
        $uniqueParkServices = [];
        $uniqueMunicipalities = [];
        $activeEquipments = 0;

        foreach ($equipments as $equipment) {
            $location = $equipment->getLocation();
            $municipality = $equipment->getMunicipality();

            if ($location && !in_array($location, $uniqueParkServices)) {
                $uniqueParkServices[] = $location;
            }

            if ($municipality && !in_array($municipality, $uniqueMunicipalities)) {
                $uniqueMunicipalities[] = $municipality;
            }

            if ($equipment->isActive()) {
                $activeEquipments++;
            }
        }

        $parkStats = [
            'total_equipments' => count($equipments),
            'unique_services' => count($uniqueParkServices),
            'unique_municipalities' => count($uniqueMunicipalities),
            'active_equipments' => $activeEquipments,
        ];

        // Récupérer l'utilisateur connecté ou utiliser des données par défaut
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        // Passer l'objet User directement au template

        return $this->render('index.html.twig', [
            'page_title' => "Tableau de bord",
            'user' => $currentUser,
            'municipalities' => $municipalities,
            'phoneLines' => $lines,
            'phoneLineStats' => $phoneLineStats,
            'equipments' => $equipments,
            'parkStats' => $parkStats,
            'agents' => $agents,
        ]);
    }

    #[Route('/lines', name: 'lines')]
    public function lines(PhoneLineRepository $phoneLineRepository, MunicipalityRepository $municipalityRepository): Response
    {
        $phoneLines = $phoneLineRepository->findAll();

        $formattedPhoneLines = [];
        foreach ($phoneLines as $line) {
            $formattedPhoneLines[] = [
                'id' => $line->getId(),
                'directLine' => $line->getDirectLine(),
                'shortNumber' => $line->getShortNumber(),
                'location' => $line->getLocation(),
                'service' => $line->getService(),
                'assignedTo' => $line->getAssignedTo(),
                'operator' => $line->getOperator(),
                'lineType' => $line->getLineType(),
                'isWorking' => $line->isWorking(),
                'municipality' => $line->getMunicipality() ? [
                    'id' => $line->getMunicipality()->getId(),
                    'name' => $line->getMunicipality()->getName(),
                ] : null,
            ];
        }

        $phoneLineStats = [
            'total_lines' => count($phoneLines),
            'unique_operators' => count(array_values(array_unique(array_column($formattedPhoneLines, 'operator')))),
            'unique_services' => count(array_values(array_unique(array_column($formattedPhoneLines, 'service')))),
            'working_lines' => count(array_filter($formattedPhoneLines, fn($line) => $line['isWorking'])),
            'not_working_lines' => count(array_filter($formattedPhoneLines, fn($line) => !$line['isWorking'])),
        ];

        // Récupérer toutes les communes
        $allMunicipalities = $municipalityRepository->findAll();

        // Créer un tableau associatif pour éliminer les doublons par nom (insensible à la casse)
        $uniqueMunicipalities = [];
        foreach ($allMunicipalities as $municipality) {
            $lowerName = strtolower($municipality->getName());
            if (!isset($uniqueMunicipalities[$lowerName])) {
                $uniqueMunicipalities[$lowerName] = $municipality;
            }
        }

        // Convertir en tableau simple et trier par nom
        $municipalities = array_values($uniqueMunicipalities);
        usort($municipalities, function($a, $b) {
            return strcasecmp($a->getName(), $b->getName());
        });

        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        // Passer l'objet User directement au template

        return $this->render('pages/lines.html.twig', [
            'page_title' => "Lignes téléphoniques",
            'user' => $currentUser,
            'municipalities' => $municipalities,
            'phoneLines' => $formattedPhoneLines,
            'phoneLineStats' => $phoneLineStats,
            'salesChartData' => $this->generateLineStatsByMunicipality($phoneLines),
            'operatorChartData' => $this->generateLineStatsByOperator($phoneLines),
        ]);
    }

    #[Route('/agents', name: 'agents')]
    public function agents(): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        $agents = $this->userRepository->findAll();

        return $this->render('pages/agents.html.twig', [
            'page_title' => "Liste des Agents",
            'agents' => $agents,
            'user' => $currentUser,
        ]);
    }

    #[Route('/account', name: 'account')]
    public function account(): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('pages/account.html.twig', [
            'page_title' => "Tableau de bord",
            'user' => $currentUser
        ]);
    }

    #[Route('/park', name: 'park')]
    public function park(MunicipalityRepository $municipalityRepository, \App\Repository\EquipmentRepository $equipmentRepository): Response
    {
        $equipments = $equipmentRepository->findAll();

        // Récupérer toutes les communes
        $allMunicipalities = $municipalityRepository->findAll();

        // Créer un tableau associatif pour éliminer les doublons par nom (insensible à la casse)
        $uniqueMunicipalities = [];
        foreach ($allMunicipalities as $municipality) {
            $lowerName = strtolower($municipality->getName());
            if (!isset($uniqueMunicipalities[$lowerName])) {
                $uniqueMunicipalities[$lowerName] = $municipality;
            }
        }

        // Convertir en tableau simple et trier par nom
        $municipalities = array_values($uniqueMunicipalities);
        usort($municipalities, function($a, $b) {
            return strcasecmp($a->getName(), $b->getName());
        });

        $formattedEquipments = [];
        foreach ($equipments as $equipment) {
            $commune = $equipment->getCommune();
            $formattedCommune = null;

            if ($commune instanceof \App\Entity\Municipality) {
                $formattedCommune = [
                    'id' => $commune->getId(),
                    'name' => $commune->getName(),
                ];
            }

            $formattedEquipments[] = [
                'id' => $equipment->getId(),
                'commune' => $formattedCommune,
                'etiquetage' => $equipment->getEtiquetage(),
                'modele' => $equipment->getModele(),
                'numeroSerie' => $equipment->getNumeroSerie(),
                'service' => $equipment->getService(),
                'utilisateur' => $equipment->getUtilisateur(),
                'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
                'os' => $equipment->getOs(),
                'version' => $equipment->getVersion(),
                'statut' => $equipment->getStatut(),
            ];
        }

        // Calculer les statistiques du parc
        $uniqueServices = [];
        $uniqueCommunes = [];
        $activeEquipments = 0;

        foreach ($formattedEquipments as $equipment) {
            // Compter les services uniques
            if (isset($equipment['service']) && !in_array($equipment['service'], $uniqueServices)) {
                $uniqueServices[] = $equipment['service'];
            }

            // Compter les communes uniques
            if (isset($equipment['commune']) && $equipment['commune'] !== null && !in_array($equipment['commune']['name'], $uniqueCommunes)) {
                $uniqueCommunes[] = $equipment['commune']['name'];
            }

            // Compter les équipements actifs (statut 'Actif')
            if (isset($equipment['statut']) && $equipment['statut'] === 'Actif') {
                $activeEquipments++;
            }
        }

        $parkStats = [
            'total_equipments' => count($equipments),
            'unique_services' => count($uniqueServices),
            'unique_municipalities' => count($uniqueCommunes),
            'active_equipments' => $activeEquipments,
        ];

        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        // Passer l'objet User directement au template

        return $this->render('pages/park.html.twig', [
            'page_title' => "Parc Informatique",
            'equipments' => $formattedEquipments,
            'municipalities' => $municipalities,
            'parkStats' => $parkStats,
            'teamChartData' => $this->generateParkStatsByModele($formattedEquipments),
            'statusChartData' => $this->generateParkStatsByStatut($formattedEquipments),
            'user' => $currentUser,
        ]);
    }

    #[Route('/calendar', name: 'calendar')]
    public function calendar(): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('pages/calendar.html.twig', [
            'page_title' => "Tableau de bord",
            'user' => $currentUser
        ]);
    }

    #[Route('/documents', name: 'documents')]
    public function documents(): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        // Passer l'objet User directement au template

        return $this->render('pages/documents.html.twig', [
            'page_title' => "Importer des Fichiers",
            'user' => $currentUser
        ]);
    }

    #[Route('/map', name: 'map')]
    public function map(): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('pages/map.html.twig', [
            'page_title' => "Tableau de bord",
            'user' => $currentUser
        ]);
    }
    #[Route('/settings', name: 'settings')]
    public function settings(SettingsRepository $settingsRepository, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        $settings = $settingsRepository->findOneBy([]);

        if (!$settings) {
            // Créer un nouvel objet Settings avec des valeurs par défaut
            $settings = new \App\Entity\Settings();
            $settings->setDarkTheme(false);
            $settings->setCrudEnabled(false);
            $settings->setDisplayMode('liste');
            $settings->setItemsPerPage(10);
            $settings->setAppName('Agglo34 Mission');
            $settings->setWelcomeMessage('Bienvenue sur l\'application Agglo34 Mission');
            $settings->setAlertThreshold(5);
            $settings->setFeatureEnabled(false);

            // Persister le nouvel objet
            $entityManager->persist($settings);
            $entityManager->flush();
        }

        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('pages/settings.html.twig', [
            'page_title' => "Paramètres administrateur",
            'settings' => $settings,
            'user' => $currentUser
        ]);
    }

    #[Route('/settings/save', name: 'settings_save', methods: ['POST'])]
    public function saveSettings(\Symfony\Component\HttpFoundation\Request $request, SettingsRepository $settingsRepository, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        $crudEnabled = $request->request->get('crud_enabled') === '1';
        $displayMode = $request->request->get('display_mode');
        $itemsPerPage = (int)$request->request->get('items_per_page');
        $appName = $request->request->get('app_name');
        $welcomeMessage = $request->request->get('welcome_message');
        $alertThreshold = (int)$request->request->get('alert_threshold');
        $featureEnabled = $request->request->get('feature_enabled') === '1';

        // Récupérer les paramètres existants ou créer un nouvel objet
        $settings = $settingsRepository->findOneBy([]);
        if (!$settings) {
            $settings = new \App\Entity\Settings();
        }

        // Mettre à jour les paramètres
        $settings->setCrudEnabled($crudEnabled);
        $settings->setDisplayMode($displayMode);
        $settings->setItemsPerPage($itemsPerPage);
        $settings->setAppName($appName);
        $settings->setWelcomeMessage($welcomeMessage);
        $settings->setAlertThreshold($alertThreshold);
        $settings->setFeatureEnabled($featureEnabled);

        // Sauvegarder en base de données
        $entityManager->persist($settings);
        $entityManager->flush();

        $this->addFlash('success', 'Paramètres enregistrés avec succès.');

        return $this->redirectToRoute('settings');
    }
    private function generateLineStatsByMunicipality(array $lines): array
    {
        $stats = [];
        foreach ($lines as $line) {
            $municipality = $line->getMunicipality();

            if ($municipality) {
                $municipalityName = $municipality->getName();

                if ($municipalityName) {
                    if (!isset($stats[$municipalityName])) {
                        $stats[$municipalityName] = 0;
                    }
                    $stats[$municipalityName]++;
                }
            }
        }

        // Si aucune donnée n'est disponible, fournir des données par défaut
        if (empty($stats)) {
            return [
                'labels' => ['Aucune donnée'],
                'data' => [0],
            ];
        }

        return [
            'labels' => array_keys($stats),
            'data' => array_values($stats),
        ];
    }

    private function generateParkStatsByModele(array $formattedEquipments): array
    {
        $stats = [];

        if (empty($formattedEquipments)) {
            return [
                'labels' => ['Aucune donnée'],
                'data' => [0],
            ];
        }

        foreach ($formattedEquipments as $equipment) {
            $modele = $equipment['modele'];
            if (!isset($stats[$modele])) {
                $stats[$modele] = 0;
            }
            $stats[$modele]++;
        }

        return [
            'labels' => array_keys($stats),
            'data' => array_values($stats),
        ];
    }

    private function generateLineStatsByOperator(array $lines): array
    {
        $stats = [];
        foreach ($lines as $line) {
            $operator = $line->getOperator();
            if (!isset($stats[$operator])) {
                $stats[$operator] = 0;
            }
            $stats[$operator]++;
        }

        return [
            'labels' => array_keys($stats),
            'data' => array_values($stats),
        ];
    }

    private function generateParkStatsByStatut(array $formattedEquipments): array
    {
        $stats = [
            'Actif' => 0,
            'Inactif' => 0,
            'Panne' => 0,
        ];

        if (empty($formattedEquipments)) {
            return [
                'labels' => array_keys($stats),
                'data' => array_values($stats),
            ];
        }

        foreach ($formattedEquipments as $equipment) {
            if (isset($equipment['statut'])) {
                if (!isset($stats[$equipment['statut']])) {
                     $stats[$equipment['statut']] = 0;
                }
                $stats[$equipment['statut']]++;
            } else {
                // Compter les équipements sans statut défini comme Inactif par défaut
                $stats['Inactif']++;
            }
        }

        return [
            'labels' => array_keys($stats),
            'data' => array_values($stats),
        ];
    }
}
