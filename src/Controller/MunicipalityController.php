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
        $municipalities = $this->municipalityRepository->findAll();
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
}