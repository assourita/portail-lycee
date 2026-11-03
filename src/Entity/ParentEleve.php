<?php

namespace App\Entity;

use App\Repository\ParentEleveRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParentEleveRepository::class)]
#[ORM\Table(name: 'parent_eleve')]
class ParentEleve
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephoneSecondaire = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 50)]
    private ?string $lienParente = null; // 'pere', 'mere', 'tuteur', 'autre'

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $profession = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\OneToOne(inversedBy: 'parentEleve', cascade: ['persist'])]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: Etudiant::class, inversedBy: 'parentsEleves')]
    #[ORM\JoinTable(name: 'parent_eleve_etudiant')]
    private Collection $enfants;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->enfants = new ArrayCollection();
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getTelephoneSecondaire(): ?string
    {
        return $this->telephoneSecondaire;
    }

    public function setTelephoneSecondaire(?string $telephoneSecondaire): static
    {
        $this->telephoneSecondaire = $telephoneSecondaire;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getLienParente(): ?string
    {
        return $this->lienParente;
    }

    public function setLienParente(string $lienParente): static
    {
        $this->lienParente = $lienParente;
        return $this;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(?string $profession): static
    {
        $this->profession = $profession;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Collection<int, Etudiant>
     */
    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function addEnfant(Etudiant $enfant): static
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants->add($enfant);
        }

        return $this;
    }

    public function removeEnfant(Etudiant $enfant): static
    {
        $this->enfants->removeElement($enfant);

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s %s (%s)', $this->nom ?? '', $this->prenom ?? '', $this->lienParente ?? '');
    }
}

