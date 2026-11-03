<?php

namespace App\Twig;

use App\Entity\PeriodeAcademique;
use App\Service\PeriodeAcademiqueService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(private PeriodeAcademiqueService $periodeService) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('periode_active', [$this, 'getPeriodeActive']),
            new TwigFunction('annees_scolaires', [$this, 'getAnneesScolaires']),
            new TwigFunction('annee_scolaire_courante', [$this, 'getAnneeScolaireCourante']),
        ];
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
