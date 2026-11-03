<?php

namespace App\Entity;

use App\Repository\ClasseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClasseRepository::class)]
class Classe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null; // "10ème", "11ème", "Terminale"

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(targetEntity: Filiere::class, mappedBy: 'classe')]
    private Collection $filieres;

    #[ORM\OneToMany(targetEntity: Trimestre::class, mappedBy: 'classe', cascade: ['persist', 'remove'])]
    private Collection $trimestres;

    public function __construct()
    {
        $this->filieres = new ArrayCollection();
        $this->trimestres = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }

    /**
     * @return Collection<int, Filiere>
     */
    public function getFilieres(): Collection
    {
        return $this->filieres;
    }

    public function addFiliere(Filiere $filiere): static
    {
        if (!$this->filieres->contains($filiere)) {
            $this->filieres->add($filiere);
            $filiere->setClasse($this);
        }

        return $this;
    }

    public function removeFiliere(Filiere $filiere): static
    {
        if ($this->filieres->removeElement($filiere)) {
            // set the owning side to null (unless already changed)
            if ($filiere->getClasse() === $this) {
                $filiere->setClasse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Trimestre>
     */
    public function getTrimestres(): Collection
    {
        return $this->trimestres;
    }

    public function addTrimestre(Trimestre $trimestre): static
    {
        if (!$this->trimestres->contains($trimestre)) {
            $this->trimestres->add($trimestre);
            $trimestre->setClasse($this);
        }

        return $this;
    }

    public function removeTrimestre(Trimestre $trimestre): static
    {
        if ($this->trimestres->removeElement($trimestre)) {
            // set the owning side to null (unless already changed)
            if ($trimestre->getClasse() === $this) {
                $trimestre->setClasse(null);
            }
        }

        return $this;
    }

    /**
     * Obtenir un trimestre spécifique pour une année scolaire donnée
     */
    public function getTrimestre(string $nomTrimestre, string $anneeScolaire): ?Trimestre
    {
        foreach ($this->trimestres as $trimestre) {
            if ($trimestre->getNom() === $nomTrimestre && $trimestre->getAnneeScolaire() === $anneeScolaire) {
                return $trimestre;
            }
        }
        return null;
    }
}
