<?php

namespace App\Repository;

use App\Entity\Devoir;
use App\Entity\Enseignant;
use App\Entity\Etudiant;
use App\Entity\PeriodeAcademique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Devoir>
 */
class DevoirRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Devoir::class);
    }

    /**
     * Trouver les devoirs d'un enseignant
     */
    public function findByEnseignant(Enseignant $enseignant, ?PeriodeAcademique $periode = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.enseignant = :enseignant')
            ->setParameter('enseignant', $enseignant)
            ->orderBy('d.dateEcheance', 'DESC');

        if ($periode) {
            $qb->andWhere('d.periodeAcademique = :periode')
               ->setParameter('periode', $periode);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouver les devoirs pour un étudiant (via sa filière)
     */
    public function findByEtudiant(Etudiant $etudiant, ?PeriodeAcademique $periode = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->join('d.matiere', 'm')
            ->andWhere('m.filiere = :filiere')
            ->andWhere('d.statut = :statut')
            ->setParameter('filiere', $etudiant->getFiliere())
            ->setParameter('statut', 'publie')
            ->orderBy('d.dateEcheance', 'ASC');

        if ($periode) {
            $qb->andWhere('d.periodeAcademique = :periode')
               ->setParameter('periode', $periode);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouver les devoirs à venir (non échéance dépassée)
     */
    public function findAVenir(?PeriodeAcademique $periode = null): array
    {
        $now = new \DateTime();
        
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.dateEcheance > :now')
            ->andWhere('d.statut = :statut')
            ->setParameter('now', $now)
            ->setParameter('statut', 'publie')
            ->orderBy('d.dateEcheance', 'ASC');

        if ($periode) {
            $qb->andWhere('d.periodeAcademique = :periode')
               ->setParameter('periode', $periode);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouver les devoirs en retard (échéance dépassée)
     */
    public function findEnRetard(?PeriodeAcademique $periode = null): array
    {
        $now = new \DateTime();
        
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.dateEcheance < :now')
            ->andWhere('d.statut = :statut')
            ->setParameter('now', $now)
            ->setParameter('statut', 'publie')
            ->orderBy('d.dateEcheance', 'DESC');

        if ($periode) {
            $qb->andWhere('d.periodeAcademique = :periode')
               ->setParameter('periode', $periode);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouver les devoirs par matière
     */
    public function findByMatiere($matiere, ?PeriodeAcademique $periode = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.matiere = :matiere')
            ->setParameter('matiere', $matiere)
            ->orderBy('d.dateEcheance', 'DESC');

        if ($periode) {
            $qb->andWhere('d.periodeAcademique = :periode')
               ->setParameter('periode', $periode);
        }

        return $qb->getQuery()->getResult();
    }
}

