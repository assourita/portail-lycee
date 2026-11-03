<?php

namespace App\Repository;

use App\Entity\Comptable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comptable>
 *
 * @method Comptable|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comptable|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comptable[]    findAll()
 * @method Comptable[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComptableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comptable::class);
    }
}
