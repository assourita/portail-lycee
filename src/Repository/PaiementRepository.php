<?php

namespace App\Repository;

use App\Entity\Paiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paiement>
 */
class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

    public function findPaiementsDuMois(): array
    {
        $debutMois = new \DateTime('first day of this month midnight');
        $finMois = new \DateTime('last day of this month 23:59:59');

        return $this->createQueryBuilder('p')
            ->where('p.datePaiement BETWEEN :debut AND :fin')
            ->setParameter('debut', $debutMois)
            ->setParameter('fin', $finMois)
            ->getQuery()
            ->getResult();
    }

    public function findDerniersPaiements(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByStatut(string $statut): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getMontantTotalDuMois(): float
    {
        $debutMois = new \DateTime('first day of this month midnight');
        $finMois = new \DateTime('last day of this month 23:59:59');

        return $this->createQueryBuilder('p')
            ->select('SUM(p.montant)')
            ->where('p.datePaiement BETWEEN :debut AND :fin')
            ->andWhere('p.statut = :statut')
            ->setParameter('debut', $debutMois)
            ->setParameter('fin', $finMois)
            ->setParameter('statut', 'paye')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }
}
