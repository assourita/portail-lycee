<?php

namespace App\Service;

use App\Entity\PeriodeAcademique;
use App\Repository\PeriodeAcademiqueRepository;
use Doctrine\ORM\EntityManagerInterface;

class PeriodeAcademiqueService
{
    private ?PeriodeAcademique $periodeActive = null;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private PeriodeAcademiqueRepository $periodeAcademiqueRepository
    ) {}

    /**
     * Obtenir la période académique active
     */
    public function getPeriodeActive(): ?PeriodeAcademique
    {
        if ($this->periodeActive === null) {
            $this->periodeActive = $this->periodeAcademiqueRepository->findActive();
        }
        
        return $this->periodeActive;
    }

    /**
     * Obtenir la période académique en cours (active et dans les dates)
     */
    public function getPeriodeEnCours(): ?PeriodeAcademique
    {
        return $this->periodeAcademiqueRepository->findEnCours();
    }

    /**
     * Vérifier si une période est active
     */
    public function hasPeriodeActive(): bool
    {
        return $this->getPeriodeActive() !== null;
    }

    /**
     * Obtenir l'année scolaire active
     */
    public function getAnneeScolaireActive(): ?string
    {
        $periode = $this->getPeriodeActive();
        return $periode ? $periode->getAnneeScolaire() : null;
    }

    /**
     * Obtenir le nom de la période active
     */
    public function getNomPeriodeActive(): ?string
    {
        $periode = $this->getPeriodeActive();
        return $periode ? $periode->getPeriode() : null;
    }

    /**
     * Forcer le rechargement de la période active (utile après modification)
     */
    public function refreshPeriodeActive(): void
    {
        $this->periodeActive = null;
    }

    /**
     * Obtenir toutes les périodes d'une année scolaire
     */
    public function getPeriodesByAnnee(string $anneeScolaire): array
    {
        return $this->periodeAcademiqueRepository->findByAnneeScolaire($anneeScolaire);
    }

    /**
     * Obtenir les années scolaires disponibles
     */
    public function getAnneesScolaires(): array
    {
        return $this->periodeAcademiqueRepository->findAnneesScolaires();
    }

    /**
     * Vérifier si une période est en cours (entre les dates)
     */
    public function isPeriodeEnCours(PeriodeAcademique $periode): bool
    {
        return $periode->isEnCours();
    }

    /**
     * Obtenir le statut d'une période
     */
    public function getStatutPeriode(PeriodeAcademique $periode): string
    {
        return $periode->getStatut();
    }
}
