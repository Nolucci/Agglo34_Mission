<?php

namespace App\Repository;

use App\Entity\Whitelist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Whitelist>
 */
class WhitelistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Whitelist::class);
    }

    public function save(Whitelist $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Whitelist $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function isUserWhitelisted(string $ldapUsername): bool
    {
        $result = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->where('w.ldapUsername = :username')
            ->andWhere('w.isActive = :active')
            ->setParameter('username', $ldapUsername)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    public function findActiveWhitelistEntries(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}