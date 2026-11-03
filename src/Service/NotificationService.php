<?php

namespace App\Service;

use App\Entity\Absence;
use App\Entity\Devoir;
use App\Entity\Facture;
use App\Entity\Soumission;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Éléments demandant une action de l'utilisateur, affichés dans la cloche du header.
 */
class NotificationService implements ResetInterface
{
    private ?array $cache = null;

    public function __construct(
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $urlGenerator,
        private PeriodeAcademiqueService $periodeService,
    ) {}

    /**
     * @return list<array{icone: string, texte: string, url: string, ton: string}>
     */
    public function pour(User $user): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $roles = $user->getRoles();
        $notifications = [];

        if (in_array('ROLE_ADMIN', $roles, true)) {
            $notifications = $this->admin();
        } elseif ($user->getEnseignant()) {
            $notifications = $this->enseignant($user);
        } elseif ($user->getEtudiant()) {
            $notifications = $this->etudiant($user);
        } elseif (in_array('ROLE_COMPTABLE', $roles, true)) {
            $notifications = $this->comptable();
        } elseif ($user->getParentEleve()) {
            $notifications = $this->parent($user);
        }

        return $this->cache = $notifications;
    }

    public function reset(): void
    {
        $this->cache = null;
    }

    private function admin(): array
    {
        $notifications = [];

        $aExaminer = (int) $this->em->createQueryBuilder()
            ->select('COUNT(a.id)')->from(Absence::class, 'a')
            ->where('a.justification IS NOT NULL')
            ->andWhere('a.justifiee = false')
            ->andWhere('a.dateValidation IS NULL')
            ->getQuery()->getSingleScalarResult();
        if ($aExaminer > 0) {
            $notifications[] = $this->item('fa-file-medical', sprintf('%d justificatif%s d\'absence à examiner', $aExaminer, $aExaminer > 1 ? 's' : ''), 'admin_absences_index', [], 'warning');
        }

        if (!$this->periodeService->getPeriodeActive()) {
            $notifications[] = $this->item('fa-calendar-xmark', 'Aucune période académique active', 'admin_periodes_academiques_index', [], 'danger');
        }

        return $notifications;
    }

    private function enseignant(User $user): array
    {
        $aCorriger = (int) $this->em->createQueryBuilder()
            ->select('COUNT(s.id)')->from(Soumission::class, 's')
            ->join('s.devoir', 'd')
            ->where('d.enseignant = :enseignant')
            ->andWhere('s.statut = :statut')
            ->setParameter('enseignant', $user->getEnseignant())
            ->setParameter('statut', 'soumis')
            ->getQuery()->getSingleScalarResult();

        return $aCorriger > 0
            ? [$this->item('fa-pen-to-square', sprintf('%d copie%s à corriger', $aCorriger, $aCorriger > 1 ? 's' : ''), 'enseignant_devoirs_index', [], 'accent')]
            : [];
    }

    private function etudiant(User $user): array
    {
        $etudiant = $user->getEtudiant();
        $notifications = [];

        if ($etudiant->getFiliere()) {
            $devoirs = $this->em->createQueryBuilder()
                ->select('d')->from(Devoir::class, 'd')
                ->join('d.matiere', 'm')
                ->where('m.filiere = :filiere')
                ->andWhere('d.statut = :publie')
                ->andWhere('d.dateEcheance BETWEEN :maintenant AND :semaine')
                ->setParameter('filiere', $etudiant->getFiliere())
                ->setParameter('publie', 'publie')
                ->setParameter('maintenant', new \DateTime())
                ->setParameter('semaine', new \DateTime('+7 days'))
                ->getQuery()->getResult();

            $aRendre = 0;
            foreach ($devoirs as $devoir) {
                $soumission = $this->em->getRepository(Soumission::class)->findOneBy(['devoir' => $devoir, 'etudiant' => $etudiant]);
                if (!$soumission || $soumission->getStatut() === 'brouillon') {
                    $aRendre++;
                }
            }
            if ($aRendre > 0) {
                $notifications[] = $this->item('fa-clock', sprintf('%d devoir%s à rendre cette semaine', $aRendre, $aRendre > 1 ? 's' : ''), 'etudiant_devoirs_index', [], 'warning');
            }
        }

        $aJustifier = $this->em->getRepository(Absence::class)->count(['etudiant' => $etudiant, 'justifiee' => false, 'justification' => null]);
        if ($aJustifier > 0) {
            $notifications[] = $this->item('fa-user-clock', sprintf('%d absence%s à justifier', $aJustifier, $aJustifier > 1 ? 's' : ''), 'etudiant_absences_index', [], 'danger');
        }

        return $notifications;
    }

    private function comptable(): array
    {
        $enRetard = (int) $this->em->createQueryBuilder()
            ->select('COUNT(f.id)')->from(Facture::class, 'f')
            ->where('f.statut = :statut')
            ->andWhere('f.dateEcheance < :aujourdhui')
            ->setParameter('statut', Facture::STATUT_EN_ATTENTE)
            ->setParameter('aujourdhui', new \DateTime('today'))
            ->getQuery()->getSingleScalarResult();

        return $enRetard > 0
            ? [$this->item('fa-triangle-exclamation', sprintf('%d facture%s en retard de paiement', $enRetard, $enRetard > 1 ? 's' : ''), 'comptable_factures', ['statut' => 'en_retard'], 'danger')]
            : [];
    }

    private function parent(User $user): array
    {
        $notifications = [];
        foreach ($user->getParentEleve()->getEnfants() as $enfant) {
            $nonJustifiees = $this->em->getRepository(Absence::class)->count(['etudiant' => $enfant, 'justifiee' => false]);
            if ($nonJustifiees > 0) {
                $notifications[] = $this->item('fa-user-clock', sprintf('%s : %d absence%s non justifiée%s', $enfant->getPrenom(), $nonJustifiees, $nonJustifiees > 1 ? 's' : '', $nonJustifiees > 1 ? 's' : ''), 'parent_enfant_absences', ['id' => $enfant->getId()], 'warning');
            }
        }

        return $notifications;
    }

    private function item(string $icone, string $texte, string $route, array $parametres, string $ton): array
    {
        return [
            'icone' => $icone,
            'texte' => $texte,
            'url' => $this->urlGenerator->generate($route, $parametres),
            'ton' => $ton,
        ];
    }
}
