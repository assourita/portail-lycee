<?php

namespace App\Command;

use App\Entity\Etudiant;
use App\Entity\Filiere;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-students',
    description: 'Crée des étudiants pour chaque filière',
)]
class CreateStudentsCommand extends Command
{
    private $entityManager;
    private $passwordHasher;
    private $usedUsernames = [];

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    private function generateUniqueUsername(string $baseUsername): string
    {
        $username = $baseUsername;
        $counter = 1;
        
        while (in_array($username, $this->usedUsernames)) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        $this->usedUsernames[] = $username;
        return $username;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer toutes les filières
        $filieres = $this->entityManager->getRepository(Filiere::class)->findAll();

        if (empty($filieres)) {
            $io->error('Aucune filière trouvée. Veuillez d\'abord créer des filières.');
            return Command::FAILURE;
        }

        $noms = ['Dupont', 'Martin', 'Bernard', 'Thomas', 'Petit', 'Robert', 'Richard', 'Durand', 'Dubois', 'Moreau'];
        $prenoms = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Paul', 'Julie', 'Thomas', 'Camille', 'Lucas', 'Emma'];

        foreach ($filieres as $filiere) {
            $io->section('Création des étudiants pour la filière : ' . $filiere->getNom());

            // Créer 5 étudiants par filière
            for ($i = 0; $i < 5; $i++) {
                // Créer l'utilisateur
                $user = new User();
                
                // Générer un nom et prénom aléatoires
                $nom = $noms[array_rand($noms)];
                $prenom = $prenoms[array_rand($prenoms)];
                
                // Configurer l'utilisateur
                $baseUsername = strtolower($prenom . '.' . $nom);
                $username = $this->generateUniqueUsername($baseUsername);
                $email = $username . '@etudiant.com';
                $password = 'etudiant123';
                
                $user->setUsername($username);
                $user->setEmail($email);
                $user->setPassword($this->passwordHasher->hashPassword($user, $password));
                $user->setRoles(['ROLE_ETUDIANT']);
                $user->setIsActivate(true);
                
                // Créer l'étudiant
                $etudiant = new Etudiant();
                $etudiant->setNom($nom);
                $etudiant->setPrenom($prenom);
                
                // Générer une date de naissance aléatoire (entre 18 et 25 ans)
                $dateNaissance = new \DateTime();
                $dateNaissance->modify('-' . rand(18, 25) . ' years');
                $dateNaissance->modify('-' . rand(0, 365) . ' days');
                $etudiant->setDateNaissance($dateNaissance);
                
                // Associer l'étudiant à la filière
                $etudiant->setFiliere($filiere);
                
                // Associer l'utilisateur à l'étudiant
                $user->setEtudiant($etudiant);
                
                $this->entityManager->persist($user);
                $this->entityManager->persist($etudiant);
                
                $io->writeln(sprintf('Créé l\'étudiant : %s %s (%s)', $prenom, $nom, $email));
            }
        }

        $this->entityManager->flush();

        $io->success('Les étudiants ont été créés avec succès !');

        return Command::SUCCESS;
    }
} 