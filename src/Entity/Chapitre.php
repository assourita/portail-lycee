<?php

namespace App\Entity;

use App\Repository\ChapitreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ChapitreRepository::class)]
class Chapitre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\Column]
    private ?int $ordre = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    // Dans l'entité Chapitre
#[ORM\ManyToOne(inversedBy: 'chapitres')]
#[ORM\JoinColumn(nullable: false)]
private ?Cours $cours = null;

#[ORM\OneToMany(mappedBy: 'chapitre', targetEntity: PieceJointe::class, cascade: ['persist', 'remove'])]
private Collection $piecesJointes;

public function __construct()
{
    $this->piecesJointes = new ArrayCollection();
    $this->createdAt = new \DateTimeImmutable();
    $this->updatedAt = new \DateTimeImmutable();
}

public function getCours(): ?Cours
{
    return $this->cours;
}

public function setCours(?Cours $cours): static
{
    $this->cours = $cours;

    return $this;
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

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * @return Collection<int, PieceJointe>
     */
    public function getPiecesJointes(): Collection
    {
        return $this->piecesJointes;
    }

    public function setPiecesJointes(Collection $piecesJointes): static
    {
        $this->piecesJointes = $piecesJointes;
        return $this;
    }

    public function addPieceJointe(PieceJointe $pieceJointe): static
    {
        if (!$this->piecesJointes->contains($pieceJointe)) {
            $this->piecesJointes->add($pieceJointe);
            $pieceJointe->setChapitre($this);
        }

        return $this;
    }

    public function removePieceJointe(PieceJointe $pieceJointe): static
    {
        if ($this->piecesJointes->removeElement($pieceJointe)) {
            // set the owning side to null (unless already changed)
            if ($pieceJointe->getChapitre() === $this) {
                $pieceJointe->setChapitre(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
