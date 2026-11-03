<?php

namespace App\Entity;

use App\Repository\SoumissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SoumissionRepository::class)]
class Soumission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateSoumission = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $fichiers = null; // JSON des chemins de fichiers

    #[ORM\Column(length: 50)]
    private ?string $statut = 'soumis'; // 'brouillon', 'soumis', 'corrige', 'rendu'

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaireEnseignant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCorrection = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\ManyToOne(inversedBy: 'soumissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Devoir $devoir = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Etudiant $etudiant = null;

    public function __construct()
    {
        $this->dateSoumission = new \DateTime();
        $this->statut = 'soumis';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateSoumission(): ?\DateTimeInterface
    {
        return $this->dateSoumission;
    }

    public function setDateSoumission(\DateTimeInterface $dateSoumission): static
    {
        $this->dateSoumission = $dateSoumission;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getFichiers(): ?string
    {
        return $this->fichiers;
    }

    public function setFichiers(?string $fichiers): static
    {
        $this->fichiers = $fichiers;
        return $this;
    }

    /**
     * @return list<array{nom: string, fichier: string}>
     */
    public function getListeFichiers(): array
    {
        $liste = $this->fichiers ? json_decode($this->fichiers, true) : [];

        return is_array($liste) ? array_values($liste) : [];
    }

    /**
     * @param array{nom: string, fichier: string} $fichier
     */
    public function ajouterFichier(array $fichier): static
    {
        $liste = $this->getListeFichiers();
        $liste[] = $fichier;
        $this->fichiers = json_encode($liste, JSON_UNESCAPED_UNICODE);

        return $this;
    }

    public function retirerFichier(string $nomStockage): static
    {
        $liste = array_filter($this->getListeFichiers(), fn (array $f) => $f['fichier'] !== $nomStockage);
        $this->fichiers = $liste ? json_encode(array_values($liste), JSON_UNESCAPED_UNICODE) : null;

        return $this;
    }

    public function getFichier(string $nomStockage): ?array
    {
        foreach ($this->getListeFichiers() as $fichier) {
            if ($fichier['fichier'] === $nomStockage) {
                return $fichier;
            }
        }

        return null;
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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getCommentaireEnseignant(): ?string
    {
        return $this->commentaireEnseignant;
    }

    public function setCommentaireEnseignant(?string $commentaireEnseignant): static
    {
        $this->commentaireEnseignant = $commentaireEnseignant;
        return $this;
    }

    public function getDateCorrection(): ?\DateTimeInterface
    {
        return $this->dateCorrection;
    }

    public function setDateCorrection(?\DateTimeInterface $dateCorrection): static
    {
        $this->dateCorrection = $dateCorrection;
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

    public function getDevoir(): ?Devoir
    {
        return $this->devoir;
    }

    public function setDevoir(?Devoir $devoir): static
    {
        $this->devoir = $devoir;
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

    /**
     * Vérifier si la soumission est en retard
     */
    public function isEnRetard(): bool
    {
        if (!$this->devoir) {
            return false;
        }

        return $this->dateSoumission > $this->devoir->getDateEcheance();
    }

    /**
     * Obtenir le délai de soumission (en jours)
     */
    public function getDelaiSoumission(): int
    {
        if (!$this->devoir) {
            return 0;
        }

        $diff = $this->devoir->getDateEcheance()->diff($this->dateSoumission);
        return $diff->invert ? -$diff->days : $diff->days;
    }

    public function __toString(): string
    {
        return sprintf(
            'Soumission de %s pour "%s"',
            $this->etudiant?->getNom() ?? '',
            $this->devoir?->getTitre() ?? ''
        );
    }
}

