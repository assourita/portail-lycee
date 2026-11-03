<?php

namespace App\Repository;

use App\Entity\Soumission;
use App\Entity\Devoir;
use App\Entity\Etudiant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Soumission>
 */
class SoumissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Soumission::class);
    }

    /**
     * Trouver la soumission d'un étudiant pour un devoir
     */
    public function findByEtudiantAndDevoir(Etudiant $etudiant, Devoir $devoir): ?Soumission
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.etudiant = :etudiant')
            ->andWhere('s.devoir = :devoir')
            ->setParameter('etudiant', $etudiant)
            ->setParameter('devoir', $devoir)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouver les soumissions d'un étudiant
     */
    public function findByEtudiant(Etudiant $etudiant): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('s.dateSoumission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les soumissions d'un devoir
     */
    public function findByDevoir(Devoir $devoir): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.devoir = :devoir')
            ->setParameter('devoir', $devoir)
            ->orderBy('s.dateSoumission', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les soumissions non corrigées
     */
    public function findNonCorrigees(Devoir $devoir): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.devoir = :devoir')
            ->andWhere('s.statut = :statut')
            ->setParameter('devoir', $devoir)
            ->setParameter('statut', 'soumis')
            ->orderBy('s.dateSoumission', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les soumissions en retard
     */
    public function findEnRetard(Devoir $devoir): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.devoir = :devoir')
            ->andWhere('s.dateSoumission > :dateEcheance')
            ->setParameter('devoir', $devoir)
            ->setParameter('dateEcheance', $devoir->getDateEcheance())
            ->orderBy('s.dateSoumission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter les soumissions par statut
     */
    public function countByStatut(Devoir $devoir, string $statut): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.devoir = :devoir')
            ->andWhere('s.statut = :statut')
            ->setParameter('devoir', $devoir)
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

