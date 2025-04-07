<?php

// src/Controller/DashboardController.php
namespace App\Controller;

use App\Repository\TownRepository;
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
                'name' => 'John Doe',
                'email' => 'johndoe@example.com',
                'image_url' => '/images/profile.jpg',
            ],
            'towns' => $towns = [
                [
                    'id' => 1,
                    'name' => 'Paris',
                    'telephoneLines' => [
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
                    'telephoneLines' => [
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
                    'id' => 3,
                    'name' => 'Marseille',
                    'telephoneLines' => [],
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
                'name' => 'John Doe',
                'email' => 'johndoe@example.com',
                'image_url' => '/images/profile.jpg',
            ],
            'towns' => $towns = [
                [
                    'id' => 1,
                    'name' => 'Paris',
                    'telephoneLines' => [
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
                    'telephoneLines' => [
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
                    'telephoneLines' => [
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
                    'telephoneLines' => [
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

    #[Route('/lines/town/{name}', name: 'lines_by_town', methods: ['GET'])]
    public function getLinesByTown(string $name, TownRepository $TownRepository): JsonResponse
    {
        $commune = $TownRepository->findOneBy(['name' => $name]);

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
