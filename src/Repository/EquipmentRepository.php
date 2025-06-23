<?php

namespace App\Repository;

use App\Entity\Equipment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Equipment>
 *
 * @method Equipment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Equipment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Equipment[]    findAll()
 * @method Equipment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipment::class);
    }

    public function save(Equipment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Equipment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Equipment[] Returns an array of Equipment objects with their associated Commune
     */
    public function findAllWithCommune(int $limit = null, int $offset = null): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.commune', 'm')
            ->addSelect('m')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d'équipements pour une commune donnée
     */
    public function countByMunicipality(int $municipalityId): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.commune = :municipalityId')
            ->setParameter('municipalityId', $municipalityId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les différentes versions d'équipements pour une commune donnée
     */
    public function findDistinctVersionsByMunicipality(int $municipalityId): array
    {
        return $this->createQueryBuilder('e')
            ->select('DISTINCT e.version as version')
            ->where('e.commune = :municipalityId')
            ->andWhere('e.version IS NOT NULL')
            ->setParameter('municipalityId', $municipalityId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Equipment[] Returns an array of Equipment objects with their associated Commune, ordered by commune name
     */
    public function findAllWithCommuneOrdered(int $limit = null, int $offset = null): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.commune', 'm')
            ->addSelect('m')
            ->orderBy('m.name', 'ASC')
            ->addOrderBy('e.service', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avec pagination dans tous les équipements
     * @param string $searchTerm Terme de recherche
     * @param int $page Page actuelle
     * @param int $limit Nombre d'éléments par page
     * @return array
     */
    public function searchWithPagination(string $searchTerm, int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.commune', 'm')
            ->addSelect('m');

        // Recherche dans tous les champs pertinents
        $qb->where(
            $qb->expr()->orX(
                $qb->expr()->like('LOWER(e.etiquetage)', ':searchTerm'),
                $qb->expr()->like('LOWER(e.modele)', ':searchTerm'),
                $qb->expr()->like('LOWER(e.numeroSerie)', ':searchTerm'),
                $qb->expr()->like('LOWER(e.service)', ':searchTerm'),
                $qb->expr()->like('LOWER(e.utilisateur)', ':searchTerm'),
                $qb->expr()->like('LOWER(e.os)', ':searchTerm'),
                $qb->expr()->like('LOWER(e.version)', ':searchTerm'),
                $qb->expr()->like('LOWER(e.statut)', ':searchTerm'),
                $qb->expr()->like('LOWER(e.localisation)', ':searchTerm'),
                $qb->expr()->like('LOWER(m.name)', ':searchTerm')
            )
        )
        ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%');

        // Compter le total des résultats
        $countQb = $this->createQueryBuilder('e_count')
            ->leftJoin('e_count.commune', 'm_count')
            ->where(
                $this->createQueryBuilder('e_count')->expr()->orX(
                    $this->createQueryBuilder('e_count')->expr()->like('LOWER(e_count.etiquetage)', ':searchTerm'),
                    $this->createQueryBuilder('e_count')->expr()->like('LOWER(e_count.modele)', ':searchTerm'),
                    $this->createQueryBuilder('e_count')->expr()->like('LOWER(e_count.numeroSerie)', ':searchTerm'),
                    $this->createQueryBuilder('e_count')->expr()->like('LOWER(e_count.service)', ':searchTerm'),
                    $this->createQueryBuilder('e_count')->expr()->like('LOWER(e_count.utilisateur)', ':searchTerm'),
                    $this->createQueryBuilder('e_count')->expr()->like('LOWER(e_count.os)', ':searchTerm'),
                    $this->createQueryBuilder('e_count')->expr()->like('LOWER(e_count.version)', ':searchTerm'),
                    $this->createQueryBuilder('e_count')->expr()->like('LOWER(e_count.statut)', ':searchTerm'),
                    $this->createQueryBuilder('e_count')->expr()->like('LOWER(e_count.localisation)', ':searchTerm'),
                    $this->createQueryBuilder('e_count')->expr()->like('LOWER(m_count.name)', ':searchTerm')
                )
            )
            ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%')
            ->select('COUNT(e_count.id)');

        $totalResults = $countQb->getQuery()->getSingleScalarResult();

        // Récupérer les résultats paginés
        $qb->orderBy('m.name', 'ASC')
            ->addOrderBy('e.service', 'ASC');

        // Récupérer les résultats paginés
        $results = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'data' => $results,
            'total' => $totalResults
        ];
    }
}