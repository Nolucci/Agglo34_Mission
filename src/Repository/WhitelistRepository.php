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

    /**
     * Vérifie si un utilisateur LDAP est dans la whitelist active
     */
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

    /**
     * Trouve une entrée whitelist par nom d'utilisateur LDAP
     */
    public function findByLdapUsername(string $ldapUsername): ?Whitelist
    {
        return $this->createQueryBuilder('w')
            ->where('w.ldapUsername = :username')
            ->setParameter('username', $ldapUsername)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les entrées actives de la whitelist
     */
    public function findActiveEntries(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}