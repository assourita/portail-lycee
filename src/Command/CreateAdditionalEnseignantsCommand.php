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

class CreateAdditionalEnseignantsCommand extends Command
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
            ->setName('app:create-additional-enseignants')
            ->setDescription('Crée des enseignants supplémentaires avec différentes spécialités');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $enseignants = [
            [
                'username' => 'prof2',
                'email' => 'prof2@example.com',
                'password' => 'password123',
                'nom' => 'Martin',
                'prenom' => 'Sophie',
                'specialite' => 'Physique'
            ],
            [
                'username' => 'prof3',
                'email' => 'prof3@example.com',
                'password' => 'password123',
                'nom' => 'Dubois',
                'prenom' => 'Pierre',
                'specialite' => 'Chimie'
            ],
            [
                'username' => 'prof4',
                'email' => 'prof4@example.com',
                'password' => 'password123',
                'nom' => 'Leroy',
                'prenom' => 'Marie',
                'specialite' => 'Sciences de la Vie et de la Terre'
            ],
            [
                'username' => 'prof5',
                'email' => 'prof5@example.com',
                'password' => 'password123',
                'nom' => 'Moreau',
                'prenom' => 'Claire',
                'specialite' => 'Français'
            ],
            [
                'username' => 'prof6',
                'email' => 'prof6@example.com',
                'password' => 'password123',
                'nom' => 'Petit',
                'prenom' => 'Thomas',
                'specialite' => 'Anglais'
            ],
            [
                'username' => 'prof7',
                'email' => 'prof7@example.com',
                'password' => 'password123',
                'nom' => 'Robert',
                'prenom' => 'Isabelle',
                'specialite' => 'Histoire-Géographie'
            ],
            [
                'username' => 'prof8',
                'email' => 'prof8@example.com',
                'password' => 'password123',
                'nom' => 'Richard',
                'prenom' => 'Philippe',
                'specialite' => 'Philosophie'
            ],
            [
                'username' => 'prof9',
                'email' => 'prof9@example.com',
                'password' => 'password123',
                'nom' => 'Durand',
                'prenom' => 'Catherine',
                'specialite' => 'Sciences Économiques et Sociales'
            ],
            [
                'username' => 'prof10',
                'email' => 'prof10@example.com',
                'password' => 'password123',
                'nom' => 'Simon',
                'prenom' => 'Nicolas',
                'specialite' => 'Informatique'
            ],
            [
                'username' => 'prof11',
                'email' => 'prof11@example.com',
                'password' => 'password123',
                'nom' => 'Laurent',
                'prenom' => 'Émilie',
                'specialite' => 'Arts Plastiques'
            ],
            [
                'username' => 'prof12',
                'email' => 'prof12@example.com',
                'password' => 'password123',
                'nom' => 'Michel',
                'prenom' => 'Laurence',
                'specialite' => 'Latin'
            ]
        ];

        foreach ($enseignants as $enseignantData) {
            // Vérifier si l'utilisateur existe déjà
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['username' => $enseignantData['username']]);

            if ($existingUser) {
                $io->warning("L'utilisateur {$enseignantData['username']} existe déjà.");
                continue;
            }

            // Créer l'utilisateur
            $user = new User();
            $user->setUsername($enseignantData['username']);
            $user->setEmail($enseignantData['email']);
            $user->setRoles(['ROLE_ENSEIGNANT']);
            $user->setIsActivate(true);
            
            $hashedPassword = $this->passwordHasher->hashPassword($user, $enseignantData['password']);
            $user->setPassword($hashedPassword);

            // Créer l'enseignant
            $enseignant = new Enseignant();
            $enseignant->setNom($enseignantData['nom']);
            $enseignant->setPrenom($enseignantData['prenom']);
            $enseignant->setEmail($enseignantData['email']);
            $enseignant->setSpecialite($enseignantData['specialite']);
            
            // Lier l'enseignant à l'utilisateur
            $enseignant->setUser($user);
            $user->setEnseignant($enseignant);

            // Sauvegarder
            $this->entityManager->persist($user);
            $this->entityManager->persist($enseignant);
        }

        $this->entityManager->flush();

        $io->success('Les enseignants supplémentaires ont été créés avec succès !');

        return Command::SUCCESS;
    }
} 