<?php

namespace App\Repository;

use App\Entity\PhoneLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhoneLine>
 */
class PhoneLineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhoneLine::class);
    }

    /**
     * Récupère toutes les lignes téléphoniques avec leurs détails
     * @return PhoneLine[]
     */
    public function findAllWithDetails(): array
    {
        return $this->createQueryBuilder('pl')
            ->leftJoin('pl.municipality', 'm')
            ->addSelect('m')
            ->orderBy('pl.installationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Filtre les lignes téléphoniques
     * @param array $filters Critères de filtrage
     * @return PhoneLine[]
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('pl');

        if (isset($filters['operator'])) {
            $qb->andWhere('pl.operateur = :operator')
               ->setParameter('operator', $filters['operator']);
        }

        if (isset($filters['type'])) {
            $qb->andWhere('pl.type = :type')
               ->setParameter('type', $filters['type']);
        }

        if (isset($filters['minSpeed'])) {
            $qb->andWhere('pl.debitMax >= :minSpeed')
               ->setParameter('minSpeed', $filters['minSpeed']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Calcule des statistiques sur les lignes téléphoniques
     * @return array
     */
    public function getPhoneLineStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT 
                COUNT(*) as total_lines,
                AVG(debit_max) as avg_speed,
                COUNT(DISTINCT operateur) as unique_operators,
                SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active_lines
            FROM phone_line
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAssociative();
    }
}
