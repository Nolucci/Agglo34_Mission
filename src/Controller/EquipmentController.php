<?php

namespace App\Controller;

use App\Entity\Box;
use App\Entity\Log;
use App\Repository\BoxRepository;
use App\Repository\MunicipalityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/equipment')]
class EquipmentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    #[Route('/create', name: 'equipment_create', methods: ['POST'])]
    public function create(Request $request, MunicipalityRepository $municipalityRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }
        
        $equipment = new Box();
        $equipment->setName($data['type'] . ' ' . $data['brand'] . ' ' . ($data['model'] ?? ''));
        $equipment->setDescription($data['description'] ?? 'Aucune description');
        $equipment->setType($data['type'] ?? '');
        $equipment->setBrand($data['brand'] ?? '');
        $equipment->setModel($data['model'] ?? '');
        $equipment->setMunicipality($data['municipality'] ?? '');
        $equipment->setLocation($data['location'] ?? '');
        $equipment->setAssignedTo($data['assignedTo'] ?? null);
        $equipment->setIsActive($data['isActive'] ?? true);
        
        $this->entityManager->persist($equipment);
        
        // Création d'un log pour cette action
        $log = new Log();
        $log->setAction('CREATE');
        $log->setEntityType('Box');
        $log->setEntityId($equipment->getId() ?? 0); // 0 temporairement, sera mis à jour après flush
        $log->setDetails('Création d\'un équipement: ' . $equipment->getName());
        $log->setUsername($data['username'] ?? 'Système');
        $log->setCreatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
        
        // Mise à jour de l'ID de l'entité dans le log si c'était une nouvelle entité
        if ($log->getEntityId() === 0) {
            $log->setEntityId($equipment->getId());
            $this->entityManager->flush();
        }
        
        return new JsonResponse([
            'success' => true, 
            'message' => 'Équipement créé avec succès',
            'equipment' => [
                'id' => $equipment->getId(),
                'name' => $equipment->getName(),
                'type' => $equipment->getType(),
                'brand' => $equipment->getBrand(),
                'model' => $equipment->getModel(),
                'municipality' => $equipment->getMunicipality(),
                'location' => $equipment->getLocation(),
                'assignedTo' => $equipment->getAssignedTo(),
                'isActive' => $equipment->isActive(),
            ]
        ]);
    }
    
    #[Route('/update/{id}', name: 'equipment_update', methods: ['POST'])]
    public function update(int $id, Request $request, BoxRepository $boxRepository): JsonResponse
    {
        $equipment = $boxRepository->find($id);
        
        if (!$equipment) {
            return new JsonResponse(['success' => false, 'message' => 'Équipement non trouvé'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }
        
        // Sauvegarde des anciennes valeurs pour le log
        $oldValues = [
            'name' => $equipment->getName(),
            'type' => $equipment->getType(),
            'brand' => $equipment->getBrand(),
            'model' => $equipment->getModel(),
            'municipality' => $equipment->getMunicipality(),
            'location' => $equipment->getLocation(),
            'assignedTo' => $equipment->getAssignedTo(),
            'isActive' => $equipment->isActive(),
        ];
        
        // Mise à jour des valeurs
        $equipment->setName($data['type'] . ' ' . $data['brand'] . ' ' . ($data['model'] ?? ''));
        $equipment->setDescription($data['description'] ?? $equipment->getDescription());
        $equipment->setType($data['type'] ?? $equipment->getType());
        $equipment->setBrand($data['brand'] ?? $equipment->getBrand());
        $equipment->setModel($data['model'] ?? $equipment->getModel());
        $equipment->setMunicipality($data['municipality'] ?? $equipment->getMunicipality());
        $equipment->setLocation($data['location'] ?? $equipment->getLocation());
        $equipment->setAssignedTo($data['assignedTo'] ?? $equipment->getAssignedTo());
        $equipment->setIsActive(isset($data['isActive']) ? $data['isActive'] : $equipment->isActive());
        
        // Création d'un log pour cette action
        $log = new Log();
        $log->setAction('UPDATE');
        $log->setEntityType('Box');
        $log->setEntityId($equipment->getId());
        $log->setDetails('Mise à jour de l\'équipement: ' . $equipment->getName() . 
                        "\nAncienne valeur: " . json_encode($oldValues, JSON_UNESCAPED_UNICODE) . 
                        "\nNouvelle valeur: " . json_encode([
                            'name' => $equipment->getName(),
                            'type' => $equipment->getType(),
                            'brand' => $equipment->getBrand(),
                            'model' => $equipment->getModel(),
                            'municipality' => $equipment->getMunicipality(),
                            'location' => $equipment->getLocation(),
                            'assignedTo' => $equipment->getAssignedTo(),
                            'isActive' => $equipment->isActive(),
                        ], JSON_UNESCAPED_UNICODE));
        $log->setUsername($data['username'] ?? 'Système');
        $log->setCreatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true, 
            'message' => 'Équipement mis à jour avec succès',
            'equipment' => [
                'id' => $equipment->getId(),
                'name' => $equipment->getName(),
                'type' => $equipment->getType(),
                'brand' => $equipment->getBrand(),
                'model' => $equipment->getModel(),
                'municipality' => $equipment->getMunicipality(),
                'location' => $equipment->getLocation(),
                'assignedTo' => $equipment->getAssignedTo(),
                'isActive' => $equipment->isActive(),
            ]
        ]);
    }
    
    #[Route('/delete/{id}', name: 'equipment_delete', methods: ['POST'])]
    public function delete(int $id, BoxRepository $boxRepository): JsonResponse
    {
        $equipment = $boxRepository->find($id);
        
        if (!$equipment) {
            return new JsonResponse(['success' => false, 'message' => 'Équipement non trouvé'], Response::HTTP_NOT_FOUND);
        }
        
        // Sauvegarde des informations pour le log
        $equipmentInfo = [
            'id' => $equipment->getId(),
            'name' => $equipment->getName(),
            'type' => $equipment->getType(),
            'brand' => $equipment->getBrand(),
            'model' => $equipment->getModel(),
            'municipality' => $equipment->getMunicipality(),
            'location' => $equipment->getLocation(),
            'assignedTo' => $equipment->getAssignedTo(),
            'isActive' => $equipment->isActive(),
        ];
        
        // Création d'un log pour cette action
        $log = new Log();
        $log->setAction('DELETE');
        $log->setEntityType('Box');
        $log->setEntityId($equipment->getId());
        $log->setDetails('Suppression de l\'équipement: ' . $equipment->getName() . 
                        "\nValeurs: " . json_encode($equipmentInfo, JSON_UNESCAPED_UNICODE));
        $log->setUsername('Système'); // Idéalement, récupérer l'utilisateur connecté
        $log->setCreatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($log);
        
        // Suppression de l'équipement
        $this->entityManager->remove($equipment);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true, 
            'message' => 'Équipement supprimé avec succès'
        ]);
    }
    
    #[Route('/list', name: 'equipment_list', methods: ['GET'])]
    public function list(BoxRepository $boxRepository): JsonResponse
    {
        $equipments = $boxRepository->findAll();
        $result = [];
        
        foreach ($equipments as $equipment) {
            $result[] = [
                'id' => $equipment->getId(),
                'name' => $equipment->getName(),
                'type' => $equipment->getType(),
                'brand' => $equipment->getBrand(),
                'model' => $equipment->getModel(),
                'municipality' => $equipment->getMunicipality(),
                'location' => $equipment->getLocation(),
                'assignedTo' => $equipment->getAssignedTo(),
                'isActive' => $equipment->isActive(),
            ];
        }
        
        return new JsonResponse([
            'success' => true,
            'equipments' => $result
        ]);
    }
}