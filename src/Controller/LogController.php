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

        return $this->render('log/index.html.twig', [
            'page_title' => 'Logs de l\'Application',
            'logs' => $logs,
        ]);
    }
}