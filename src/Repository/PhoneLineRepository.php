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

    /**
     * Récupère toutes les lignes téléphoniques triées par commune
     * @return PhoneLine[]
     */
    public function findAllOrderedByMunicipality(int $limit = null, int $offset = null): array
    {
        return $this->createQueryBuilder('pl')
            ->leftJoin('pl.municipality', 'm')
            ->addSelect('m')
            ->orderBy('m.name', 'ASC')
            ->addOrderBy('pl.service', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avec pagination dans toutes les lignes téléphoniques
     * @param string $searchTerm Terme de recherche
     * @param int $page Page actuelle
     * @param int $limit Nombre d'éléments par page
     * @return array
     */
    public function searchWithPagination(string $searchTerm, int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('pl')
            ->leftJoin('pl.municipality', 'm')
            ->addSelect('m');

        // Recherche dans tous les champs pertinents
        $qb->where(
            $qb->expr()->orX(
                $qb->expr()->like('LOWER(pl.location)', ':searchTerm'),
                $qb->expr()->like('LOWER(pl.service)', ':searchTerm'),
                $qb->expr()->like('LOWER(pl.assignedTo)', ':searchTerm'),
                $qb->expr()->like('LOWER(pl.phoneBrand)', ':searchTerm'),
                $qb->expr()->like('LOWER(pl.model)', ':searchTerm'),
                $qb->expr()->like('LOWER(pl.operator)', ':searchTerm'),
                $qb->expr()->like('LOWER(pl.lineType)', ':searchTerm'),
                $qb->expr()->like('LOWER(pl.directLine)', ':searchTerm'),
                $qb->expr()->like('LOWER(pl.shortNumber)', ':searchTerm'),
                $qb->expr()->like('LOWER(m.name)', ':searchTerm')
            )
        )
        ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%');

        // Compter le total des résultats
        $countQb = $this->createQueryBuilder('pl_count')
            ->leftJoin('pl_count.municipality', 'm_count')
            ->where(
                $this->createQueryBuilder('pl_count')->expr()->orX(
                    $this->createQueryBuilder('pl_count')->expr()->like('LOWER(pl_count.location)', ':searchTerm'),
                    $this->createQueryBuilder('pl_count')->expr()->like('LOWER(pl_count.service)', ':searchTerm'),
                    $this->createQueryBuilder('pl_count')->expr()->like('LOWER(pl_count.assignedTo)', ':searchTerm'),
                    $this->createQueryBuilder('pl_count')->expr()->like('LOWER(pl_count.phoneBrand)', ':searchTerm'),
                    $this->createQueryBuilder('pl_count')->expr()->like('LOWER(pl_count.model)', ':searchTerm'),
                    $this->createQueryBuilder('pl_count')->expr()->like('LOWER(pl_count.operator)', ':searchTerm'),
                    $this->createQueryBuilder('pl_count')->expr()->like('LOWER(pl_count.lineType)', ':searchTerm'),
                    $this->createQueryBuilder('pl_count')->expr()->like('LOWER(pl_count.directLine)', ':searchTerm'),
                    $this->createQueryBuilder('pl_count')->expr()->like('LOWER(pl_count.shortNumber)', ':searchTerm'),
                    $this->createQueryBuilder('pl_count')->expr()->like('LOWER(m_count.name)', ':searchTerm')
                )
            )
            ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%')
            ->select('COUNT(pl_count.id)');

        $totalResults = $countQb->getQuery()->getSingleScalarResult();

        // Récupérer les résultats paginés
        $qb->orderBy('m.name', 'ASC')
            ->addOrderBy('pl.service', 'ASC');

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
