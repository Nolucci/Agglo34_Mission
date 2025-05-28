<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArchiveController extends AbstractController
{
    #[Route('/archives', name: 'app_archive')]
    public function index(ArchiveRepository $archiveRepository): Response
    {
        $archives = $archiveRepository->findAll();

        return $this->render('pages/archives.html.twig', [
            'page_title' => 'Archives',
            'archives' => $archives,
        ]);
    }
}