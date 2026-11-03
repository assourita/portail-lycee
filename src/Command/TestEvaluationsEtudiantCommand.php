<?php

namespace App\Command;

use App\Entity\Etudiant;
use App\Entity\Evaluation;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test-evaluations-etudiant',
    description: 'Tester les évaluations pour un étudiant'
)]
class TestEvaluationsEtudiantCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>=== TEST ÉVALUATIONS ÉTUDIANT ===</info>');

        // Récupérer un étudiant
        $etudiant = $this->entityManager->getRepository(Etudiant::class)->findOneBy([], ['id' => 'ASC']);
        
        if (!$etudiant) {
            $output->writeln('<error>Aucun étudiant trouvé!</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Test pour: ' . $etudiant->getNom() . ' ' . $etudiant->getPrenom() . '</info>');
        $output->writeln('<info>Filière: ' . ($etudiant->getFiliere() ? $etudiant->getFiliere()->getNom() : 'N/A') . '</info>');

        // Récupérer les évaluations de sa filière
        $evaluations = $this->entityManager->getRepository(Evaluation::class)
            ->createQueryBuilder('e')
            ->join('e.matiere', 'm')
            ->where('m.filiere = :filiere')
            ->setParameter('filiere', $etudiant->getFiliere())
            ->orderBy('e.dateEvaluation', 'DESC')
            ->getQuery()
            ->getResult();

        $output->writeln('<comment>Évaluations trouvées: ' . count($evaluations) . '</comment>');

        foreach ($evaluations as $eval) {
            $output->writeln('  - ' . $eval->getNom() . 
                ' (Date: ' . $eval->getDateEvaluation()->format('d/m/Y') . 
                ', Matière: ' . $eval->getMatiere()->getNom() . ')');
        }

        return Command::SUCCESS;
    }
}
