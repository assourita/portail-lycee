<?php

namespace App\Repository;

use App\Entity\Etudiant;
use App\Entity\Facture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facture>
 */
class FactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }

    public function findByStatut(string $statut): array
    {
        return $this->findBy(['statut' => $statut], ['dateEcheance' => 'ASC']);
    }

    public function countByStatut(string $statut): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Facture[]
     */
    public function findFacturesEnRetard(): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.etudiant', 'e')
            ->addSelect('e')
            ->where('f.dateEcheance < :aujourdhui')
            ->andWhere('f.statut = :statut')
            ->setParameter('aujourdhui', new \DateTime('today'))
            ->setParameter('statut', Facture::STATUT_EN_ATTENTE)
            ->orderBy('f.dateEcheance', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Facture[]
     */
    public function findFiltres(array $filtres): array
    {
        $qb = $this->createQueryBuilder('f')
            ->join('f.etudiant', 'e')
            ->addSelect('e')
            ->orderBy('f.dateCreation', 'DESC');

        if (($filtres['statut'] ?? '') === 'en_retard') {
            $qb->andWhere('f.statut = :statut AND f.dateEcheance < :aujourdhui')
                ->setParameter('statut', Facture::STATUT_EN_ATTENTE)
                ->setParameter('aujourdhui', new \DateTime('today'));
        } elseif (!empty($filtres['statut'])) {
            $qb->andWhere('f.statut = :statut')->setParameter('statut', $filtres['statut']);
        }
        if (!empty($filtres['recherche'])) {
            $qb->andWhere('e.nom LIKE :q OR e.prenom LIKE :q OR f.libelle LIKE :q')
                ->setParameter('q', '%'.$filtres['recherche'].'%');
        }
        if (!empty($filtres['debut'])) {
            $qb->andWhere('f.dateCreation >= :debut')->setParameter('debut', $filtres['debut']);
        }
        if (!empty($filtres['fin'])) {
            $qb->andWhere('f.dateCreation <= :fin')->setParameter('fin', $filtres['fin']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Facture[] factures encore à régler pour un étudiant
     */
    public function findOuvertesPourEtudiant(Etudiant $etudiant): array
    {
        return $this->findBy(['etudiant' => $etudiant, 'statut' => Facture::STATUT_EN_ATTENTE], ['dateEcheance' => 'ASC']);
    }

    /**
     * @return array<int, float> id étudiant => total facturé (hors factures annulées)
     */
    public function getTotauxParEtudiant(): array
    {
        $resultats = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.etudiant) AS etudiant, SUM(f.montant) AS total')
            ->where('f.statut != :annule')
            ->setParameter('annule', Facture::STATUT_ANNULE)
            ->groupBy('f.etudiant')
            ->getQuery()
            ->getArrayResult();

        return array_column(array_map(fn ($r) => ['id' => (int) $r['etudiant'], 'total' => (float) $r['total']], $resultats), 'total', 'id');
    }

    public function getTotalFacture(?\DateTimeInterface $debut = null, ?\DateTimeInterface $fin = null): float
    {
        $qb = $this->createQueryBuilder('f')
            ->select('SUM(f.montant)')
            ->where('f.statut != :annule')
            ->setParameter('annule', Facture::STATUT_ANNULE);
        if ($debut) {
            $qb->andWhere('f.dateCreation >= :debut')->setParameter('debut', $debut);
        }
        if ($fin) {
            $qb->andWhere('f.dateCreation <= :fin')->setParameter('fin', $fin);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }
}
