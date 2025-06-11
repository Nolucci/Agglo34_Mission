<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Entity\Log;
use App\Repository\EquipmentRepository;
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
    private EquipmentRepository $equipmentRepository;
    private MunicipalityRepository $municipalityRepository;

    public function __construct(EntityManagerInterface $entityManager, EquipmentRepository $equipmentRepository, MunicipalityRepository $municipalityRepository)
    {
        $this->entityManager = $entityManager;
        $this->equipmentRepository = $equipmentRepository;
        $this->municipalityRepository = $municipalityRepository;
    }

    #[Route('/create', name: 'equipment_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        $equipment = new Equipment();

        $municipality = null;
        if (isset($data['commune']) && $data['commune'] !== null) {
            $municipality = $this->municipalityRepository->find($data['commune']);
        }
        $equipment->setCommune($municipality);

        $equipment->setEtiquetage($data['etiquetage'] ?? null);
        $equipment->setModele($data['modele'] ?? null);
        $equipment->setNumeroSerie($data['numeroSerie'] ?? null);
        $equipment->setService($data['service'] ?? null);
        $equipment->setUtilisateur($data['utilisateur'] ?? null);

        if (isset($data['dateGarantie']) && $data['dateGarantie'] !== null) {
            try {
                $equipment->setDateGarantie(new \DateTimeImmutable($data['dateGarantie']));
            } catch (\Exception $e) {
                return new JsonResponse(['success' => false, 'message' => 'Format de date de garantie invalide'], Response::HTTP_BAD_REQUEST);
            }
        }

        $equipment->setOs($data['os'] ?? null);
        $equipment->setVersion($data['version'] ?? null);
        $equipment->setStatut($data['statut'] ?? null);


        $this->entityManager->persist($equipment);

        $log = new Log();
        $log->setAction('CREATE');
        $log->setEntityType('Equipment');
        $log->setEntityId($equipment->getId() ?? 0);
        $log->setDetails('Création d\'un équipement');
        $log->setUsername($data['username'] ?? 'Système');
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        if ($log->getEntityId() === 0) {
            $log->setEntityId($equipment->getId());
            $this->entityManager->flush();
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Équipement créé avec succès',
            'equipment' => [
                'id' => $equipment->getId(),
                'commune' => $equipment->getCommune() ? $equipment->getCommune()->getId() : null,
                'etiquetage' => $equipment->getEtiquetage(),
                'modele' => $equipment->getModele(),
                'numeroSerie' => $equipment->getNumeroSerie(),
                'service' => $equipment->getService(),
                'utilisateur' => $equipment->getUtilisateur(),
                'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
                'os' => $equipment->getOs(),
                'version' => $equipment->getVersion(),
                'statut' => $equipment->getStatut(),
            ]
        ]);
    }

    #[Route('/update/{id}', name: 'equipment_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $equipment = $this->equipmentRepository->find($id);

        if (!$equipment) {
            return new JsonResponse(['success' => false, 'message' => 'Équipement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        $oldValues = [
            'commune' => $equipment->getCommune() ? $equipment->getCommune()->getId() : null,
            'etiquetage' => $equipment->getEtiquetage(),
            'modele' => $equipment->getModele(),
            'numeroSerie' => $equipment->getNumeroSerie(),
            'service' => $equipment->getService(),
            'utilisateur' => $equipment->getUtilisateur(),
            'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
            'os' => $equipment->getOs(),
            'version' => $equipment->getVersion(),
            'statut' => $equipment->getStatut(),
        ];

        $municipality = null;
        if (isset($data['commune']) && $data['commune'] !== null) {
            $municipality = $this->municipalityRepository->find($data['commune']);
        }
        $equipment->setCommune($municipality ?? $equipment->getCommune());

        $equipment->setEtiquetage($data['etiquetage'] ?? $equipment->getEtiquetage());
        $equipment->setModele($data['modele'] ?? $equipment->getModele());
        $equipment->setNumeroSerie($data['numeroSerie'] ?? $equipment->getNumeroSerie());
        $equipment->setService($data['service'] ?? $equipment->getService());
        $equipment->setUtilisateur($data['utilisateur'] ?? $equipment->getUtilisateur());

        if (isset($data['dateGarantie'])) {
             if ($data['dateGarantie'] !== null) {
                try {
                    $equipment->setDateGarantie(new \DateTimeImmutable($data['dateGarantie']));
                } catch (\Exception $e) {
                    return new JsonResponse(['success' => false, 'message' => 'Format de date de garantie invalide'], Response::HTTP_BAD_REQUEST);
                }
            } else {
                $equipment->setDateGarantie(null);
            }
        }

        $equipment->setOs($data['os'] ?? $equipment->getOs());
        $equipment->setVersion($data['version'] ?? $equipment->getVersion());
        $equipment->setStatut($data['statut'] ?? $equipment->getStatut());

        $this->entityManager->flush();

        $log = new Log();
        $log->setAction('UPDATE');
        $log->setEntityType('Equipment');
        $log->setEntityId($equipment->getId());
        $log->setDetails('Mise à jour de l\'équipement: ' . $equipment->getId() .
                        "\nAncienne valeur: " . json_encode($oldValues, JSON_UNESCAPED_UNICODE) .
                        "\nNouvelle valeur: " . json_encode([
                            'commune' => $equipment->getCommune() ? $equipment->getCommune()->getId() : null,
                            'etiquetage' => $equipment->getEtiquetage(),
                            'modele' => $equipment->getModele(),
                            'numeroSerie' => $equipment->getNumeroSerie(),
                            'service' => $equipment->getService(),
                            'utilisateur' => $equipment->getUtilisateur(),
                            'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
                            'os' => $equipment->getOs(),
                            'version' => $equipment->getVersion(),
                            'statut' => $equipment->getStatut(),
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
                'commune' => $equipment->getCommune() ? $equipment->getCommune()->getId() : null,
                'etiquetage' => $equipment->getEtiquetage(),
                'modele' => $equipment->getModele(),
                'numeroSerie' => $equipment->getNumeroSerie(),
                'service' => $equipment->getService(),
                'utilisateur' => $equipment->getUtilisateur(),
                'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
                'os' => $equipment->getOs(),
                'version' => $equipment->getVersion(),
                'statut' => $equipment->getStatut(),
            ]
        ]);
    }

    #[Route('/delete/{id}', name: 'equipment_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $equipment = $this->equipmentRepository->find($id);

        if (!$equipment) {
            return new JsonResponse(['success' => false, 'message' => 'Équipement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $equipmentInfo = [
            'id' => $equipment->getId(),
            'commune' => $equipment->getCommune() ? $equipment->getCommune()->getId() : null,
            'etiquetage' => $equipment->getEtiquetage(),
            'modele' => $equipment->getModele(),
            'numeroSerie' => $equipment->getNumeroSerie(),
            'service' => $equipment->getService(),
            'utilisateur' => $equipment->getUtilisateur(),
            'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
            'os' => $equipment->getOs(),
            'version' => $equipment->getVersion(),
            'statut' => $equipment->getStatut(),
        ];

        $log = new Log();
        $log->setAction('DELETE');
        $log->setEntityType('Equipment');
        $log->setEntityId($equipment->getId());
        $log->setDetails('Suppression de l\'équipement: ' . $equipment->getId() .
                        "\nValeurs: " . json_encode($equipmentInfo, JSON_UNESCAPED_UNICODE));
        $log->setUsername($this->getUser() ? $this->getUser()->getUsername() : 'Système');
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($log);

        $archive = new \App\Entity\Archive();
        $archive->setEntityType('Equipment');
        $archive->setEntityId($equipment->getId());
        $archive->setArchivedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
        $archive->setData($equipmentInfo);

        $this->entityManager->persist($archive);

        $this->entityManager->remove($equipment);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Équipement supprimé avec succès'
        ]);
    }

    #[Route('/list', name: 'equipment_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $equipments = $this->equipmentRepository->findAll();
        $result = [];

        foreach ($equipments as $equipment) {
            $result[] = [
                'id' => $equipment->getId(),
                'commune' => $equipment->getCommune() ? $equipment->getCommune()->getId() : null,
                'etiquetage' => $equipment->getEtiquetage(),
                'modele' => $equipment->getModele(),
                'numeroSerie' => $equipment->getNumeroSerie(),
                'service' => $equipment->getService(),
                'utilisateur' => $equipment->getUtilisateur(),
                'dateGarantie' => $equipment->getDateGarantie() ? $equipment->getDateGarantie()->format('Y-m-d') : null,
                'os' => $equipment->getOs(),
                'version' => $equipment->getVersion(),
                'statut' => $equipment->getStatut(),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'equipments' => $result
        ]);
    }
}