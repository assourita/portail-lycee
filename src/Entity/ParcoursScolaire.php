<?php

namespace App\Entity;

use App\Repository\ParcoursRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParcoursRepository::class)]
class ParcoursScolaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $anneeScolaire = null;

    #[ORM\Column(length: 50)]
    private ?string $niveau = null; // '10ème', '11ème', 'Terminale'

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $moyenneAnnuelle = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $moyenneTrimestre1 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $moyenneTrimestre2 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $moyenneTrimestre3 = null;

    #[ORM\Column(nullable: true)]
    private ?int $rangClasse = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalEtudiants = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalAbsences = null;

    #[ORM\Column(nullable: true)]
    private ?int $absencesJustifiees = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $tauxAssiduite = null;

    #[ORM\Column(length: 50)]
    private ?string $decision = null; // 'admis', 'redouble', 'en_cours'

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $appreciationGenerale = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remarques = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Etudiant $etudiant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Filiere $filiere = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Classe $classe = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->decision = 'en_cours';
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

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): static
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getMoyenneAnnuelle(): ?string
    {
        return $this->moyenneAnnuelle;
    }

    public function setMoyenneAnnuelle(?string $moyenneAnnuelle): static
    {
        $this->moyenneAnnuelle = $moyenneAnnuelle;
        return $this;
    }

    public function getMoyenneTrimestre1(): ?string
    {
        return $this->moyenneTrimestre1;
    }

    public function setMoyenneTrimestre1(?string $moyenneTrimestre1): static
    {
        $this->moyenneTrimestre1 = $moyenneTrimestre1;
        return $this;
    }

    public function getMoyenneTrimestre2(): ?string
    {
        return $this->moyenneTrimestre2;
    }

    public function setMoyenneTrimestre2(?string $moyenneTrimestre2): static
    {
        $this->moyenneTrimestre2 = $moyenneTrimestre2;
        return $this;
    }

    public function getMoyenneTrimestre3(): ?string
    {
        return $this->moyenneTrimestre3;
    }

    public function setMoyenneTrimestre3(?string $moyenneTrimestre3): static
    {
        $this->moyenneTrimestre3 = $moyenneTrimestre3;
        return $this;
    }

    public function getRangClasse(): ?int
    {
        return $this->rangClasse;
    }

    public function setRangClasse(?int $rangClasse): static
    {
        $this->rangClasse = $rangClasse;
        return $this;
    }

    public function getTotalEtudiants(): ?int
    {
        return $this->totalEtudiants;
    }

    public function setTotalEtudiants(?int $totalEtudiants): static
    {
        $this->totalEtudiants = $totalEtudiants;
        return $this;
    }

    public function getTotalAbsences(): ?int
    {
        return $this->totalAbsences;
    }

    public function setTotalAbsences(?int $totalAbsences): static
    {
        $this->totalAbsences = $totalAbsences;
        return $this;
    }

    public function getAbsencesJustifiees(): ?int
    {
        return $this->absencesJustifiees;
    }

    public function setAbsencesJustifiees(?int $absencesJustifiees): static
    {
        $this->absencesJustifiees = $absencesJustifiees;
        return $this;
    }

    public function getTauxAssiduite(): ?string
    {
        return $this->tauxAssiduite;
    }

    public function setTauxAssiduite(?string $tauxAssiduite): static
    {
        $this->tauxAssiduite = $tauxAssiduite;
        return $this;
    }

    public function getDecision(): ?string
    {
        return $this->decision;
    }

    public function setDecision(string $decision): static
    {
        $this->decision = $decision;
        return $this;
    }

    public function getAppreciationGenerale(): ?string
    {
        return $this->appreciationGenerale;
    }

    public function setAppreciationGenerale(?string $appreciationGenerale): static
    {
        $this->appreciationGenerale = $appreciationGenerale;
        return $this;
    }

    public function getRemarques(): ?string
    {
        return $this->remarques;
    }

    public function setRemarques(?string $remarques): static
    {
        $this->remarques = $remarques;
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

    public function getFiliere(): ?Filiere
    {
        return $this->filiere;
    }

    public function setFiliere(?Filiere $filiere): static
    {
        $this->filiere = $filiere;
        return $this;
    }

    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    public function setClasse(?Classe $classe): static
    {
        $this->classe = $classe;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s (%s)',
            $this->etudiant?->getNom() ?? '',
            $this->anneeScolaire ?? '',
            $this->niveau ?? ''
        );
    }
}

