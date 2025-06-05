<?php
namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\PhoneLineRepository;
use App\Repository\MunicipalityRepository;
use App\Repository\SettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function index(PhoneLineRepository $phoneLineRepository, MunicipalityRepository $municipalityRepository, \App\Repository\BoxRepository $boxRepository, UserRepository $userRepository): Response
    {
        // Récupérer les données de la base de données
        $lines = $phoneLineRepository->findAll();
        $municipalities = $municipalityRepository->findAll();
        $equipments = $boxRepository->findAll();
        
        // Calculer les statistiques des lignes téléphoniques
        $uniqueOperators = [];
        $uniqueServices = [];
        $globalLines = 0;
        $localLines = 0;
        
        foreach ($lines as $line) {
            $operator = $line->getOperator();
            $service = $line->getService();
            
            if ($operator && !in_array($operator, $uniqueOperators)) {
                $uniqueOperators[] = $operator;
            }
            
            if ($service && !in_array($service, $uniqueServices)) {
                $uniqueServices[] = $service;
            }
            
            if ($line->isGlobal()) {
                $globalLines++;
            } else {
                $localLines++;
            }
        }

        $phoneLineStats = [
            'total_lines' => count($lines),
            'unique_operators' => count($uniqueOperators),
            'unique_services' => count($uniqueServices),
            'global_lines' => $globalLines,
            'local_lines' => $localLines,
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
        
        if ($currentUser) {
            $user = [
                'name' => $currentUser->getUsername(),
                'email' => $currentUser->getEmail(),
                'image_url' => '/images/img.png',
            ];
        } else {
            $user = [
                'name' => 'Frederic F',
                'email' => 'fredericf@example.com',
                'image_url' => '/images/img.png',
            ];
        }

        return $this->render('index.html.twig', [
            'page_title' => "Tableau de bord",
            'user' => $user,
            'municipalities' => $municipalities,
            'phoneLines' => $lines,
            'phoneLineStats' => $phoneLineStats,
            'equipments' => $equipments,
            'parkStats' => $parkStats,
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
                'location' => $line->getLocation(),
                'service' => $line->getService(),
                'assignedTo' => $line->getAssignedTo(),
                'operator' => $line->getOperator(),
                'lineType' => $line->getLineType(),
                'isGlobal' => $line->isGlobal(),
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
            'global_lines' => count(array_filter($formattedPhoneLines, fn($line) => $line['isGlobal'])),
            'local_lines' => count(array_filter($formattedPhoneLines, fn($line) => !$line['isGlobal'])),
        ];

        $municipalities = $municipalityRepository->findAll();

        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('pages/lines.html.twig', [
            'page_title' => "Lignes téléphoniques",
            'user' => $user,
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
        $agents = $this->userRepository->findAll();

        return $this->render('pages/agents.html.twig', [
            'page_title' => "Liste des Agents",
            'agents' => $agents,
        ]);
    }

    #[Route('/account', name: 'account')]
    public function account(): Response
    {
        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('pages/account.html.twig', [
            'page_title' => "Tableau de bord",
            'user' => $user
        ]);
    }

    #[Route('/park', name: 'park')]
    public function park(MunicipalityRepository $municipalityRepository, \App\Repository\BoxRepository $boxRepository): Response
    {
        $equipments = $boxRepository->findAll();
        $municipalities = $municipalityRepository->findAll();

        $formattedEquipments = [];
        foreach ($equipments as $equipment) {
            $municipality = $equipment->getMunicipality();
            $formattedMunicipality = null;

            if ($municipality instanceof \App\Entity\Municipality) {
                $formattedMunicipality = [
                    'id' => $municipality->getId(),
                    'name' => $municipality->getName(),
                ];
            } elseif (is_string($municipality) && !empty($municipality)) {
                // Handle case where municipality is stored as a string
                $formattedMunicipality = [
                    'id' => null, // No ID available for string municipalities
                    'name' => $municipality,
                ];
            }

            $formattedEquipments[] = [
                'id' => $equipment->getId(),
                'type' => $equipment->getType(),
                'brand' => $equipment->getBrand(),
                'model' => $equipment->getModel(),
                'assignedTo' => $equipment->getAssignedTo(),
                'location' => $equipment->getLocation(),
                'isActive' => $equipment->isActive(),
                'municipality' => $formattedMunicipality,
            ];
        }

        // Calculer les statistiques du parc
        $uniqueServices = [];
        $uniqueMunicipalities = [];
        $activeEquipments = 0;

        foreach ($formattedEquipments as $equipment) {
            // Compter les services uniques (utilisons location comme service pour l'exemple)
            if (isset($equipment['location']) && !in_array($equipment['location'], $uniqueServices)) {
                $uniqueServices[] = $equipment['location'];
            }
            
            // Compter les municipalités uniques
            if (isset($equipment['municipality']) && $equipment['municipality'] !== null && !in_array($equipment['municipality']['name'], $uniqueMunicipalities)) {
                $uniqueMunicipalities[] = $equipment['municipality']['name'];
            }
            
            // Compter les équipements actifs
            if (isset($equipment['isActive']) && $equipment['isActive']) {
                $activeEquipments++;
            }
        }

        $parkStats = [
            'total_equipments' => count($equipments),
            'unique_services' => count($uniqueServices),
            'unique_municipalities' => count($uniqueMunicipalities),
            'active_equipments' => $activeEquipments,
        ];

        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('pages/park.html.twig', [
            'page_title' => "Parc Informatique",
            'equipments' => $formattedEquipments,
            'municipalities' => $municipalities,
            'parkStats' => $parkStats,
            'teamChartData' => $this->generateParkStatsByType($formattedEquipments),
            'statusChartData' => $this->generateParkStatsByStatus($formattedEquipments),
            'user' => $user,
        ]);
    }

    #[Route('/calendar', name: 'calendar')]
    public function calendar(): Response
    {
        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('pages/calendar.html.twig', [
            'page_title' => "Tableau de bord",
            'user' => $user
        ]);
    }

    #[Route('/documents', name: 'documents')]
    public function documents(): Response
    {
        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('pages/documents.html.twig', [
            'page_title' => "Importer des Fichiers",
            'user' => $user
        ]);
    }

    #[Route('/map', name: 'map')]
    public function map(): Response
    {
        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('pages/map.html.twig', [
            'page_title' => "Tableau de bord",
            'user' => $user
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

        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('pages/settings.html.twig', [
            'page_title' => "Paramètres administrateur",
            'settings' => $settings,
            'user' => $user
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

    private function generateParkStatsByType(array $formattedEquipments): array
    {
        $stats = [];
        
        if (empty($formattedEquipments)) {
            return [
                'labels' => ['Ordinateurs', 'Imprimantes', 'Téléphones', 'Autres'],
                'data' => [0, 0, 0, 0],
            ];
        }
        
        foreach ($formattedEquipments as $equipment) {
            $type = $equipment['type'];
            if (!isset($stats[$type])) {
                $stats[$type] = 0;
            }
            $stats[$type]++;
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

    private function generateParkStatsByStatus(array $formattedEquipments): array
    {
        $stats = [
            'Actif' => 0,
            'Inactif' => 0,
        ];
        
        if (empty($formattedEquipments)) {
            return [
                'labels' => array_keys($stats),
                'data' => array_values($stats),
            ];
        }
        
        foreach ($formattedEquipments as $equipment) {
            if ($equipment['isActive']) {
                $stats['Actif']++;
            } else {
                $stats['Inactif']++;
            }
        }

        return [
            'labels' => array_keys($stats),
            'data' => array_values($stats),
        ];
    }
}
