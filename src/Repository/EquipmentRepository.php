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
}