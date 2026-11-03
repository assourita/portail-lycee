<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Le nom d\'utilisateur est obligatoire')]
    #[Assert\Length(min: 3, max: 180, minMessage: 'Le nom d\'utilisateur doit faire au moins {{ limit }} caractères', maxMessage: 'Le nom d\'utilisateur ne peut pas dépasser {{ limit }} caractères')]
    private ?string $username = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private ?bool $isActivate = false;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email(message: 'L\'adresse email {{ value }} n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Enseignant::class, cascade: ['persist', 'remove'])]
    private ?Enseignant $enseignant = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Etudiant::class, cascade: ['persist', 'remove'])]
    private ?Etudiant $etudiant = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: ParentEleve::class, cascade: ['persist', 'remove'])]
    private ?ParentEleve $parentEleve = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Comptable::class, cascade: ['persist', 'remove'])]
    private ?Comptable $comptable = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Admin::class, cascade: ['persist', 'remove'])]
    private ?Admin $admin = null;

    // Paramètres utilisateur
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $theme = 'light';

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $langue = 'fr';

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $notificationsEmail = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $notificationsSms = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $googleAuthenticatorSecret = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $passwordChangedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function isActivate(): ?bool
    {
        return $this->isActivate;
    }

    public function setIsActivate(bool $isActivate): static
    {
        $this->isActivate = $isActivate;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function getEnseignant(): ?Enseignant
    {
        return $this->enseignant;
    }

    public function setEnseignant(?Enseignant $enseignant): static
    {
        if ($enseignant === null && $this->enseignant !== null) {
            $this->enseignant->setUser(null);
        }

        if ($enseignant !== null && $enseignant->getUser() !== $this) {
            $enseignant->setUser($this);
        }

        $this->enseignant = $enseignant;
        return $this;
    }

    public function getEtudiant(): ?Etudiant
    {
        return $this->etudiant;
    }

    public function setEtudiant(?Etudiant $etudiant): static
    {
        if ($etudiant === null && $this->etudiant !== null) {
            $this->etudiant->setUser(null);
        }

        if ($etudiant !== null && $etudiant->getUser() !== $this) {
            $etudiant->setUser($this);
        }

        $this->etudiant = $etudiant;
        return $this;
    }

    public function getParentEleve(): ?ParentEleve
    {
        return $this->parentEleve;
    }

    public function setParentEleve(?ParentEleve $parentEleve): static
    {
        if ($parentEleve === null && $this->parentEleve !== null) {
            $this->parentEleve->setUser(null);
        }

        if ($parentEleve !== null && $parentEleve->getUser() !== $this) {
            $parentEleve->setUser($this);
        }

        $this->parentEleve = $parentEleve;
        return $this;
    }

    public function getComptable(): ?Comptable
    {
        return $this->comptable;
    }

    public function setComptable(?Comptable $comptable): static
    {
        if ($comptable === null && $this->comptable !== null) {
            $this->comptable->setUser(null);
        }

        if ($comptable !== null && $comptable->getUser() !== $this) {
            $comptable->setUser($this);
        }

        $this->comptable = $comptable;
        return $this;
    }

    public function getAdmin(): ?Admin
    {
        return $this->admin;
    }

    public function setAdmin(?Admin $admin): static
    {
        if ($admin === null && $this->admin !== null) {
            $this->admin->setUser(null);
        }

        if ($admin !== null && $admin->getUser() !== $this) {
            $admin->setUser($this);
        }

        $this->admin = $admin;
        return $this;
    }

    // Getters et setters pour les paramètres
    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): static
    {
        $this->theme = $theme;
        return $this;
    }

    public function getLangue(): ?string
    {
        return $this->langue;
    }

    public function setLangue(?string $langue): static
    {
        $this->langue = $langue;
        return $this;
    }

    public function getNotificationsEmail(): ?bool
    {
        return $this->notificationsEmail;
    }

    public function setNotificationsEmail(?bool $notificationsEmail): static
    {
        $this->notificationsEmail = $notificationsEmail;
        return $this;
    }

    public function getNotificationsSms(): ?bool
    {
        return $this->notificationsSms;
    }

    public function setNotificationsSms(?bool $notificationsSms): static
    {
        $this->notificationsSms = $notificationsSms;
        return $this;
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->googleAuthenticatorSecret;
    }

    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): static
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
        return $this;
    }

    public function getPasswordChangedAt(): ?\DateTimeInterface
    {
        return $this->passwordChangedAt;
    }

    public function setPasswordChangedAt(?\DateTimeInterface $passwordChangedAt): static
    {
        $this->passwordChangedAt = $passwordChangedAt;
        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }
}
