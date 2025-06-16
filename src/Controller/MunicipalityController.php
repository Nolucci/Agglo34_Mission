<?php

namespace App\Controller;

use App\Repository\MunicipalityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class MunicipalityController extends AbstractController
{
    private $municipalityRepository;

    public function __construct(MunicipalityRepository $municipalityRepository)
    {
        $this->municipalityRepository = $municipalityRepository;
    }

    #[Route('/api/municipalities', name: 'municipality_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $allMunicipalities = $this->municipalityRepository->findAll();

        // Créer un tableau associatif pour éliminer les doublons par nom (insensible à la casse)
        $uniqueMunicipalities = [];
        foreach ($allMunicipalities as $municipality) {
            $lowerName = strtolower($municipality->getName());
            if (!isset($uniqueMunicipalities[$lowerName])) {
                $uniqueMunicipalities[$lowerName] = $municipality;
            }
        }

        // Convertir en tableau simple et trier par nom
        $municipalities = array_values($uniqueMunicipalities);
        usort($municipalities, function($a, $b) {
            return strcasecmp($a->getName(), $b->getName());
        });

        $data = [];
        foreach ($municipalities as $municipality) {
            $data[] = [
                'id' => $municipality->getId(),
                'name' => $municipality->getName()
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/municipalities/{id}', name: 'municipality_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $municipality = $this->municipalityRepository->find($id);

        if (!$municipality) {
            return $this->json(['error' => 'Municipalité non trouvée'], 404);
        }

        return $this->json([
            'id' => $municipality->getId(),
            'name' => $municipality->getName()
        ]);
    }

    #[Route('/api/municipalities/find-by-name/{name}', name: 'municipality_find_by_name', methods: ['GET'])]
    public function findByName(string $name): JsonResponse
    {
        $municipality = $this->municipalityRepository->findByNameFlexible($name);

        if (!$municipality) {
            return $this->json(['error' => 'Municipalité non trouvée'], 404);
        }

        return $this->json([
            'id' => $municipality->getId(),
            'name' => $municipality->getName()
        ]);
    }
}