<?php

namespace App\Controller;

use App\Entity\Municipality;
use App\Repository\MunicipalityRepository;
use App\Repository\PhoneLineRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class BoxController extends AbstractController
{
    #[Route('/box/{id}', name: 'box_index')]
    public function index(int $id, MunicipalityRepository $municipalityRepository, PhoneLineRepository $phoneLineRepository): Response
    {
        $municipality = $municipalityRepository->find($id);

        if (!$municipality) {
            throw new NotFoundHttpException('Commune non trouvée.');
        }

        $phoneLines = $phoneLineRepository->findBy(['municipality' => $municipality]);

        $parkItems = [];

        return $this->render('pages/box.html.twig', [
            'municipality' => $municipality,
            'phoneLines' => $phoneLines,
            'parkItems' => $parkItems,
        ]);
    }
    #[Route('/box', name: 'box_list')]
    public function list(MunicipalityRepository $municipalityRepository, PhoneLineRepository $phoneLineRepository): Response
    {

        $municipalities = $municipalityRepository->findAll();
        $phoneLines = $phoneLineRepository->findAll();

        $boxes = [];

        return $this->render('pages/box_list.html.twig', [
            'boxes' => $boxes,
            'page_title' => 'Liste des boîtes',
        ]);
    }
}