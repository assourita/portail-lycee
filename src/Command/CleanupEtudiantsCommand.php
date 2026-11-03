<?php

namespace App\Command;

use App\Entity\Etudiant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:cleanup-etudiants',
    description: 'Nettoie les étudiants sans user_id et crée les utilisateurs associés'
)]
class CleanupEtudiantsCommand extends Command
{
    private $entityManager;
    private $passwordHasher;
    private $existingUsernames = [];

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure()
    {
        $this->setHelp('Cette commande crée des utilisateurs pour les étudiants qui n\'en ont pas');
    }

    private function generateUniqueUsername(string $baseUsername): string
    {
        // Charger tous les noms d'utilisateur existants une seule fois
        if (empty($this->existingUsernames)) {
            $users = $this->entityManager->getRepository(User::class)->findAll();
            foreach ($users as $user) {
                $this->existingUsernames[$user->getUsername()] = true;
            }
        }

        $username = $baseUsername;
        $suffix = 1;

        // Tant que le nom d'utilisateur existe, ajouter un suffixe numérique
        while (isset($this->existingUsernames[$username])) {
            $username = $baseUsername . $suffix;
            $suffix++;
        }

        // Ajouter le nouveau nom d'utilisateur à la liste
        $this->existingUsernames[$username] = true;

        return $username;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Nettoyage des étudiants sans user_id');

        // D'abord, vérifier s'il y a des étudiants avec des user_id invalides
        $invalidEtudiants = $this->entityManager->getRepository(Etudiant::class)
            ->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')
            ->where('e.user IS NOT NULL')
            ->andWhere('u.id IS NULL')
            ->getQuery()
            ->getResult();

        foreach ($invalidEtudiants as $etudiant) {
            $etudiant->setUser(null);
            $io->warning(sprintf('Correction de l\'étudiant %s %s avec un user_id invalide', 
                $etudiant->getNom(), 
                $etudiant->getPrenom()
            ));
        }

        $this->entityManager->flush();

        // Maintenant, traiter les étudiants sans user_id
        $etudiants = $this->entityManager->getRepository(Etudiant::class)->findBy(['user' => null]);
        
        if (empty($etudiants)) {
            $io->success('Aucun étudiant sans user_id trouvé.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('Trouvé %d étudiants sans user_id.', count($etudiants)));

        foreach ($etudiants as $etudiant) {
            $email = $etudiant->getEmail();
            if (!$email) {
                $io->warning(sprintf('L\'étudiant %s %s n\'a pas d\'email.', 
                    $etudiant->getNom(), 
                    $etudiant->getPrenom()
                ));
                continue;
            }

            // Vérifier si un utilisateur avec cet email existe déjà
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                // Vérifier si l'utilisateur est déjà associé à un autre étudiant
                $existingEtudiant = $this->entityManager->getRepository(Etudiant::class)->findOneBy(['user' => $existingUser]);
                if ($existingEtudiant) {
                    $io->warning(sprintf('L\'email %s est déjà utilisé par l\'étudiant %s %s', 
                        $email,
                        $existingEtudiant->getNom(),
                        $existingEtudiant->getPrenom()
                    ));
                    continue;
                }
                
                $etudiant->setUser($existingUser);
                $io->info(sprintf('Utilisateur existant associé à l\'étudiant %s %s', 
                    $etudiant->getNom(), 
                    $etudiant->getPrenom()
                ));
            } else {
                // Créer un nouvel utilisateur
                $user = new User();
                $user->setEmail($email);
                
                // Générer un nom d'utilisateur unique
                $baseUsername = strtolower($etudiant->getPrenom() . '.' . $etudiant->getNom());
                $username = $this->generateUniqueUsername($baseUsername);
                $user->setUsername($username);
                
                $user->setRoles(['ROLE_ETUDIANT']);
                $user->setIsActivate(true);
                
                // Générer un mot de passe temporaire
                $password = bin2hex(random_bytes(4));
                $user->setPassword($this->passwordHasher->hashPassword($user, $password));
                
                // D'abord persister l'utilisateur
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                
                // Ensuite associer l'étudiant
                $etudiant->setUser($user);
                
                $io->info(sprintf('Nouvel utilisateur créé pour l\'étudiant %s %s (username: %s, mot de passe temporaire: %s)', 
                    $etudiant->getNom(), 
                    $etudiant->getPrenom(),
                    $username,
                    $password
                ));
            }
        }

        $this->entityManager->flush();
        $io->success('Opération terminée avec succès.');

        return Command::SUCCESS;
    }
} 