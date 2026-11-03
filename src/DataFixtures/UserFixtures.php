<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création d'un admin
        $admin = new User();
        $admin->setEmail('admin@lycee.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // Création d'un étudiant
        $etudiant = new User();
        $etudiant->setEmail('etudiant@lycee.fr');
        $etudiant->setRoles(['ROLE_ETUDIANT']);
        $etudiant->setPassword($this->passwordHasher->hashPassword($etudiant, 'etudiant123'));
        $manager->persist($etudiant);

        // Création d'un enseignant
        $enseignant = new User();
        $enseignant->setEmail('enseignant@lycee.fr');
        $enseignant->setRoles(['ROLE_ENSEIGNANT']);
        $enseignant->setPassword($this->passwordHasher->hashPassword($enseignant, 'enseignant123'));
        $manager->persist($enseignant);

        // Création d'un comptable
        $comptable = new User();
        $comptable->setEmail('comptable@lycee.fr');
        $comptable->setRoles(['ROLE_COMPTABLE']);
        $comptable->setPassword($this->passwordHasher->hashPassword($comptable, 'comptable123'));
        $manager->persist($comptable);

        $manager->flush();
    }
} 