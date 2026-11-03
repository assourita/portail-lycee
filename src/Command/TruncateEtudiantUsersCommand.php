<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'app:truncate-etudiant-users',
    description: 'Supprime tous les utilisateurs avec le rôle étudiant'
)]
class TruncateEtudiantUsersCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this->setHelp('Cette commande supprime tous les utilisateurs ayant le rôle ROLE_ETUDIANT');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Suppression des utilisateurs avec le rôle étudiant');

        // Demander confirmation
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'Êtes-vous sûr de vouloir supprimer tous les utilisateurs avec le rôle étudiant ? Cette action est irréversible. (y/n) ',
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            $io->warning('Opération annulée');
            return Command::SUCCESS;
        }

        // Désactiver les contraintes de clé étrangère
        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS = 0');

        try {
            // Récupérer tous les utilisateurs avec le rôle étudiant
            $users = $this->entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ETUDIANT%')
                ->getQuery()
                ->getResult();

            $count = count($users);

            if ($count === 0) {
                $io->info('Aucun utilisateur avec le rôle étudiant trouvé.');
                return Command::SUCCESS;
            }

            // Supprimer les utilisateurs
            foreach ($users as $user) {
                $this->entityManager->remove($user);
            }
            
            $this->entityManager->flush();
            
            // Réactiver les contraintes de clé étrangère
            $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
            
            $io->success(sprintf('%d utilisateurs avec le rôle étudiant ont été supprimés', $count));
        } catch (\Exception $e) {
            // En cas d'erreur, réactiver les contraintes
            $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
            $io->error('Une erreur est survenue : ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
} 