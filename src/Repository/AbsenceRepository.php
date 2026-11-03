<?php

namespace App\Repository;

use App\Entity\Absence;
use App\Entity\Etudiant;
use App\Entity\PeriodeAcademique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Absence>
 */
class AbsenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Absence::class);
    }

    /**
     * Trouver les absences d'un étudiant
     */
    public function findByEtudiant(Etudiant $etudiant, ?PeriodeAcademique $periode = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('a.date', 'DESC');

        if ($periode) {
            $qb->andWhere('a.periodeAcademique = :periode')
               ->setParameter('periode', $periode);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compter les absences d'un étudiant
     */
    public function countByEtudiant(Etudiant $etudiant, ?PeriodeAcademique $periode = null, ?bool $justifiee = null): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant);

        if ($periode) {
            $qb->andWhere('a.periodeAcademique = :periode')
               ->setParameter('periode', $periode);
        }

        if ($justifiee !== null) {
            $qb->andWhere('a.justifiee = :justifiee')
               ->setParameter('justifiee', $justifiee);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouver les absences non justifiées
     */
    public function findNonJustifiees(?PeriodeAcademique $periode = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.justifiee = :justifiee')
            ->andWhere('a.justification IS NULL')
            ->setParameter('justifiee', false)
            ->orderBy('a.date', 'DESC');

        if ($periode) {
            $qb->andWhere('a.periodeAcademique = :periode')
               ->setParameter('periode', $periode);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouver les absences en attente de validation
     */
    public function findEnAttenteValidation(?PeriodeAcademique $periode = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.justifiee = :justifiee')
            ->andWhere('a.justification IS NOT NULL')
            ->setParameter('justifiee', false)
            ->orderBy('a.date', 'DESC');

        if ($periode) {
            $qb->andWhere('a.periodeAcademique = :periode')
               ->setParameter('periode', $periode);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Statistiques d'assiduité par filière
     */
    public function getStatistiquesByFiliere($filiere, ?PeriodeAcademique $periode = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id) as total')
            ->join('a.etudiant', 'e')
            ->andWhere('e.filiere = :filiere')
            ->setParameter('filiere', $filiere);

        if ($periode) {
            $qb->andWhere('a.periodeAcademique = :periode')
               ->setParameter('periode', $periode);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Calculer le taux d'assiduité d'un étudiant (en pourcentage)
     */
    public function calculateTauxAssiduite(Etudiant $etudiant, ?PeriodeAcademique $periode = null): float
    {
        // Nombre total de jours dans la période (approximatif)
        $joursTotal = 180; // Une année scolaire typique
        
        if ($periode) {
            $dateDebut = $periode->getDateDebut();
            $dateFin = $periode->getDateFin();
            if ($dateDebut && $dateFin) {
                $diff = $dateDebut->diff($dateFin);
                $joursTotal = $diff->days;
            }
        }

        // Compter les absences
        $absences = $this->countByEtudiant($etudiant, $periode);
        
        // Calculer le taux (100% - pourcentage d'absences)
        if ($joursTotal > 0) {
            return max(0, 100 - (($absences / $joursTotal) * 100));
        }
        
        return 100.0;
    }

    /**
     * Trouver les absences récentes (derniers 7 jours)
     */
    public function findRecentes(?PeriodeAcademique $periode = null, int $jours = 7): array
    {
        $dateDebut = new \DateTime("-{$jours} days");
        
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.date >= :dateDebut')
            ->setParameter('dateDebut', $dateDebut)
            ->orderBy('a.date', 'DESC');

        if ($periode) {
            $qb->andWhere('a.periodeAcademique = :periode')
               ->setParameter('periode', $periode);
        }

        return $qb->getQuery()->getResult();
    }
}

