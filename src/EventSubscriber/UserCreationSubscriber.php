<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\Admin;
use App\Entity\Etudiant;
use App\Entity\Enseignant;
use App\Entity\Comptable;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserCreationSubscriber implements EventSubscriberInterface
{
    private $passwordHasher;
    private $slugger;

    public function __construct(UserPasswordHasherInterface $passwordHasher, SluggerInterface $slugger)
    {
        $this->passwordHasher = $passwordHasher;
        $this->slugger = $slugger;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();

        // Vérifier si l'entité est l'une des entités que nous voulons gérer
        if (!$entity instanceof Admin && !$entity instanceof Etudiant && 
            !$entity instanceof Enseignant && !$entity instanceof Comptable) {
            return;
        }

        // Créer un nouvel utilisateur
        $user = new User();
        
        // Définir l'email et le mot de passe en fonction du type d'entité
        $email = strtolower($this->slugger->slug($entity->getNom() . '.' . $entity->getPrenom())) . '@lycee.fr';
        $password = $this->generateDefaultPassword($entity);
        
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        // Définir le rôle en fonction du type d'entité
        if ($entity instanceof Admin) {
            $user->setRoles(['ROLE_ADMIN']);
        } elseif ($entity instanceof Etudiant) {
            $user->setRoles(['ROLE_ETUDIANT']);
        } elseif ($entity instanceof Enseignant) {
            $user->setRoles(['ROLE_ENSEIGNANT']);
        } elseif ($entity instanceof Comptable) {
            $user->setRoles(['ROLE_COMPTABLE']);
        }

        // Sauvegarder l'utilisateur
        $entityManager->persist($user);
        $entityManager->flush();

        // Stocker les identifiants dans l'entité d'origine
        $entity->setEmail($email);
        $entity->setPassword($password);
    }

    private function generateDefaultPassword($entity): string
    {
        // Générer un mot de passe par défaut basé sur le nom et prénom
        $defaultPassword = strtolower($this->slugger->slug($entity->getNom() . $entity->getPrenom()));
        return $defaultPassword . '123';
    }
} 