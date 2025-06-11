<?php

namespace App\Repository;

use App\Entity\Archive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

        return $qb->getQuery()->getResult();
    }
}