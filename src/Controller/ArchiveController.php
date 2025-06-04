<?php

namespace App\Controller;

use App\Repository\ArchiveRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArchiveController extends AbstractController
{
    #[Route('/archives', name: 'app_archive')]
    public function index(ArchiveRepository $archiveRepository): Response
    {
        $archives = $archiveRepository->findAll();

        $user = [
            'name' => 'Frederic F',
            'email' => 'fredericf@example.com',
            'image_url' => '/images/img.png',
        ];

        return $this->render('pages/archives.html.twig', [
            'page_title' => 'Archives',
            'archives' => $archives,
            'user' => $user,
        ]);
    }
}