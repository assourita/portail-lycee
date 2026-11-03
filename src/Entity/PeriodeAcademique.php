<?php

namespace App\Entity;

use App\Repository\PeriodeAcademiqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PeriodeAcademiqueRepository::class)]
class PeriodeAcademique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $anneeScolaire = null; // "2024-2025"

    #[ORM\Column(length: 50)]
    private ?string $periode = null; // "1er Trimestre", "2ème Trimestre", etc.

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column]
    private bool $isActive = false; // Une seule période peut être active à la fois

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'periodeAcademique')]
    private Collection $notes;

    #[ORM\OneToMany(targetEntity: Cours::class, mappedBy: 'periodeAcademique')]
    private Collection $cours;

    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'periodeAcademique')]
    private Collection $evaluations;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->cours = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnneeScolaire(): ?string
    {
        return $this->anneeScolaire;
    }

    public function setAnneeScolaire(string $anneeScolaire): static
    {
        $this->anneeScolaire = $anneeScolaire;
        return $this;
    }

    public function getPeriode(): ?string
    {
        return $this->periode;
    }

    public function setPeriode(string $periode): static
    {
        $this->periode = $periode;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setPeriodeAcademique($this);
        }
        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getPeriodeAcademique() === $this) {
                $note->setPeriodeAcademique(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Cours>
     */
    public function getCours(): Collection
    {
        return $this->cours;
    }

    public function addCours(Cours $cours): static
    {
        if (!$this->cours->contains($cours)) {
            $this->cours->add($cours);
            $cours->setPeriodeAcademique($this);
        }
        return $this;
    }

    public function removeCours(Cours $cours): static
    {
        if ($this->cours->removeElement($cours)) {
            if ($cours->getPeriodeAcademique() === $this) {
                $cours->setPeriodeAcademique(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Evaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setPeriodeAcademique($this);
        }
        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            if ($evaluation->getPeriodeAcademique() === $this) {
                $evaluation->setPeriodeAcademique(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->anneeScolaire, $this->periode);
    }

    /**
     * Vérifier si la période est en cours (entre dateDebut et dateFin)
     */
    public function isEnCours(): bool
    {
        $now = new \DateTime();
        return $now >= $this->dateDebut && $now <= $this->dateFin;
    }

    /**
     * Obtenir le statut de la période
     */
    public function getStatut(): string
    {
        if (!$this->isActive) {
            return 'Inactive';
        }
        
        if ($this->isEnCours()) {
            return 'En cours';
        }
        
        $now = new \DateTime();
        if ($now < $this->dateDebut) {
            return 'À venir';
        }
        
        return 'Terminée';
    }
}
