<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LogController extends AbstractController
{
    #[Route('/logs', name: 'app_logs')]
    public function index(LogRepository $logRepository): Response
    {
        $logs = $logRepository->findAll();

        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('log/index.html.twig', [
            'page_title' => 'Logs de l\'Application',
            'logs' => $logs,
            'user' => $user,
        ]);
    }
}