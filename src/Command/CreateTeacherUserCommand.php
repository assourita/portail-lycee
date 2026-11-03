<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Enseignant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateTeacherUserCommand extends Command
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:create-teacher-user')
            ->setDescription('Creates a new user and teacher and links them together');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create User
        $user = new User();
        $user->setUsername('prof1');
        $user->setEmail('prof1@example.com');
        $user->setRoles(['ROLE_ENSEIGNANT']);
        $user->setIsActivate(true);
        
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);

        // Create Enseignant
        $enseignant = new Enseignant();
        $enseignant->setNom('Dupont');
        $enseignant->setPrenom('Jean');
        $enseignant->setEmail('prof1@example.com');
        $enseignant->setSpecialite('Mathématiques');
        
        // Link them together
        $enseignant->setUser($user);
        $user->setEnseignant($enseignant);

        // Save to database
        $this->entityManager->persist($user);
        $this->entityManager->persist($enseignant);
        $this->entityManager->flush();

        $io->success('Teacher user created successfully!');

        return Command::SUCCESS;
    }
} 