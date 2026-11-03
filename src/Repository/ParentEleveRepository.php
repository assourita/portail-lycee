<?php

namespace App\Repository;

use App\Entity\ParentEleve;
use App\Entity\Etudiant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParentEleve>
 */
class ParentEleveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParentEleve::class);
    }

    /**
     * Trouver les parents d'un étudiant
     */
    public function findByEtudiant(Etudiant $etudiant): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.enfants', 'e')
            ->andWhere('e = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver un parent par email
     */
    public function findByEmail(string $email): ?ParentEleve
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouver les parents sans compte utilisateur
     */
    public function findSansCompte(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user IS NULL')
            ->getQuery()
            ->getResult();
    }
}

