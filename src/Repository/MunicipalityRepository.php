<?php

namespace App\Repository;

use App\Entity\Municipality;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Municipality>
 */
class MunicipalityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Municipality::class);
    }

    /**
     * Recherche une commune par son nom de manière flexible
     *
     * @param string $name Le nom de la commune à rechercher
     * @return Municipality|null La commune trouvée ou null
     */
    public function findByNameFlexible(string $name): ?Municipality
    {
        // D'abord, essayer une recherche exacte
        $municipality = $this->findOneBy(['name' => $name]);
        if ($municipality) {
            return $municipality;
        }

        // Ensuite, essayer une recherche insensible à la casse
        $municipality = $this->createQueryBuilder('m')
            ->where('LOWER(m.name) = LOWER(:name)')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
        if ($municipality) {
            return $municipality;
        }

        // Enfin, essayer une recherche partielle
        return $this->createQueryBuilder('m')
            ->where('LOWER(m.name) LIKE LOWER(:name)')
            ->orWhere('LOWER(:name) LIKE LOWER(CONCAT(m.name, \'%\'))')
            ->orWhere('LOWER(:name) LIKE LOWER(CONCAT(\'%\', m.name))')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Municipality[] Returns an array of Municipality objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Municipality
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
