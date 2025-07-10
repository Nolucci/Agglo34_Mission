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
    public function searchWithPagination(string $searchTerm, int $page = 1, int $limit = 50, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.commune', 'm')
            ->addSelect('m');

        // Appliquer le terme de recherche générique
        if (!empty($searchTerm)) {
            $qb->andWhere(
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
        }

        // Appliquer les filtres spécifiques
        $this->applyFilters($qb, $filters);

        // Compter le total des résultats
        $countQb = $this->createQueryBuilder('b_count')
            ->leftJoin('b_count.commune', 'm_count');

        if (!empty($searchTerm)) {
            $countQb->andWhere(
                $countQb->expr()->orX(
                    $countQb->expr()->like('LOWER(b_count.service)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(b_count.adresse)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(b_count.ligneSupport)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(b_count.type)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(b_count.statut)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(m_count.name)', ':searchTerm')
                )
            )
            ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%');
        }
        $this->applyFilters($countQb, $filters);

        $totalResults = $countQb->select('COUNT(b_count.id)')->getQuery()->getSingleScalarResult();

        // Récupérer les résultats paginés
        $qb->orderBy('m.name', 'ASC')
            ->addOrderBy('b.service', 'ASC');

        $results = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'data' => $results,
            'total' => $totalResults
        ];
    }

    /**
     * Applique les filtres au QueryBuilder.
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array $filters
     */
    private function applyFilters(\Doctrine\ORM\QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['municipalityName']) && $filters['municipalityName'] !== '') {
            $qb->andWhere('LOWER(m.name) LIKE :municipalityName')
               ->setParameter('municipalityName', '%' . strtolower($filters['municipalityName']) . '%');
        }
        if (isset($filters['service']) && $filters['service'] !== '') {
            $qb->andWhere('LOWER(b.service) LIKE :service')
               ->setParameter('service', '%' . strtolower($filters['service']) . '%');
        }
        if (isset($filters['type']) && $filters['type'] !== '') {
            $qb->andWhere('LOWER(b.type) LIKE :type')
               ->setParameter('type', '%' . strtolower($filters['type']) . '%');
        }
        if (isset($filters['adresse']) && $filters['adresse'] !== '') {
            $qb->andWhere('LOWER(b.adresse) LIKE :adresse')
               ->setParameter('adresse', '%' . strtolower($filters['adresse']) . '%');
        }
        if (isset($filters['ligne_support']) && $filters['ligne_support'] !== '') {
            $qb->andWhere('LOWER(b.ligneSupport) LIKE :ligneSupport')
               ->setParameter('ligneSupport', '%' . strtolower($filters['ligne_support']) . '%');
        }
        if (isset($filters['statut']) && $filters['statut'] !== '') {
            $qb->andWhere('LOWER(b.statut) LIKE :statut')
               ->setParameter('statut', '%' . strtolower($filters['statut']) . '%');
        }
    }

    /**
     * Récupère les boxs filtrées avec pagination.
     * @param array $filters Critères de filtrage
     * @param int $page Page actuelle
     * @param int $limit Nombre d'éléments par page
     * @return array
     */
    public function findFilteredBoxes(array $filters, int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.commune', 'm')
            ->addSelect('m');

        $this->applyFilters($qb, $filters);

        // Compter le total des résultats
        $countQb = $this->createQueryBuilder('b_count')
            ->leftJoin('b_count.commune', 'm_count');
        $this->applyFilters($countQb, $filters);
        $totalResults = $countQb->select('COUNT(b_count.id)')->getQuery()->getSingleScalarResult();

        // Récupérer les résultats paginés
        $qb->orderBy('m.name', 'ASC')
            ->addOrderBy('b.service', 'ASC');

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