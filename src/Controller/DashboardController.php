<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    // Suppression du constructeur et de l'import PhoneLineRepository
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

        // Récupération des municipalités
        $municipalities = array_map(function($line) use ($lines) {
            return [
                'id' => array_search($line['municipality'], array_column($lines, 'municipality')),
                'nom' => $line['municipality']
            ];
        }, $lines);
        $municipalities = array_map("unserialize", array_unique(array_map("serialize", $municipalities)));

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

        // Récupération des municipalités
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
        ]);
    }

    // Autres méthodes du contrôleur restent inchangées
    #[Route('/agents', name: 'agents')]
    public function agents(): Response
    {
        return $this->render('pages/agents.html.twig', [
            'page_title' => "Tableau de bord"
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
        return $this->render('pages/park.html.twig', [
            'page_title' => "Tableau de bord"
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
    #[Route('/map', name: 'settings')]
    public function settings(): Response
    {
        return $this->render('pages/map.html.twig', [
            'page_title' => "Tableau de bord"
        ]);
    }
}
