<?php

namespace App\Repository;

use App\Entity\Archive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Archive>
 */
class ArchiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Archive::class);
    }

    /**
     * Trouve les archives en fonction des filtres appliqués
     *
     * @param array $filters Les filtres à appliquer
     * @return Archive[] Returns an array of Archive objects
     */
    public function findByFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.archivedAt', 'DESC');

        $this->applyFilters($qb, $filters);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les archives en fonction des filtres appliqués avec pagination
     *
     * @param array $filters Les filtres à appliquer
     * @param int $page Le numéro de page
     * @param int $limit Le nombre d'éléments par page
     * @return Archive[] Returns an array of Archive objects
     */
    public function findByFiltersWithPagination(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.archivedAt', 'DESC');

        $this->applyFilters($qb, $filters);

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre d'archives en fonction des filtres appliqués
     *
     * @param array $filters Les filtres à appliquer
     * @return int Le nombre d'archives
     */
    public function countByFilters(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)');

        $this->applyFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Applique les filtres à la requête
     *
     * @param \Doctrine\ORM\QueryBuilder $qb Le query builder
     * @param array $filters Les filtres à appliquer
     */
    private function applyFilters($qb, array $filters = []): void
    {
        // Filtre par type d'entité
        if (!empty($filters['entityType'])) {
            $qb->andWhere('a.entityType = :entityType')
               ->setParameter('entityType', $filters['entityType']);
        }

        // Filtre par date de début
        if (!empty($filters['startDate'])) {
            $startDate = new \DateTime($filters['startDate']);
            $startDate->setTime(0, 0, 0);
            $qb->andWhere('a.archivedAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        // Filtre par date de fin
        if (!empty($filters['endDate'])) {
            $endDate = new \DateTime($filters['endDate']);
            $endDate->setTime(23, 59, 59);
            $qb->andWhere('a.archivedAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }
    }

    /**
     * Supprime toutes les archives
     */
    public function deleteAll(): void
    {
        $this->createQueryBuilder('a')
            ->delete()
            ->getQuery()
            ->execute();
    }
}