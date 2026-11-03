<?php

namespace App\Repository;

use App\Entity\Cours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cours>
 */
class CoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cours::class);
    }

    /**
     * @return Cours[] Returns an array of Cours objects for a specific filiere
     */
    public function findByFiliere($filiere): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.matiere', 'm')
            ->andWhere('m.filiere = :filiere')
            ->setParameter('filiere', $filiere)
            ->orderBy('c.dateAjout', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Cours[] Returns an array of Cours objects for a specific filiere and periode
     */
    public function findByFiliereAndPeriode($filiere, $periodeAcademique): array
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.matiere', 'm')
            ->andWhere('m.filiere = :filiere')
            ->setParameter('filiere', $filiere)
            ->orderBy('c.dateAjout', 'DESC');

        if ($periodeAcademique) {
            $qb->andWhere('c.periodeAcademique = :periode')
               ->setParameter('periode', $periodeAcademique);
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Cours[] Returns an array of Cours objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Cours
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
