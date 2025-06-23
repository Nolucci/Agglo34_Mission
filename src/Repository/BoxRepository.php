<?php

namespace App\Repository;

use App\Entity\Box;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Box>
 */
class BoxRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Box::class);
    }

    public function getPaginatedBoxes(int $page, int $limit, ?string $sortField, ?string $sortOrder): array
    {
        $queryBuilder = $this->createQueryBuilder('b');

        if ($sortField && $sortOrder) {
            // Handle special case for 'commune,service' sort
            if ($sortField === 'commune,service') {
                $queryBuilder->orderBy('b.commune', $sortOrder);
                $queryBuilder->addOrderBy('b.service', $sortOrder);
            } else {
                $queryBuilder->orderBy('b.' . $sortField, $sortOrder);
            }
        }

        $queryBuilder->setFirstResult(($page - 1) * $limit)
                     ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    public function countAll(): int
    {
        return $this->createQueryBuilder('b')
            ->select('count(b.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Recherche avec pagination dans toutes les boxes
     * @param string $searchTerm Terme de recherche
     * @param int $page Page actuelle
     * @param int $limit Nombre d'éléments par page
     * @return array
     */
    public function searchWithPagination(string $searchTerm, int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.commune', 'm')
            ->addSelect('m');

        // Recherche dans tous les champs pertinents
        $qb->where(
            $qb->expr()->orX(
                $qb->expr()->like('LOWER(b.service)', ':searchTerm'),
                $qb->expr()->like('LOWER(b.adresse)', ':searchTerm'),
                $qb->expr()->like('LOWER(b.ligneSupport)', ':searchTerm'),
                $qb->expr()->like('LOWER(b.type)', ':searchTerm'),
                $qb->expr()->like('LOWER(b.statut)', ':searchTerm'),
                $qb->expr()->like('LOWER(m.name)', ':searchTerm')
            )
        )
        ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%');

        // Compter le total des résultats
        $countQb = $this->createQueryBuilder('b_count')
            ->leftJoin('b_count.commune', 'm_count')
            ->where(
                $this->createQueryBuilder('b_count')->expr()->orX(
                    $this->createQueryBuilder('b_count')->expr()->like('LOWER(b_count.service)', ':searchTerm'),
                    $this->createQueryBuilder('b_count')->expr()->like('LOWER(b_count.adresse)', ':searchTerm'),
                    $this->createQueryBuilder('b_count')->expr()->like('LOWER(b_count.ligneSupport)', ':searchTerm'),
                    $this->createQueryBuilder('b_count')->expr()->like('LOWER(b_count.type)', ':searchTerm'),
                    $this->createQueryBuilder('b_count')->expr()->like('LOWER(b_count.statut)', ':searchTerm'),
                    $this->createQueryBuilder('b_count')->expr()->like('LOWER(m_count.name)', ':searchTerm')
                )
            )
            ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%')
            ->select('COUNT(b_count.id)');

        $totalResults = $countQb->getQuery()->getSingleScalarResult();

        // Récupérer les résultats paginés
        $qb->orderBy('m.name', 'ASC')
            ->addOrderBy('b.service', 'ASC');

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