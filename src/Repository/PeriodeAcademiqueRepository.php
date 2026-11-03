<?php

namespace App\Repository;

use App\Entity\PeriodeAcademique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PeriodeAcademique>
 */
class PeriodeAcademiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeriodeAcademique::class);
    }

    /**
     * Trouver la période académique active
     */
    public function findActive(): ?PeriodeAcademique
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.dateCreation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouver toutes les périodes d'une année scolaire
     */
    public function findByAnneeScolaire(string $anneeScolaire): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.anneeScolaire = :annee')
            ->setParameter('annee', $anneeScolaire)
            ->orderBy('p.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver la période en cours (active et dans les dates)
     */
    public function findEnCours(): ?PeriodeAcademique
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.dateDebut <= :now')
            ->andWhere('p.dateFin >= :now')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->orderBy('p.dateCreation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Désactiver toutes les périodes actives
     */
    public function desactiverToutes(): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.isActive', ':active')
            ->setParameter('active', false)
            ->where('p.isActive = :oldActive')
            ->setParameter('oldActive', true)
            ->getQuery()
            ->execute();
    }

    /**
     * Obtenir les années scolaires disponibles
     */
    public function findAnneesScolaires(): array
    {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p.anneeScolaire')
            ->orderBy('p.anneeScolaire', 'DESC')
            ->getQuery()
            ->getSingleColumnResult();
    }
}
