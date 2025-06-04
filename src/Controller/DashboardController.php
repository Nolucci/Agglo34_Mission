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
    public function index(PhoneLineRepository $phoneLineRepository, MunicipalityRepository $municipalityRepository, \App\Repository\BoxRepository $boxRepository): Response
    {
        $lines = $phoneLineRepository->findAll();
        
        // Calculer les statistiques des lignes téléphoniques
        $uniqueOperators = [];
        $uniqueServices = [];
        $globalLines = 0;
        $localLines = 0;
        
        foreach ($lines as $line) {
            if (!in_array($line->getOperator(), $uniqueOperators)) {
                $uniqueOperators[] = $line->getOperator();
            }
            
            if (!in_array($line->getService(), $uniqueServices)) {
                $uniqueServices[] = $line->getService();
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

        $municipalities = $municipalityRepository->findAll();

        // Récupérer les équipements du parc informatique
        $equipments = $boxRepository->findAll();
        
        // Calculer les statistiques du parc
        $uniqueParkServices = [];
        $uniqueMunicipalities = [];
        $activeEquipments = 0;

        foreach ($equipments as $equipment) {
            if (!in_array($equipment->getLocation(), $uniqueParkServices)) {
                $uniqueParkServices[] = $equipment->getLocation();
            }
            
            $municipalityName = $equipment->getMunicipality();
            if (!in_array($municipalityName, $uniqueMunicipalities)) {
                $uniqueMunicipalities[] = $municipalityName;
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

        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('index.html.twig', [
            'page_title' => "Tableau de bord",
            'user' => $user,
            'municipalities' => $municipalities,
            'lines' => $lines,
            'phoneLines' => $lines,
            'phoneLineStats' => $phoneLineStats,
            'equipments' => $equipments,
            'parkStats' => $parkStats,
        ]);
    }

    #[Route('/lines', name: 'lines')]
    public function lines(PhoneLineRepository $phoneLineRepository, MunicipalityRepository $municipalityRepository): Response
    {
        $lines = $phoneLineRepository->findAll();

        $phoneLineStats = [
            'total_lines' => count($lines),
            'unique_operators' => count(array_values(array_unique(array_column($lines, 'operator')))),
            'unique_services' => count(array_values(array_unique(array_column($lines, 'service')))),
            'global_lines' => count(array_filter($lines, fn($line) => $line->isGlobal())),
            'local_lines' => count(array_filter($lines, fn($line) => !$line->isGlobal())),
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
            'lines' => $lines,
            'phoneLines' => $lines,
            'phoneLineStats' => $phoneLineStats,
            'salesChartData' => $this->generateLineStatsByMunicipality($lines), 
            'operatorChartData' => $this->generateLineStatsByOperator($lines), 
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

        // Calculer les statistiques du parc
        $uniqueServices = [];
        $uniqueMunicipalities = [];
        $activeEquipments = 0;

        foreach ($equipments as $equipment) {
            // Compter les services uniques (utilisons location comme service pour l'exemple)
            if (!in_array($equipment->getLocation(), $uniqueServices)) {
                $uniqueServices[] = $equipment->getLocation();
            }
            
            // Compter les municipalités uniques
            $municipalityName = $equipment->getMunicipality();
            if (!in_array($municipalityName, $uniqueMunicipalities)) {
                $uniqueMunicipalities[] = $municipalityName;
            }
            
            // Compter les équipements actifs
            if ($equipment->isActive()) {
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
            'equipments' => $equipments,
            'parkStats' => $parkStats,
            'teamChartData' => $this->generateParkStatsByType($equipments),
            'statusChartData' => $this->generateParkStatsByStatus($equipments),
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
    public function settings(SettingsRepository $settingsRepository): Response
    {
        $settings = $settingsRepository->findOneBy([]);

        if (!$settings) {
            $settings = [];
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
    public function saveSettings(\Symfony\Component\HttpFoundation\Request $request): Response
    {
        $crudEnabled = $request->request->get('crud_enabled');
        $displayMode = $request->request->get('display_mode');
        $itemsPerPage = $request->request->get('items_per_page');
        $appName = $request->request->get('app_name');
        $welcomeMessage = $request->request->get('welcome_message');
        $alertThreshold = $request->request->get('alert_threshold');
        $featureEnabled = $request->request->get('feature_enabled') === '1';


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
                
                if (!isset($stats[$municipalityName])) {
                    $stats[$municipalityName] = 0;
                }
                $stats[$municipalityName]++;
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

    private function generateParkStatsByType(array $equipments): array
    {
        $stats = [];
        
        if (empty($equipments)) {
            return [
                'labels' => ['Ordinateurs', 'Imprimantes', 'Téléphones', 'Autres'],
                'data' => [0, 0, 0, 0],
            ];
        }
        
        foreach ($equipments as $equipment) {
            $type = $equipment->getType();
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

    private function generateParkStatsByStatus(array $equipments): array
    {
        $stats = [
            'Actif' => 0,
            'Inactif' => 0,
        ];
        
        if (empty($equipments)) {
            return [
                'labels' => array_keys($stats),
                'data' => array_values($stats),
            ];
        }
        
        foreach ($equipments as $equipment) {
            if ($equipment->isActive()) {
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
