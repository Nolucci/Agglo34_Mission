<?php

namespace App\Repository;

use App\Entity\Log;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Log>
 *
 * @method Log|null find($id, $lockMode = null, $lockVersion = null)
 * @method Log|null findOneBy(array $criteria, array $orderBy = null)
 * @method Log[]    findAll()
 * @method Log[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    /**
     * Trouve les logs pour une entité spécifique
     */
    public function findByEntity(string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.entityType = :entityType')
            ->andWhere('l.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les logs récents
     */
    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les logs par type d'action
     */
    public function findByAction(string $action): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.action = :action')
            ->setParameter('action', $action)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les logs par utilisateur
     */
    public function findByUsername(string $username): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.username = :username')
            ->setParameter('username', $username)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les logs en fonction des filtres appliqués
     *
     * @param array $filters Les filtres à appliquer
     * @return Log[] Returns an array of Log objects
     */
    public function findByFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC');

        $this->applyFilters($qb, $filters);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les logs en fonction des filtres appliqués avec pagination
     *
     * @param array $filters Les filtres à appliquer
     * @param int $page Le numéro de page
     * @param int $limit Le nombre d'éléments par page
     * @return Log[] Returns an array of Log objects
     */
    public function findByFiltersWithPagination(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC');

        $this->applyFilters($qb, $filters);

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de logs en fonction des filtres appliqués
     *
     * @param array $filters Les filtres à appliquer
     * @return int Le nombre de logs
     */
    public function countByFilters(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)');

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
            $qb->andWhere('l.entityType = :entityType')
               ->setParameter('entityType', $filters['entityType']);
        }

        // Filtre par action
        if (!empty($filters['action'])) {
            $qb->andWhere('l.action = :action')
               ->setParameter('action', $filters['action']);
        }

        // Filtre par utilisateur
        if (!empty($filters['username'])) {
            $qb->andWhere('l.username = :username')
               ->setParameter('username', $filters['username']);
        }

        // Filtre par date de début
        if (!empty($filters['startDate'])) {
            $startDate = new \DateTime($filters['startDate']);
            $startDate->setTime(0, 0, 0);
            $qb->andWhere('l.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        // Filtre par date de fin
        if (!empty($filters['endDate'])) {
            $endDate = new \DateTime($filters['endDate']);
            $endDate->setTime(23, 59, 59);
            $qb->andWhere('l.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }
    }

    /**
     * Supprime tous les logs
     */
    public function deleteAll(): void
    {
        $this->createQueryBuilder('l')
            ->delete()
            ->getQuery()
            ->execute();
    }
}