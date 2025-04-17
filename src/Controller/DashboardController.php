<?php

// src/Controller/DashboardController.php
namespace App\Controller;

use App\Repository\MunicipalityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        $this->addFlash('success', 'Connexion réussie !');
        $this->addFlash('danger', 'Erreur lors de la connexion.');

        return $this->render('index.html.twig', [
            'page_title' => "Tableau de bord",
            'lendings' => null,
            'top_countries' => null,
            'tasks' => null,
            'user' => $user = [
                'name' => 'Frederic F',
                'email' => 'fredericf@example.com',
                'image_url' => '/images/img.png',
            ],
            'municipalities' => $municipalities = [
                [
                    'id' => 1,
                    'name' => 'Paris',
                    'address' => '10 Rue de Paris, 75000 Paris',
                    'contactName' => 'Marie Dupont',
                    'contactPhone' => '0147253625',
                    'phoneLines' => [
                        [
                            'id' => 101,
                            'numero' => 33123456789,
                            'operator' => 'Orange',
                            'speed' => 500,
                            'installationDate' => '2024-01-10 08:30:00',
                            'type' => 'Fibre',
                            'monthlyFee' => 39.99,
                            'contractId' => 'CTR-00101',
                            'isActive' => true,
                        ],
                        [
                            'id' => 102,
                            'numero' => 33987654321,
                            'operator' => 'SFR',
                            'speed' => 300,
                            'installationDate' => '2023-12-15 14:00:00',
                            'type' => 'ADSL',
                            'monthlyFee' => 29.99,
                            'contractId' => 'CTR-00102',
                            'isActive' => false,
                        ],
                    ],
                ],
                [
                    'id' => 2,
                    'name' => 'Lyon',
                    'address' => '5 Place Bellecour, 69000 Lyon',
                    'contactName' => 'Jean Martin',
                    'contactPhone' => '0478787878',
                    'phoneLines' => [
                        [
                            'id' => 103,
                            'numero' => 33445566778,
                            'operator' => 'Bouygues',
                            'speed' => 400,
                            'installationDate' => '2024-02-01 10:00:00',
                            'type' => 'Fibre',
                            'monthlyFee' => 34.99,
                            'contractId' => 'CTR-00103',
                            'isActive' => true,
                        ],
                    ],
                ],
                [
                    'id' => 3,
                    'name' => 'Marseille',
                    'address' => '12 Quai du Port, 13000 Marseille',
                    'contactName' => 'Lucie Morel',
                    'contactPhone' => '0491919191',
                    'phoneLines' => [],
                ],
            ],
        ]);
    }


    #[Route('/agents', name: 'agents')]
    public function agents(): Response
    {
        return $this->render('pages/agents.html.twig', [
            'page_title' => "Tableau de bord"]);
    }

    #[Route('/account', name: 'account')]
    public function account(): Response
    {
        return $this->render('pages/account.html.twig', [
            'page_title' => "Tableau de bord"]);
    }

    #[Route('/login', name: 'login')]
    public function login(): Response
    {
        return $this->render('pages/login.html.twig', [
            'page_title' => "Tableau de bord"]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): Response
    {
        return $this->render('pages/logout.html.twig', [
            'page_title' => "Tableau de bord"]);
    }

    #[Route('/park', name: 'park')]
    public function park(): Response
    {
        return $this->render('pages/park.html.twig', [
            'page_title' => "Tableau de bord"]);
    }

    #[Route('/calendar', name: 'calendar')]
    public function calendar(): Response
    {
        return $this->render('pages/calendar.html.twig', [
            'page_title' => "Tableau de bord"]);
    }

    #[Route('/documents', name: 'documents')]
    public function documents(): Response
    {
        return $this->render('pages/documents.html.twig', [
            'page_title' => "Tableau de bord"]);
    }

    #[Route('/map', name: 'map')]
    public function map(): Response
    {
        return $this->render('pages/map.html.twig.twig', [
            'page_title' => "Tableau de bord"]);
    }

    #[Route('/settings', name: 'settings')]
    public function settings(): Response
    {
        return $this->render('pages/settings.html.twig', [
            'page_title' => "Tableau de bord"]);
    }

    #[Route('/lines', name: 'lines')]
    public function lines(): Response
    {
        return $this->render('pages/lines.html.twig', [
            'page_title' => "Lignes Téléphoniques",'lendings' => null,
            'top_countries' => null,
            'tasks' => null,
            'user' => $user = [
                'name' => 'Frederic F',
                'email' => 'fredericf@example.com',
                'image_url' => '/images/profile.jpg',
            ],
            'municipalities' => $municipalities = [
                [
                    'id' => 1,
                    'name' => 'Paris',
                    'phoneLines' => [
                        [
                            'id' => 101,
                            'numero' => 33123456789,
                            'operator' => 'Orange',
                            'speed' => 500,
                            'installationDate' => '2024-01-10 08:30:00',
                        ],
                        [
                            'id' => 102,
                            'numero' => 33987654321,
                            'operator' => 'SFR',
                            'speed' => 300,
                            'installationDate' => '2023-12-15 14:00:00',
                        ],
                    ],
                ],
                [
                    'id' => 2,
                    'name' => 'Lyon',
                    'phoneLines' => [
                        [
                            'id' => 103,
                            'numero' => 33445566778,
                            'operator' => 'Bouygues',
                            'speed' => 400,
                            'installationDate' => '2024-02-01 10:00:00',
                        ],
                    ],
                ],
                [
                    'id' => 2,
                    'name' => 'Lyon',
                    'phoneLines' => [
                        [
                            'id' => 103,
                            'numero' => 33445566778,
                            'operator' => 'Bouygues',
                            'speed' => 400,
                            'installationDate' => '2024-02-01 10:00:00',
                        ],
                    ],
                ],
                [
                    'id' => 2,
                    'name' => 'Lyon',
                    'phoneLines' => [
                        [
                            'id' => 103,
                            'numero' => 33445566778,
                            'operator' => 'Bouygues',
                            'speed' => 400,
                            'installationDate' => '2024-02-01 10:00:00',
                        ],
                    ],
                ],
            ],
        ]);
    }

    #[Route('/lines/municipality/{name}', name: 'lines_by_municipality', methods: ['GET'])]
    public function getLinesByMunicipality(string $name, MunicipalityRepository $MunicipalityRepository): JsonResponse
    {
        $commune = $MunicipalityRepository->findOneBy(['name' => $name]);

        if (!$commune) {
            return $this->json(['error' => 'Commune not found'], 404);
        }

        $lines = $commune->getLignes();

        $data = array_map(function($line) {
            return [
                'numero' => $line->getNumero(),
                'type' => $line->getType(),
                'debitMax' => $line->getDebitMax(),
                'operateur' => $line->getOperateur(),
                'dateInstallation' => $line->getDateInstallation()?->format('Y-m-d'),
            ];
        }, $lines->toArray());

        return $this->json([
            'commune' => $commune->getNom(),
            'lignes' => $data,
        ]);
    }

}
