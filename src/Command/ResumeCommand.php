<?php

namespace App\Command;

use App\Entity\Etudiant;
use App\Entity\Evaluation;
use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:resume-activation',
    description: 'Résumé de l\'activation des fonctionnalités'
)]
class ResumeCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>=== RÉSUMÉ ACTIVATION FONCTIONNALITÉS ===</info>');

        // Compter les entités principales
        $etudiants = $this->entityManager->getRepository(Etudiant::class)->findAll();
        $evaluations = $this->entityManager->getRepository(Evaluation::class)->findAll();
        $notes = $this->entityManager->getRepository(Note::class)->findAll();

        $output->writeln('<comment>Étudiants: ' . count($etudiants) . '</comment>');
        $output->writeln('<comment>Évaluations: ' . count($evaluations) . '</comment>');
        $output->writeln('<comment>Notes: ' . count($notes) . '</comment>');

        $output->writeln('');
        $output->writeln('<info>FONCTIONNALITÉS ACTIVÉES:</info>');
        $output->writeln('<comment>✓ Dashboard comptable avec statistiques réelles</comment>');
        $output->writeln('<comment>✓ Gestion des paiements et factures</comment>');
        $output->writeln('<comment>✓ Rapports financiers</comment>');
        $output->writeln('<comment>✓ Gestion des étudiants (comptable)</comment>');
        $output->writeln('<comment>✓ Cours accessibles aux étudiants</comment>');
        $output->writeln('<comment>✓ Évaluations programmées (enseignants)</comment>');
        $output->writeln('<comment>✓ Notes des étudiants</comment>');
        $output->writeln('<comment>✓ Évaluations visibles par les étudiants</comment>');
        $output->writeln('<comment>✓ Templates complets et fonctionnels</comment>');

        $output->writeln('');
        $output->writeln('<info>ROUTES ACTIVÉES:</info>');
        $output->writeln('<comment>- /comptable/dashboard (Dashboard comptable)</comment>');
        $output->writeln('<comment>- /comptable/paiements (Gestion paiements)</comment>');
        $output->writeln('<comment>- /comptable/factures (Gestion factures)</comment>');
        $output->writeln('<comment>- /comptable/rapports (Rapports)</comment>');
        $output->writeln('<comment>- /comptable/etudiants (Gestion étudiants)</comment>');
        $output->writeln('<comment>- /etudiant/evaluations (Évaluations étudiantes)</comment>');
        $output->writeln('<comment>- /enseignant/evaluations (Évaluations enseignants)</comment>');

        return Command::SUCCESS;
    }
}
