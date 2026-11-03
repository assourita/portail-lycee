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
    name: 'app:create-notes',
    description: 'Créer des notes de test'
)]
class CreateNotesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>=== CRÉATION DE NOTES ===</info>');

        // Récupérer les étudiants et évaluations
        $etudiants = $this->entityManager->getRepository(Etudiant::class)->findAll();
        $evaluations = $this->entityManager->getRepository(Evaluation::class)->findAll();

        if (empty($etudiants) || empty($evaluations)) {
            $output->writeln('<error>Aucun étudiant ou évaluation trouvé!</error>');
            return Command::FAILURE;
        }

        $createdCount = 0;

        // Créer des notes pour chaque étudiant
        foreach ($etudiants as $etudiant) {
            if (!$etudiant->getFiliere()) {
                continue;
            }

            // Récupérer les évaluations de la filière de l'étudiant
            $evaluationsFiliere = array_filter($evaluations, function($eval) use ($etudiant) {
                return $eval->getMatiere()->getFiliere()->getId() === $etudiant->getFiliere()->getId();
            });

            // Créer 2-4 notes par étudiant
            $nbNotes = rand(2, min(4, count($evaluationsFiliere)));
            
            foreach ($evaluationsFiliere as $evaluation) {
                if ($nbNotes <= 0) break;

                // Générer une note aléatoire
                $valeurNote = rand(8, $evaluation->getNoteMax());
                
                $note = new Note();
                $note->setValeur($valeurNote);
                $note->setDateNote($evaluation->getDateEvaluation());
                $note->setCommentaire($this->getAppreciation($valeurNote, $evaluation->getNoteMax()));
                $note->setType('evaluation'); // Type par défaut
                $note->setEtudiant($etudiant);
                $note->setMatiere($evaluation->getMatiere());
                $note->setEnseignant($evaluation->getEnseignant());
                $note->setEvaluation($evaluation);
                $note->setPeriodeAcademique($evaluation->getPeriodeAcademique());
                
                $this->entityManager->persist($note);
                $createdCount++;
                $nbNotes--;

                $output->writeln('<comment>Note créée: ' . $etudiant->getNom() . 
                    ' - ' . $evaluation->getNom() . ' = ' . $valeurNote . '/' . $evaluation->getNoteMax() . '</comment>');
            }
        }

        $this->entityManager->flush();
        $output->writeln('<success>' . $createdCount . ' notes créées avec succès!</success>');

        return Command::SUCCESS;
    }

    private function getAppreciation(int $note, int $noteMax): string
    {
        $pourcentage = ($note / $noteMax) * 100;
        
        if ($pourcentage >= 90) {
            return 'Excellent';
        } elseif ($pourcentage >= 80) {
            return 'Très bien';
        } elseif ($pourcentage >= 70) {
            return 'Bien';
        } elseif ($pourcentage >= 60) {
            return 'Assez bien';
        } elseif ($pourcentage >= 50) {
            return 'Passable';
        } else {
            return 'Insuffisant';
        }
    }
}
