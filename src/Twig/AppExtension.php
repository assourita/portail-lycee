<?php

namespace App\Twig;

use App\Entity\PeriodeAcademique;
use App\Entity\User;
use App\Service\NotificationService;
use App\Service\PeriodeAcademiqueService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private PeriodeAcademiqueService $periodeService,
        private NotificationService $notificationService,
        private Security $security,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('notifications', [$this, 'getNotifications']),
            new TwigFunction('mois_court', [$this, 'moisCourt']),
            new TwigFunction('profil_utilisateur', [$this, 'getProfilUtilisateur']),
            new TwigFunction('periode_active', [$this, 'getPeriodeActive']),
            new TwigFunction('annees_scolaires', [$this, 'getAnneesScolaires']),
            new TwigFunction('annee_scolaire_courante', [$this, 'getAnneeScolaireCourante']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('depuis', [$this, 'depuis']),
        ];
    }

    public function depuis(?\DateTimeInterface $date): string
    {
        if (!$date) {
            return 'jamais';
        }

        $secondes = time() - $date->getTimestamp();

        return match (true) {
            $secondes < 0 => 'le '.$date->format('d/m/Y'),
            $secondes < 60 => 'à l\'instant',
            $secondes < 3600 => sprintf('il y a %d min', intdiv($secondes, 60)),
            $secondes < 86400 => sprintf('il y a %d h', intdiv($secondes, 3600)),
            $secondes < 172800 => 'hier',
            $secondes < 604800 => sprintf('il y a %d jours', intdiv($secondes, 86400)),
            default => 'le '.$date->format('d/m/Y'),
        };
    }

    public function moisCourt(?\DateTimeInterface $date): string
    {
        $mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

        return $date ? $mois[(int) $date->format('n') - 1] : '';
    }

    public function getNotifications(): array
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $this->notificationService->pour($user) : [];
    }

    /**
     * Espace, libellé de rôle, nom affiché et initiales de l'utilisateur connecté.
     *
     * @return array{espace: string, role: string, nom: string, initiales: string}|null
     */
    public function getProfilUtilisateur(): ?array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        [$espace, $role, $personne] = match (true) {
            $this->security->isGranted('ROLE_ADMIN') => ['admin', 'Administration', $user->getAdmin()],
            $this->security->isGranted('ROLE_ENSEIGNANT') => ['enseignant', 'Enseignant', $user->getEnseignant()],
            $this->security->isGranted('ROLE_ETUDIANT') => ['etudiant', 'Élève', $user->getEtudiant()],
            $this->security->isGranted('ROLE_COMPTABLE') => ['comptable', 'Comptabilité', $user->getComptable()],
            $this->security->isGranted('ROLE_PARENT') => ['parent', 'Parent', $user->getParentEleve()],
            default => ['', 'Utilisateur', null],
        };

        $nom = $personne ? trim($personne->getPrenom().' '.$personne->getNom()) : $user->getUsername();
        $mots = preg_split('/[\s.@_-]+/', $nom ?: $user->getUsername(), -1, PREG_SPLIT_NO_EMPTY);
        $initiales = mb_strtoupper(mb_substr($mots[0] ?? '?', 0, 1).mb_substr($mots[1] ?? '', 0, 1));

        return ['espace' => $espace, 'role' => $role, 'nom' => $nom, 'initiales' => $initiales];
    }

    public function getPeriodeActive(): ?PeriodeAcademique
    {
        return $this->periodeService->getPeriodeActive();
    }

    /**
     * Années scolaires proposées dans les formulaires : les trois précédentes, la courante et la suivante,
     * complétées par celles déjà enregistrées en base.
     *
     * @return string[] du plus récent au plus ancien
     */
    public function getAnneesScolaires(): array
    {
        $debutCourante = (int) substr($this->getAnneeScolaireCourante(), 0, 4);

        $annees = [];
        for ($annee = $debutCourante + 1; $annee >= $debutCourante - 3; $annee--) {
            $annees[] = sprintf('%d-%d', $annee, $annee + 1);
        }

        $annees = array_unique(array_merge($annees, $this->periodeService->getAnneesScolaires()));
        rsort($annees);

        return $annees;
    }

    /**
     * L'année scolaire commence en septembre.
     */
    public function getAnneeScolaireCourante(): string
    {
        if ($active = $this->periodeService->getAnneeScolaireActive()) {
            return $active;
        }

        $annee = (int) date('Y');
        $debut = (int) date('n') >= 9 ? $annee : $annee - 1;

        return sprintf('%d-%d', $debut, $debut + 1);
    }
}
