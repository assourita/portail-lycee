<?php

namespace App\Repository;

use App\Entity\Bulletin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bulletin>
 */
class BulletinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bulletin::class);
    }

    public function save(Bulletin $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Bulletin $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Bulletin[] Returns an array of Bulletin objects
     */
    public function findByEtudiant($etudiant): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('b.anneeScolaire', 'DESC')
            ->addOrderBy('b.periode', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Bulletin[] Returns an array of Bulletin objects
     */
    public function findByFiliere($filiere): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.etudiant', 'e')
            ->andWhere('e.filiere = :filiere')
            ->setParameter('filiere', $filiere)
            ->orderBy('b.anneeScolaire', 'DESC')
            ->addOrderBy('b.periode', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
