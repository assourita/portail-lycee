<?php

namespace App\Command;

use App\Entity\Evaluation;
use App\Entity\Enseignant;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:debug-evaluations',
    description: 'Débogage des évaluations'
)]
class DebugEvaluationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EvaluationRepository $evaluationRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>=== DÉBOGAGE ÉVALUATIONS ===</info>');

        // 1. Vérifier les enseignants
        $enseignants = $this->entityManager->getRepository(Enseignant::class)->findAll();
        $output->writeln('<comment>Enseignants trouvés: ' . count($enseignants) . '</comment>');

        foreach ($enseignants as $enseignant) {
            $output->writeln('  - ' . $enseignant->getNom() . ' ' . $enseignant->getPrenom() . 
                ' (ID: ' . $enseignant->getId() . ', Matières: ' . count($enseignant->getMatieres()) . ')');
        }

        // 2. Vérifier les évaluations
        $evaluations = $this->evaluationRepository->findAll();
        $output->writeln('<comment>Évaluations trouvées: ' . count($evaluations) . '</comment>');

        foreach ($evaluations as $eval) {
            $output->writeln('  - ' . $eval->getNom() . 
                ' (ID: ' . $eval->getId() . 
                ', Date: ' . ($eval->getDateEvaluation() ? $eval->getDateEvaluation()->format('d/m/Y') : 'N/A') .
                ', Enseignant: ' . ($eval->getEnseignant() ? $eval->getEnseignant()->getNom() : 'N/A') .
                ', Matière: ' . ($eval->getMatiere() ? $eval->getMatiere()->getNom() : 'N/A') .
                ', Période: ' . ($eval->getPeriodeAcademique() ? $eval->getPeriodeAcademique()->getPeriode() : 'N/A') . ')');
        }

        // 3. Test pour un enseignant spécifique
        if (!empty($enseignants)) {
            $enseignantTest = $enseignants[0];
            $output->writeln('<info>=== TEST POUR: ' . $enseignantTest->getNom() . ' ===</info>');
            
            $qb = $this->evaluationRepository->createQueryBuilder('e')
                ->where('e.enseignant = :enseignant')
                ->setParameter('enseignant', $enseignantTest)
                ->orderBy('e.dateEvaluation', 'DESC');
            
            $evaluationsEnseignant = $qb->getQuery()->getResult();
            $output->writeln('<comment>Évaluations pour cet enseignant: ' . count($evaluationsEnseignant) . '</comment>');
            
            foreach ($evaluationsEnseignant as $eval) {
                $output->writeln('  - ' . $eval->getNom() . ' (' . $eval->getDateEvaluation()->format('d/m/Y') . ')');
            }
        }

        return Command::SUCCESS;
    }
}
