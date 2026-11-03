<?php

namespace App\Repository;

use App\Entity\Evaluation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evaluation>
 */
class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

    /**
     * Trouver les évaluations d'une matière pour une période académique
     */
    public function findByMatiereAndPeriode($matiere, $periodeAcademique): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.matiere = :matiere')
            ->andWhere('e.periodeAcademique = :periode')
            ->setParameter('matiere', $matiere)
            ->setParameter('periode', $periodeAcademique)
            ->orderBy('e.dateEvaluation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les évaluations d'un enseignant pour une période
     */
    public function findByEnseignantAndPeriode($enseignant, $periodeAcademique): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.enseignant = :enseignant')
            ->andWhere('e.periodeAcademique = :periode')
            ->setParameter('enseignant', $enseignant)
            ->setParameter('periode', $periodeAcademique)
            ->orderBy('e.dateEvaluation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les évaluations d'une filière pour une période
     */
    public function findByFiliereAndPeriode($filiere, $periodeAcademique): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.matiere', 'm')
            ->andWhere('m.filiere = :filiere')
            ->andWhere('e.periodeAcademique = :periode')
            ->setParameter('filiere', $filiere)
            ->setParameter('periode', $periodeAcademique)
            ->orderBy('e.dateEvaluation', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
