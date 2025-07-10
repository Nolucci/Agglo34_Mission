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
    public function findAllWithCommune(?int $limit = null, ?int $offset = null): array
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
    public function findAllWithCommuneOrdered(?int $limit = null, ?int $offset = null): array
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
    public function searchWithPagination(string $searchTerm, int $page = 1, int $limit = 50, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.commune', 'm')
            ->addSelect('m');

        // Appliquer le terme de recherche générique
        if (!empty($searchTerm)) {
            $qb->andWhere(
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
        }

        // Appliquer les filtres spécifiques
        $this->applyFilters($qb, $filters);

        // Compter le total des résultats
        $countQb = $this->createQueryBuilder('e_count')
            ->leftJoin('e_count.commune', 'm_count');

        if (!empty($searchTerm)) {
            $countQb->andWhere(
                $countQb->expr()->orX(
                    $countQb->expr()->like('LOWER(e_count.etiquetage)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(e_count.modele)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(e_count.numeroSerie)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(e_count.service)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(e_count.utilisateur)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(e_count.os)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(e_count.version)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(e_count.statut)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(e_count.localisation)', ':searchTerm'),
                    $countQb->expr()->like('LOWER(m_count.name)', ':searchTerm')
                )
            )
            ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%');
        }
        $this->applyFilters($countQb, $filters);

        $totalResults = $countQb->select('COUNT(e_count.id)')->getQuery()->getSingleScalarResult();

        // Récupérer les résultats paginés
        $qb->orderBy('m.name', 'ASC')
            ->addOrderBy('e.service', 'ASC');

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
        if (isset($filters['modele']) && $filters['modele'] !== '') {
            $qb->andWhere('LOWER(e.modele) LIKE :modele')
               ->setParameter('modele', '%' . strtolower($filters['modele']) . '%');
        }
        if (isset($filters['service']) && $filters['service'] !== '') {
            $qb->andWhere('LOWER(e.service) LIKE :service')
               ->setParameter('service', '%' . strtolower($filters['service']) . '%');
        }
        if (isset($filters['commune']) && $filters['commune'] !== '') {
            // Si c'est un ID numérique, filtrer par ID
            if (is_numeric($filters['commune'])) {
                $qb->andWhere('m.id = :communeId')
                   ->setParameter('communeId', $filters['commune']);
            } else {
                // Sinon, filtrer par nom (recherche partielle insensible à la casse)
                $qb->andWhere('LOWER(m.name) LIKE :communeName')
                   ->setParameter('communeName', '%' . strtolower($filters['commune']) . '%');
            }
        }
        if (isset($filters['communeName']) && $filters['communeName'] !== '') {
            $qb->andWhere('LOWER(m.name) LIKE :communeName')
               ->setParameter('communeName', '%' . strtolower($filters['communeName']) . '%');
        }
        if (isset($filters['statut']) && $filters['statut'] !== '') {
            $qb->andWhere('LOWER(e.statut) LIKE :statut')
               ->setParameter('statut', '%' . strtolower($filters['statut']) . '%');
        }
        if (isset($filters['localisation']) && $filters['localisation'] !== '') {
            $qb->andWhere('LOWER(e.localisation) LIKE :localisation')
               ->setParameter('localisation', '%' . strtolower($filters['localisation']) . '%');
        }
        if (isset($filters['etiquetage']) && $filters['etiquetage'] !== '') {
            $qb->andWhere('LOWER(e.etiquetage) LIKE :etiquetage')
               ->setParameter('etiquetage', '%' . strtolower($filters['etiquetage']) . '%');
        }
        if (isset($filters['numeroSerie']) && $filters['numeroSerie'] !== '') {
            $qb->andWhere('LOWER(e.numeroSerie) LIKE :numeroSerie')
               ->setParameter('numeroSerie', '%' . strtolower($filters['numeroSerie']) . '%');
        }
        if (isset($filters['utilisateur']) && $filters['utilisateur'] !== '') {
            $qb->andWhere('LOWER(e.utilisateur) LIKE :utilisateur')
               ->setParameter('utilisateur', '%' . strtolower($filters['utilisateur']) . '%');
        }
        if (isset($filters['os']) && $filters['os'] !== '') {
            $qb->andWhere('LOWER(e.os) LIKE :os')
               ->setParameter('os', '%' . strtolower($filters['os']) . '%');
        }
        if (isset($filters['version']) && $filters['version'] !== '') {
            $qb->andWhere('LOWER(e.version) LIKE :version')
               ->setParameter('version', '%' . strtolower($filters['version']) . '%');
        }
    }

    /**
     * Récupère les équipements filtrés avec pagination.
     * @param array $filters Critères de filtrage
     * @param int $page Page actuelle
     * @param int $limit Nombre d'éléments par page
     * @return array
     */
    public function findFilteredEquipments(array $filters, int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.commune', 'm')
            ->addSelect('m');

        $this->applyFilters($qb, $filters);

        // Compter le total des résultats
        $countQb = $this->createQueryBuilder('e_count')
            ->leftJoin('e_count.commune', 'm_count');
        $this->applyFilters($countQb, $filters);
        $totalResults = $countQb->select('COUNT(e_count.id)')->getQuery()->getSingleScalarResult();

        // Récupérer les résultats paginés
        $qb->orderBy('m.name', 'ASC')
            ->addOrderBy('e.service', 'ASC');

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