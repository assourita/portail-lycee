<?php

namespace App\Entity;

use App\Repository\FactureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FactureRepository::class)]
class Facture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $montant = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $dateEcheance = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $dateCreation = null;

    #[ORM\ManyToOne(targetEntity: Etudiant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Etudiant $etudiant = null;

    /**
     * @var Collection<int, Paiement>
     */
    #[ORM\OneToMany(targetEntity: Paiement::class, mappedBy: 'facture')]
    private Collection $paiements;

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
        $this->dateCreation = new \DateTime();
        $this->statut = self::STATUT_EN_ATTENTE;
    }

    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_PAYE = 'paye';
    public const STATUT_ANNULE = 'annule';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
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

    public function getDateEcheance(): ?\DateTime
    {
        return $this->dateEcheance;
    }

    public function setDateEcheance(\DateTime $dateEcheance): static
    {
        $this->dateEcheance = $dateEcheance;
        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
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
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function getMontantPaye(): float
    {
        $total = 0.0;
        foreach ($this->paiements as $paiement) {
            if ($paiement->getStatut() === 'paye') {
                $total += $paiement->getMontant();
            }
        }

        return $total;
    }

    public function getResteAPayer(): float
    {
        return max(0.0, (float) $this->montant - $this->getMontantPaye());
    }

    public function isEnRetard(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE
            && $this->dateEcheance !== null
            && $this->dateEcheance < new \DateTime('today');
    }

    /**
     * Passe la facture à « payée » dès que les paiements couvrent son montant.
     */
    public function actualiserStatut(): static
    {
        if ($this->statut !== self::STATUT_ANNULE) {
            $this->statut = $this->getResteAPayer() <= 0.0 ? self::STATUT_PAYE : self::STATUT_EN_ATTENTE;
        }

        return $this;
    }
}
