<?php

namespace App\Controller;

use App\Entity\Municipality;
use App\Repository\MunicipalityRepository;
use App\Repository\PhoneLineRepository; // Ajout du PhoneLineRepository
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class BoxController extends AbstractController
{
    #[Route('/box/{id}', name: 'box_index')]
    // Injection de PhoneLineRepository
    public function index(int $id, MunicipalityRepository $municipalityRepository, PhoneLineRepository $phoneLineRepository): Response
    {
        $municipality = $municipalityRepository->find($id);

        if (!$municipality) {
            throw new NotFoundHttpException('Commune non trouvée.');
        }

        // Récupérer les lignes téléphoniques liées à cette commune
        $phoneLines = $phoneLineRepository->findBy(['municipality' => $municipality]);

        // Ici, nous devons récupérer les données de 'type'. La source n'est pas claire
        // Je vais demander à l'utilisateur où trouver ces informations.
        // Pour l'instant, je passe une variable vide.
        $parkItems = []; // Placeholder pour les données de 'type'

        return $this->render('pages/box.html.twig', [
            'municipality' => $municipality,
            'phoneLines' => $phoneLines,
            'parkItems' => $parkItems, // Passage du placeholder
        ]);
    }
    #[Route('/box', name: 'box_list')]
    public function list(): Response
    {
        // Generate fictitious box data
        $boxes = [
            [
                'commune' => 'Béziers',
                'localisation' => 'Centre Ville',
                'adresse' => 'Place Jean Jaurès',
                'ligne_support' => 'Fibre Optique',
                'type' => 'Armoire de Rue',
            ],
            [
                'commune' => 'Narbonne',
                'localisation' => 'Les Halles',
                'adresse' => 'Boulevard Frédéric Mistral',
                'ligne_support' => 'Cuivre',
                'type' => 'Point de Mutualisation',
            ],
            [
                'commune' => 'Agde',
                'localisation' => 'Le Cap d\'Agde',
                'adresse' => 'Avenue des Sergents',
                'ligne_support' => 'Fibre Optique',
                'type' => 'Plaque Façade',
            ],
            [
                'commune' => 'Valras-Plage',
                'localisation' => 'Front de Mer',
                'adresse' => 'Avenue des Rosses Marines',
                'ligne_support' => 'Cuivre',
                'type' => 'Armoire de Rue',
            ],
            [
                'commune' => 'Sète',
                'localisation' => 'Le Port',
                'adresse' => 'Quai d\'Alger',
                'ligne_support' => 'Fibre Optique',
                'type' => 'Armoire de Rue',
            ],
        ];

        return $this->render('pages/box_list.html.twig', [
            'boxes' => $boxes,
            'page_title' => 'Liste des boîtes',
        ]);
    }
}