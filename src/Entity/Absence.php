<?php

namespace App\Entity;

use App\Repository\AbsenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbsenceRepository::class)]
class Absence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null; // 'absence', 'retard'

    #[ORM\Column(length: 20)]
    private ?string $periode = null; // 'matin', 'apres-midi', 'journee', 'cours'

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column]
    private ?bool $justifiee = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $justification = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentJustificatif = null; // Chemin vers le fichier

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Etudiant $etudiant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Matiere $matiere = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Enseignant $enseignant = null; // Qui a enregistré l'absence

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $validePar = null; // Admin qui a validé la justification

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?PeriodeAcademique $periodeAcademique = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->justifiee = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
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

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(?\DateTimeInterface $heureDebut): static
    {
        $this->heureDebut = $heureDebut;
        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(?\DateTimeInterface $heureFin): static
    {
        $this->heureFin = $heureFin;
        return $this;
    }

    public function isJustifiee(): ?bool
    {
        return $this->justifiee;
    }

    public function setJustifiee(bool $justifiee): static
    {
        $this->justifiee = $justifiee;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;
        return $this;
    }

    public function getJustification(): ?string
    {
        return $this->justification;
    }

    public function setJustification(?string $justification): static
    {
        $this->justification = $justification;
        return $this;
    }

    public function getDocumentJustificatif(): ?string
    {
        return $this->documentJustificatif;
    }

    public function setDocumentJustificatif(?string $documentJustificatif): static
    {
        $this->documentJustificatif = $documentJustificatif;
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

    public function getEtudiant(): ?Etudiant
    {
        return $this->etudiant;
    }

    public function setEtudiant(?Etudiant $etudiant): static
    {
        $this->etudiant = $etudiant;
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

    public function getValidePar(): ?User
    {
        return $this->validePar;
    }

    public function setValidePar(?User $validePar): static
    {
        $this->validePar = $validePar;
        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): static
    {
        $this->dateValidation = $dateValidation;
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

    /**
     * Obtenir le statut de l'absence
     */
    public function getStatut(): string
    {
        if ($this->justifiee) {
            return 'Justifiée';
        }
        
        if ($this->justification) {
            return 'En attente de validation';
        }
        
        return 'Non justifiée';
    }

    /**
     * Obtenir la durée en heures (approximatif)
     */
    public function getDureeHeures(): float
    {
        if ($this->periode === 'journee') {
            return 8.0;
        } elseif ($this->periode === 'matin' || $this->periode === 'apres-midi') {
            return 4.0;
        } elseif ($this->heureDebut && $this->heureFin) {
            $diff = $this->heureDebut->diff($this->heureFin);
            return $diff->h + ($diff->i / 60);
        }
        
        return 1.0; // Par défaut pour un cours
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s (%s) - %s',
            $this->etudiant?->getNom() ?? '',
            $this->date?->format('d/m/Y') ?? '',
            $this->type ?? '',
            $this->getStatut()
        );
    }
}

