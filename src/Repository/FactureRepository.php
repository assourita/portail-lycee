<?php

namespace App\Repository;

use App\Entity\Facture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facture>
 */
class FactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }

    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getResult();
    }

    public function countByStatut(string $statut): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findFacturesEnRetard(): array
    {
        $aujourdhui = new \DateTime();

        return $this->createQueryBuilder('f')
            ->where('f.dateEcheance < :aujourdhui')
            ->andWhere('f.statut != :statut')
            ->setParameter('aujourdhui', $aujourdhui)
            ->setParameter('statut', 'paye')
            ->getQuery()
            ->getResult();
    }
}
