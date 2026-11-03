<?php

namespace App\Entity;

use App\Repository\MatiereRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: MatiereRepository::class)]
class Matiere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?int $coeficient = null;

    #[ORM\OneToMany(mappedBy: 'matiere', targetEntity: Cours::class)]
    private Collection $cours;

    #[ORM\ManyToOne(inversedBy: 'matieres')]
    private ?Filiere $filiere = null;

    #[ORM\ManyToOne(inversedBy: 'matieres')]
    private ?Enseignant $enseignant = null;

    #[ORM\OneToMany(mappedBy: 'matiere', targetEntity: Note::class)]
    private Collection $notes;

    public function __construct()
    {
        $this->cours = new ArrayCollection();
        $this->notes = new ArrayCollection();
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

    public function getCoeficient(): ?int
    {
        return $this->coeficient;
    }

    public function setCoeficient(int $coeficient): static
    {
        $this->coeficient = $coeficient;
        return $this;
    }

    public function getCours(): Collection
    {
        return $this->cours;
    }

    public function addCours(Cours $cours): static
    {
        if (!$this->cours->contains($cours)) {
            $this->cours->add($cours);
            $cours->setMatiere($this);
        }
        return $this;
    }

    public function removeCours(Cours $cours): static
    {
        if ($this->cours->removeElement($cours)) {
            if ($cours->getMatiere() === $this) {
                $cours->setMatiere(null);
            }
        }
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

    public function getEnseignant(): ?Enseignant
    {
        return $this->enseignant;
    }

    public function setEnseignant(?Enseignant $enseignant): static
    {
        $this->enseignant = $enseignant;
        return $this;
    }

    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setMatiere($this);
        }
        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getMatiere() === $this) {
                $note->setMatiere(null);
            }
        }
        return $this;
    }
}
