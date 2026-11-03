<?php

namespace App\Repository;

use App\Entity\Paiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function findDerniersPaiements(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.etudiant', 'e')
            ->addSelect('e')
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getMontantTotalDuMois(): float
    {
        return $this->getTotalEncaisse(
            new \DateTime('first day of this month midnight'),
            new \DateTime('last day of this month 23:59:59')
        );
    }

    public function getTotalEncaisse(?\DateTimeInterface $debut = null, ?\DateTimeInterface $fin = null): float
    {
        $qb = $this->createQueryBuilder('p')
            ->select('SUM(p.montant)')
            ->where('p.statut = :statut')
            ->setParameter('statut', 'paye');
        $this->filtrerPeriode($qb, $debut, $fin);

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Paiement[]
     */
    public function findFiltres(array $filtres): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.etudiant', 'e')
            ->addSelect('e')
            ->leftJoin('p.facture', 'f')
            ->addSelect('f')
            ->orderBy('p.datePaiement', 'DESC');

        if (!empty($filtres['statut'])) {
            $qb->andWhere('p.statut = :statut')->setParameter('statut', $filtres['statut']);
        }
        if (!empty($filtres['type'])) {
            $qb->andWhere('p.type = :type')->setParameter('type', $filtres['type']);
        }
        if (!empty($filtres['recherche'])) {
            $qb->andWhere('e.nom LIKE :q OR e.prenom LIKE :q OR p.reference LIKE :q')
                ->setParameter('q', '%'.$filtres['recherche'].'%');
        }
        $this->filtrerPeriode($qb, $filtres['debut'] ?? null, $filtres['fin'] ?? null);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<string, float> libellé du type => montant encaissé
     */
    public function getTotauxParType(?\DateTimeInterface $debut = null, ?\DateTimeInterface $fin = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.type AS type, SUM(p.montant) AS total')
            ->where('p.statut = :statut')
            ->setParameter('statut', 'paye')
            ->groupBy('p.type')
            ->orderBy('total', 'DESC');
        $this->filtrerPeriode($qb, $debut, $fin);

        $totaux = [];
        foreach ($qb->getQuery()->getArrayResult() as $ligne) {
            $totaux[$ligne['type']] = (float) $ligne['total'];
        }

        return $totaux;
    }

    /**
     * @return array<string, float> mois (Y-m) => montant encaissé, du plus ancien au plus récent
     */
    public function getTotauxParMois(\DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        $mois = [];
        $curseur = \DateTime::createFromInterface($debut)->modify('first day of this month midnight');
        while ($curseur <= $fin) {
            $mois[$curseur->format('Y-m')] = 0.0;
            $curseur->modify('+1 month');
        }

        $paiements = $this->createQueryBuilder('p')
            ->select('p.datePaiement, p.montant')
            ->where('p.statut = :statut')
            ->andWhere('p.datePaiement BETWEEN :debut AND :fin')
            ->setParameter('statut', 'paye')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getArrayResult();

        foreach ($paiements as $paiement) {
            $cle = $paiement['datePaiement']->format('Y-m');
            if (isset($mois[$cle])) {
                $mois[$cle] += (float) $paiement['montant'];
            }
        }

        return $mois;
    }

    /**
     * @return array<int, float> id étudiant => total encaissé
     */
    public function getTotauxParEtudiant(): array
    {
        $resultats = $this->createQueryBuilder('p')
            ->select('IDENTITY(p.etudiant) AS etudiant, SUM(p.montant) AS total')
            ->where('p.statut = :statut')
            ->setParameter('statut', 'paye')
            ->groupBy('p.etudiant')
            ->getQuery()
            ->getArrayResult();

        return array_column(array_map(fn ($r) => ['id' => (int) $r['etudiant'], 'total' => (float) $r['total']], $resultats), 'total', 'id');
    }

    /**
     * @return string[]
     */
    public function findTypesUtilises(): array
    {
        return array_column(
            $this->createQueryBuilder('p')->select('DISTINCT p.type')->orderBy('p.type')->getQuery()->getArrayResult(),
            'type'
        );
    }

    private function filtrerPeriode(QueryBuilder $qb, ?\DateTimeInterface $debut, ?\DateTimeInterface $fin): void
    {
        if ($debut) {
            $qb->andWhere('p.datePaiement >= :debut')->setParameter('debut', $debut);
        }
        if ($fin) {
            $qb->andWhere('p.datePaiement <= :fin')->setParameter('fin', $fin);
        }
    }
}
