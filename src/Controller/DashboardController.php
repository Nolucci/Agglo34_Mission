<?php
namespace App\Controller;

use App\Repository\UserRepository;
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
    public function index(): Response
    {
        // Données statiques pour remplacer les appels au repository
        $lines = [
            [
                'id' => 1,
                'location' => 'Mairie Centrale',
                'service' => 'Administration',
                'assignedTo' => 'Jean Dupont',
                'phoneBrand' => 'Apple',
                'model' => 'iPhone 12',
                'operator' => 'Orange',
                'lineType' => 'Mobile',
                'municipality' => 'Béziers',
                'isGlobal' => true
            ],
            [
                'id' => 2,
                'location' => 'Service Technique',
                'service' => 'Maintenance',
                'assignedTo' => 'Marie Martin',
                'phoneBrand' => 'Samsung',
                'model' => 'Galaxy S21',
                'operator' => 'SFR',
                'lineType' => 'Fixe',
                'municipality' => 'Béziers',
                'isGlobal' => false
            ],
            [
                'id' => 3,
                'location' => 'Police Municipale',
                'service' => 'Sécurité',
                'assignedTo' => 'Pierre Dubois',
                'phoneBrand' => 'Google',
                'model' => 'Pixel 6',
                'operator' => 'Bouygues',
                'lineType' => 'Mobile',
                'municipality' => 'Agde',
                'isGlobal' => true
            ],
            [
                'id' => 4,
                'location' => 'Bibliothèque',
                'service' => 'Culture',
                'assignedTo' => 'Sophie Leroy',
                'phoneBrand' => 'Huawei',
                'model' => 'P30',
                'operator' => 'Free',
                'lineType' => 'Fixe',
                'municipality' => 'Sète',
                'isGlobal' => false
            ]
        ];

        // Calcul manuel des statistiques
        $phoneLineStats = [
            'total_lines' => count($lines),
            'unique_operators' => count(array_values(array_unique(array_column($lines, 'operator')))),
            'unique_services' => count(array_values(array_unique(array_column($lines, 'service')))),
            'global_lines' => count(array_filter($lines, fn($line) => $line['isGlobal'])),
            'local_lines' => count(array_filter($lines, fn($line) => !$line['isGlobal'])),
        ];

        // Récupération des communes
        $municipalities = array_map(function($line) use ($lines) {
            return [
                'id' => array_search($line['municipality'], array_column($lines, 'municipality')),
                'nom' => $line['municipality']
            ];
        }, $lines);
        $municipalities = array_map("unserialize", array_unique(array_map("serialize", $municipalities)));

       // Données statiques pour le parc informatique (copiées depuis la fonction park)
        $equipments = [
            [
                'id' => 1,
                'type' => 'Ordinateur portable',
                'brand' => 'Dell',
                'model' => 'Latitude 7400',
                'assignedTo' => 'Jean Dupont',
                'location' => 'Bureau 101',
                'municipality' => 'Béziers',
                'isActive' => true
            ],
            [
                'id' => 2,
                'type' => 'Écran',
                'brand' => 'HP',
                'model' => 'EliteDisplay',
                'assignedTo' => 'Marie Martin',
                'location' => 'Bureau 102',
                'municipality' => 'Béziers',
                'isActive' => true
            ],
            [
                'id' => 3,
                'type' => 'Imprimante',
                'brand' => 'Epson',
                'model' => 'EcoTank 4750',
                'assignedTo' => 'Service Technique',
                'location' => 'Salle d\'impression',
                'municipality' => 'Agde',
                'isActive' => true
            ],
            [
                'id' => 4,
                'type' => 'Ordinateur de bureau',
                'brand' => 'Lenovo',
                'model' => 'ThinkCentre',
                'assignedTo' => 'Sophie Leroy',
                'location' => 'Bureau 201',
                'municipality' => 'Sète',
                'isActive' => false
            ],
        ];

        // Calcul manuel des statistiques du parc
        $parkStats = [
            'total_equipments' => count($equipments),
            'unique_services' => count(array_values(array_unique(array_column($equipments, 'assignedTo')))),
            'unique_municipalities' => count(array_values(array_unique(array_column($equipments, 'municipality')))),
            'active_equipments' => count(array_filter($equipments, fn($equipment) => $equipment['isActive'])),
        ];

        return $this->render('index.html.twig', [
            'page_title' => "Tableau de bord",
            'user' => [
                'name' => 'Frederic F',
                'email' => 'fredericf@example.com',
                'image_url' => '/images/img.png',
            ],
            'municipalities' => $municipalities,
            'lines' => $lines,
            'phoneLines' => $lines,
            'phoneLineStats' => $phoneLineStats,
            'equipments' => $equipments,
            'parkStats' => $parkStats, // Ajout des statistiques du matériel
        ]);
    }

    #[Route('/lines', name: 'lines')]
    public function lines(): Response
    {
        // Données statiques pour remplacer les appels au repository
        $lines = [
            [
                'id' => 1,
                'location' => 'Mairie Centrale',
                'service' => 'Administration',
                'assignedTo' => 'Jean Dupont',
                'phoneBrand' => 'Apple',
                'model' => 'iPhone 12',
                'operator' => 'Orange',
                'lineType' => 'Mobile',
                'municipality' => 'Béziers',
                'isGlobal' => true
            ],
            [
                'id' => 2,
                'location' => 'Service Technique',
                'service' => 'Maintenance',
                'assignedTo' => 'Marie Martin',
                'phoneBrand' => 'Samsung',
                'model' => 'Galaxy S21',
                'operator' => 'SFR',
                'lineType' => 'Fixe',
                'municipality' => 'Béziers',
                'isGlobal' => false
            ],
            [
                'id' => 3,
                'location' => 'Police Municipale',
                'service' => 'Sécurité',
                'assignedTo' => 'Pierre Dubois',
                'phoneBrand' => 'Google',
                'model' => 'Pixel 6',
                'operator' => 'Bouygues',
                'lineType' => 'Mobile',
                'municipality' => 'Agde',
                'isGlobal' => true
            ],
            [
                'id' => 4,
                'location' => 'Bibliothèque',
                'service' => 'Culture',
                'assignedTo' => 'Sophie Leroy',
                'phoneBrand' => 'Huawei',
                'model' => 'P30',
                'operator' => 'Free',
                'lineType' => 'Fixe',
                'municipality' => 'Sète',
                'isGlobal' => false
            ]
        ];

        // Calcul manuel des statistiques
        $phoneLineStats = [
            'total_lines' => count($lines),
            'unique_operators' => count(array_values(array_unique(array_column($lines, 'operator')))),
            'unique_services' => count(array_values(array_unique(array_column($lines, 'service')))),
            'global_lines' => count(array_filter($lines, fn($line) => $line['isGlobal'])),
            'local_lines' => count(array_filter($lines, fn($line) => !$line['isGlobal'])),
        ];

        // Récupération des communes
        $municipalities = array_map(function($line) use ($lines) {
            return [
                'id' => array_search($line['municipality'], array_column($lines, 'municipality')),
                'nom' => $line['municipality']
            ];
        }, $lines);
        $municipalities = array_map("unserialize", array_unique(array_map("serialize", $municipalities)));

        return $this->render('pages/lines.html.twig', [
            'page_title' => "Lignes téléphoniques",
            'user' => [
                'name' => 'Frederic F',
                'email' => 'fredericf@example.com',
                'image_url' => '/images/img.png',
            ],
            'municipalities' => $municipalities,
            'lines' => $lines,
            'phoneLines' => $lines,
            'phoneLineStats' => $phoneLineStats,
            'salesChartData' => $this->generateLineStatsByMunicipality($lines),
            'operatorChartData' => $this->generateLineStatsByOperator($lines),
        ]);
    }

    // Autres méthodes du contrôleur restent inchangées
    #[Route('/agents', name: 'agents')]
    public function agents(): Response
    {
        $agents = [
            [
                'id' => 1,
                'name' => 'Frédéric Fernandez',
                'email' => 'frederic.fernandez@example.com'
            ],
            [
                'id' => 2,
                'name' => 'Marie Martin',
                'email' => 'marie.martin@example.com'
            ],
            [
                'id' => 3,
                'name' => 'Pierre Dubois',
                'email' => 'pierre.dubois@example.com'
            ],
            [
                'id' => 4,
                'name' => 'Sophie Leroy',
                'email' => 'sophie.leroy@example.com'
            ],
        ];

        return $this->render('pages/agents.html.twig', [
            'page_title' => "Liste des Agents",
            'agents' => $agents,
        ]);
    }

    #[Route('/account', name: 'account')]
    public function account(): Response
    {
        return $this->render('pages/account.html.twig', [
            'page_title' => "Tableau de bord"
        ]);
    }

    #[Route('/login', name: 'login')]
    public function login(): Response
    {
        return $this->render('pages/login.html.twig', [
            'page_title' => "Tableau de bord"
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): Response
    {
        return $this->render('pages/logout.html.twig', [
            'page_title' => "Tableau de bord"
        ]);
    }

    #[Route('/park', name: 'park')]
    public function park(): Response
    {
        // Données statiques pour le parc informatique
        $equipments = [
            [
                'id' => 1,
                'type' => 'Ordinateur portable',
                'brand' => 'Dell',
                'model' => 'Latitude 7400',
                'assignedTo' => 'Jean Dupont',
                'location' => 'Bureau 101',
                'municipality' => 'Béziers',
                'isActive' => true
            ],
            [
                'id' => 2,
                'type' => 'Écran',
                'brand' => 'HP',
                'model' => 'EliteDisplay',
                'assignedTo' => 'Marie Martin',
                'location' => 'Bureau 102',
                'municipality' => 'Béziers',
                'isActive' => true
            ],
            [
                'id' => 3,
                'type' => 'Imprimante',
                'brand' => 'Epson',
                'model' => 'EcoTank 4750',
                'assignedTo' => 'Service Technique',
                'location' => 'Salle d\'impression',
                'municipality' => 'Agde',
                'isActive' => true
            ],
            [
                'id' => 4,
                'type' => 'Ordinateur de bureau',
                'brand' => 'Lenovo',
                'model' => 'ThinkCentre',
                'assignedTo' => 'Sophie Leroy',
                'location' => 'Bureau 201',
                'municipality' => 'Sète',
                'isActive' => false
            ],
        ];

        // Calcul manuel des statistiques du parc
        $parkStats = [
            'total_equipments' => count($equipments),
            'unique_services' => count(array_values(array_unique(array_column($equipments, 'assignedTo')))), // Using assignedTo as a proxy for service
            'unique_municipalities' => count(array_values(array_unique(array_column($equipments, 'municipality')))),
            'active_equipments' => count(array_filter($equipments, fn($equipment) => $equipment['isActive'])),
        ];

        return $this->render('pages/park.html.twig', [
            'page_title' => "Parc Informatique",
            'equipments' => $equipments,
            'parkStats' => $parkStats,
            'teamChartData' => $this->generateParkStatsByType($equipments),
            'statusChartData' => $this->generateParkStatsByStatus($equipments),
        ]);
    }

    #[Route('/calendar', name: 'calendar')]
    public function calendar(): Response
    {
        return $this->render('pages/calendar.html.twig', [
            'page_title' => "Tableau de bord"
        ]);
    }

    #[Route('/documents', name: 'documents')]
    public function documents(): Response
    {
        return $this->render('pages/documents.html.twig', [
            'page_title' => "Importer des Fichiers"
        ]);
    }

    #[Route('/map', name: 'map')]
    public function map(): Response
    {
        return $this->render('pages/map.html.twig', [
            'page_title' => "Tableau de bord"
        ]);
    }
    #[Route('/settings', name: 'settings')]
    public function settings(): Response
    {
        // Pour l'instant, on passe des paramètres par défaut ou vides
        $settings = [
            'crud_enabled' => true,
            'display_mode' => 'liste',
            'items_per_page' => 10,
            'app_name' => '',
            'welcome_message' => 'Bienvenue sur le tableau de bord !',
            'alert_threshold' => 5,
            'feature_enabled' => false,
        ];

        return $this->render('pages/settings.html.twig', [
            'page_title' => "Paramètres administrateur",
            'settings' => $settings
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
        $featureEnabled = $request->request->get('feature_enabled') === '1'; // Checkbox value is '1' if checked, null otherwise

        // Ici, vous devriez ajouter la logique pour sauvegarder ces paramètres,
        // par exemple dans une base de données ou un fichier de configuration.
        // Pour l'instant, nous allons juste afficher un message de succès.

        $this->addFlash('success', 'Paramètres enregistrés avec succès.');

        return $this->redirectToRoute('settings');
    }
    private function generateLineStatsByMunicipality(array $lines): array
    {
        $stats = [];
        foreach ($lines as $line) {
            $municipality = $line['municipality'];
            if (!isset($stats[$municipality])) {
                $stats[$municipality] = 0;
            }
            $stats[$municipality]++;
        }

        return [
            'labels' => array_keys($stats),
            'data' => array_values($stats),
        ];
    }

    private function generateParkStatsByType(array $equipments): array
    {
        $stats = [];
        foreach ($equipments as $equipment) {
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
            $operator = $line['operator'];
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
        foreach ($equipments as $equipment) {
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
