<?php

namespace App\Entity;

use App\Repository\DevoirRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DevoirRepository::class)]
class Devoir
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $consignes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateEcheance = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column]
    private ?int $noteMax = 20;

    #[ORM\Column]
    private ?bool $accepteSoumissionsTardives = false;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'brouillon'; // 'brouillon', 'publie', 'cloture'

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $documentsJoints = null; // JSON des chemins de fichiers

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Matiere $matiere = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Enseignant $enseignant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?PeriodeAcademique $periodeAcademique = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Evaluation $evaluation = null; // Lien optionnel avec une évaluation

    #[ORM\OneToMany(targetEntity: Soumission::class, mappedBy: 'devoir', cascade: ['remove'])]
    private Collection $soumissions;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->soumissions = new ArrayCollection();
        $this->statut = 'brouillon';
        $this->accepteSoumissionsTardives = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getConsignes(): ?string
    {
        return $this->consignes;
    }

    public function setConsignes(?string $consignes): static
    {
        $this->consignes = $consignes;
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

    public function getDateEcheance(): ?\DateTimeInterface
    {
        return $this->dateEcheance;
    }

    public function setDateEcheance(\DateTimeInterface $dateEcheance): static
    {
        $this->dateEcheance = $dateEcheance;
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

    public function getNoteMax(): ?int
    {
        return $this->noteMax;
    }

    public function setNoteMax(int $noteMax): static
    {
        $this->noteMax = $noteMax;
        return $this;
    }

    public function isAccepteSoumissionsTardives(): ?bool
    {
        return $this->accepteSoumissionsTardives;
    }

    public function setAccepteSoumissionsTardives(bool $accepteSoumissionsTardives): static
    {
        $this->accepteSoumissionsTardives = $accepteSoumissionsTardives;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDocumentsJoints(): ?string
    {
        return $this->documentsJoints;
    }

    public function setDocumentsJoints(?string $documentsJoints): static
    {
        $this->documentsJoints = $documentsJoints;
        return $this;
    }

    public function getMatiere(): ?Matiere
    {
        return $this->matiere;
    }

    public function setMatiere(?Matiere $matiere): static
    {
        $this->matiere = $matiere;
        return $this;
    }

    public function getEnseignant(): ?Enseignant
    {
        return $this->enseignant;
    }

    public function setEnseignant(?Enseignant $enseignant): static
    {
        $this->enseignant = $enseignant;
        return $this;
    }

    public function getPeriodeAcademique(): ?PeriodeAcademique
    {
        return $this->periodeAcademique;
    }

    public function setPeriodeAcademique(?PeriodeAcademique $periodeAcademique): static
    {
        $this->periodeAcademique = $periodeAcademique;
        return $this;
    }

    public function getEvaluation(): ?Evaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(?Evaluation $evaluation): static
    {
        $this->evaluation = $evaluation;
        return $this;
    }

    /**
     * @return Collection<int, Soumission>
     */
    public function getSoumissions(): Collection
    {
        return $this->soumissions;
    }

    public function addSoumission(Soumission $soumission): static
    {
        if (!$this->soumissions->contains($soumission)) {
            $this->soumissions->add($soumission);
            $soumission->setDevoir($this);
        }

        return $this;
    }

    public function removeSoumission(Soumission $soumission): static
    {
        if ($this->soumissions->removeElement($soumission)) {
            if ($soumission->getDevoir() === $this) {
                $soumission->setDevoir(null);
            }
        }

        return $this;
    }

    /**
     * Vérifier si le devoir est en retard
     */
    public function isEnRetard(): bool
    {
        return $this->dateEcheance < new \DateTime();
    }

    /**
     * Obtenir le nombre de jours restants
     */
    public function getJoursRestants(): int
    {
        $now = new \DateTime();
        $diff = $now->diff($this->dateEcheance);
        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Obtenir le taux de soumission
     */
    public function getTauxSoumission(): float
    {
        if (!$this->matiere || !$this->matiere->getFiliere()) {
            return 0;
        }

        $totalEtudiants = $this->matiere->getFiliere()->getEtudiants()->count();
        if ($totalEtudiants === 0) {
            return 0;
        }

        return ($this->soumissions->count() / $totalEtudiants) * 100;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (%s) - %s',
            $this->titre ?? '',
            $this->matiere?->getNom() ?? '',
            $this->dateEcheance?->format('d/m/Y') ?? ''
        );
    }
}

