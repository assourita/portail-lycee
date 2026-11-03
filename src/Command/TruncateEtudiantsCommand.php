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
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'app:truncate-etudiants',
    description: 'Vide la table etudiant et supprime les utilisateurs correspondants'
)]
class TruncateEtudiantsCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this->setHelp('Cette commande vide la table etudiant et supprime les utilisateurs associés');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Vidage de la table etudiant et suppression des utilisateurs associés');

        // Demander confirmation
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'Êtes-vous sûr de vouloir supprimer tous les étudiants et leurs utilisateurs associés ? Cette action est irréversible. (y/n) ',
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            $io->warning('Opération annulée');
            return Command::SUCCESS;
        }

        // Désactiver les contraintes de clé étrangère
        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS = 0');

        try {
            // Récupérer tous les étudiants avec leurs utilisateurs
            $etudiants = $this->entityManager->getRepository(Etudiant::class)->findAll();
            $usersToDelete = [];

            foreach ($etudiants as $etudiant) {
                if ($etudiant->getUser()) {
                    $usersToDelete[] = $etudiant->getUser();
                }
            }

            // Supprimer tous les étudiants
            $this->entityManager->createQuery('DELETE FROM App\Entity\Etudiant e')->execute();
            
            // Supprimer les utilisateurs associés
            foreach ($usersToDelete as $user) {
                $this->entityManager->remove($user);
            }
            
            $this->entityManager->flush();
            
            // Réactiver les contraintes de clé étrangère
            $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
            
            $io->success(sprintf(
                'Opération terminée avec succès : %d étudiants et %d utilisateurs supprimés',
                count($etudiants),
                count($usersToDelete)
            ));
        } catch (\Exception $e) {
            // En cas d'erreur, réactiver les contraintes
            $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
            $io->error('Une erreur est survenue : ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
} 