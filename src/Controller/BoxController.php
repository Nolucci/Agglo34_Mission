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
    public function list(MunicipalityRepository $municipalityRepository, PhoneLineRepository $phoneLineRepository): Response
    {
        // Récupérer les données nécessaires des dépôts
        // La structure exacte des données de "boîtes" n'est pas claire à partir des entités existantes.
        // Il est probable qu'il faille combiner des informations de Municipality et PhoneLine.
        // Pour l'instant, je vais récupérer toutes les communes et lignes téléphoniques
        // et laisser un placeholder pour la logique de construction des données de boîtes.

        $municipalities = $municipalityRepository->findAll();
        $phoneLines = $phoneLineRepository->findAll();

        // TODO: Implémenter la logique pour construire le tableau $boxes
        // en utilisant les données de $municipalities et $phoneLines.
        // Le format attendu est un tableau de tableaux avec les clés :
        // 'commune', 'localisation', 'adresse', 'ligne_support', 'type'.
        $boxes = []; // Placeholder

        return $this->render('pages/box_list.html.twig', [
            'boxes' => $boxes,
            'page_title' => 'Liste des boîtes',
        ]);
    }
}