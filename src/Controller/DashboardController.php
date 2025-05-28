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
    public function index(PhoneLineRepository $phoneLineRepository, MunicipalityRepository $municipalityRepository): Response
    {
        // Récupérer les lignes téléphoniques réelles
        $lines = $phoneLineRepository->findAll();

        // Calculer les statistiques des lignes téléphoniques à partir des données réelles
        $phoneLineStats = [
            'total_lines' => count($lines),
            'unique_operators' => count(array_values(array_unique(array_column($lines, 'operator')))), // Assurez-vous que l'entité PhoneLine a une propriété 'operator'
            'unique_services' => count(array_values(array_unique(array_column($lines, 'service')))), // Assurez-vous que l'entité PhoneLine a une propriété 'service'
            'global_lines' => count(array_filter($lines, fn($line) => $line->isGlobal())), // Assurez-vous que l'entité PhoneLine a une méthode isGlobal()
            'local_lines' => count(array_filter($lines, fn($line) => !$line->isGlobal())),
        ];

        // Récupérer les communes réelles
        $municipalities = $municipalityRepository->findAll();

        // TODO: Remplacer les données fictives du parc informatique par des données réelles si une entité correspondante existe.
        // Pour l'instant, laisser un placeholder.
        $equipments = []; // Placeholder

        // TODO: Calculer les statistiques du parc informatique à partir des données réelles si elles sont implémentées.
        $parkStats = [
            'total_equipments' => 0,
            'unique_services' => 0,
            'unique_municipalities' => 0,
            'active_equipments' => 0,
        ]; // Placeholder

        // TODO: Remplacer les données utilisateur statiques par les données de l'utilisateur connecté si nécessaire.
        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ]; // Placeholder ou récupérer l'utilisateur connecté

        return $this->render('index.html.twig', [
            'page_title' => "Tableau de bord",
            'user' => $user,
            'municipalities' => $municipalities,
            'lines' => $lines,
            'phoneLines' => $lines, // phoneLines semble être un alias de lines dans la vue
            'phoneLineStats' => $phoneLineStats,
            'equipments' => $equipments,
            'parkStats' => $parkStats,
        ]);
    }

    #[Route('/lines', name: 'lines')]
    public function lines(PhoneLineRepository $phoneLineRepository, MunicipalityRepository $municipalityRepository): Response
    {
        // Récupérer les lignes téléphoniques réelles
        $lines = $phoneLineRepository->findAll();

        // Calculer les statistiques des lignes téléphoniques à partir des données réelles
        $phoneLineStats = [
            'total_lines' => count($lines),
            'unique_operators' => count(array_values(array_unique(array_column($lines, 'operator')))), // Assurez-vous que l'entité PhoneLine a une propriété 'operator'
            'unique_services' => count(array_values(array_unique(array_column($lines, 'service')))), // Assurez-vous que l'entité PhoneLine a une propriété 'service'
            'global_lines' => count(array_filter($lines, fn($line) => $line->isGlobal())), // Assurez-vous que l'entité PhoneLine a une méthode isGlobal()
            'local_lines' => count(array_filter($lines, fn($line) => !$line->isGlobal())),
        ];

        // Récupérer les communes réelles
        $municipalities = $municipalityRepository->findAll();

        // TODO: Remplacer les données utilisateur statiques par les données de l'utilisateur connecté si nécessaire.
        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ]; // Placeholder ou récupérer l'utilisateur connecté

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

    // Autres méthodes du contrôleur restent inchangées
    #[Route('/agents', name: 'agents')]
    public function agents(): Response
    {
        // Récupérer les agents réels en utilisant le UserRepository injecté dans le constructeur
        $agents = $this->userRepository->findAll();

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
    public function park(MunicipalityRepository $municipalityRepository): Response
    {
        // TODO: Remplacer les données fictives du parc informatique par des données réelles si une entité correspondante existe.
        // Pour l'instant, laisser un placeholder.
        $equipments = []; // Placeholder

        // TODO: Calculer les statistiques du parc informatique à partir des données réelles si elles sont implémentées.
        $parkStats = [
            'total_equipments' => 0,
            'unique_services' => 0,
            'unique_municipalities' => 0,
            'active_equipments' => 0,
        ]; // Placeholder

        // TODO: Les méthodes generateParkStatsByType et generateParkStatsByStatus devront être adaptées
        // pour fonctionner avec des objets entité si les données réelles sont implémentées.

        return $this->render('pages/park.html.twig', [
            'page_title' => "Parc Informatique",
            'equipments' => $equipments,
            'parkStats' => $parkStats,
            'teamChartData' => $this->generateParkStatsByType($equipments), // Ces méthodes devront peut-être être adaptées
            'statusChartData' => $this->generateParkStatsByStatus($equipments), // Ces méthodes devront peut-être être adaptées
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
    public function settings(SettingsRepository $settingsRepository): Response
    {
        // Récupérer les paramètres réels en utilisant le SettingsRepository
        // Supposons qu'il n'y a qu'une seule ligne de paramètres dans la table.
        $settings = $settingsRepository->findOneBy([]); // Récupère la première (et unique) ligne

        // Si aucun paramètre n'existe, on peut passer un tableau vide ou des valeurs par défaut.
        if (!$settings) {
            $settings = []; // Ou des valeurs par défaut si approprié
        }

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
        $featureEnabled = $request->request->get('feature_enabled') === '1';

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
