<?php

namespace App\Repository;

use App\Entity\ParcoursScolaire;
use App\Entity\Etudiant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParcoursScolaire>
 */
class ParcoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParcoursScolaire::class);
    }

    /**
     * Trouver le parcours d'un étudiant
     */
    public function findByEtudiant(Etudiant $etudiant): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('p.anneeScolaire', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver le parcours d'une année spécifique
     */
    public function findByEtudiantAndAnnee(Etudiant $etudiant, string $anneeScolaire): ?ParcoursScolaire
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.etudiant = :etudiant')
            ->andWhere('p.anneeScolaire = :annee')
            ->setParameter('etudiant', $etudiant)
            ->setParameter('annee', $anneeScolaire)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

