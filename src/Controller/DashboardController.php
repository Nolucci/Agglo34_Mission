<?php

// src/Controller/DashboardController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        // Simule des données pour l'affichage
        return $this->render('index.html.twig', [
            'page_title' => "Tableau de bord",
            'lendings' => null,
            'top_countries' => null,
            'tasks' => null,
            'user' => $user = [
                'name' => 'John Doe',
                'email' => 'johndoe@example.com',
                'image_url' => '/images/profile.jpg', // Peut être null si pas d'image
            ],
        ]);
    }

    #[Route('/agents', name: 'agents')]
    public function agents(): Response
    {
        // Simule des données pour l'affichage
        return $this->render('agents.html.twig');
    }

    #[Route('/account', name: 'account')]
    public function account(): Response
    {
        // Simule des données pour l'affichage
        return $this->render('agents.html.twig');
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): Response
    {
        // Simule des données pour l'affichage
        return $this->render('agents.html.twig');
    }

    #[Route('/park', name: 'park')]
    public function park(): Response
    {
        // Simule des données pour l'affichage
        return $this->render('park.html.twig');
    }

    #[Route('/calendar', name: 'calendar')]
    public function calendar(): Response
    {
        // Simule des données pour l'affichage
        return $this->render('calendar.html.twig');
    }

    #[Route('/documents', name: 'documents')]
    public function documents(): Response
    {
        // Simule des données pour l'affichage
        return $this->render('documents.html.twig');
    }

    #[Route('/map', name: 'map')]
    public function map(): Response
    {
        // Simule des données pour l'affichage
        return $this->render('map.html.twig');
    }

    #[Route('/settings', name: 'settings')]
    public function settings(): Response
    {
        // Simule des données pour l'affichage
        return $this->render('settings.html.twig');
    }
}
