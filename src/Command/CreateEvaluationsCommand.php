<?php

namespace App\Command;

use App\Entity\Evaluation;
use App\Entity\Enseignant;
use App\Entity\Matiere;
use App\Entity\Trimestre;
use App\Entity\PeriodeAcademique;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-evaluations',
    description: 'Créer des évaluations de test'
)]
class CreateEvaluationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>=== CREATION D EVALUATIONS ===</info>');

        // Récupérer les enseignants
        $enseignants = $this->entityManager->getRepository(Enseignant::class)->findAll();
        
        if (empty($enseignants)) {
            $output->writeln('<error>Aucun enseignant trouvé!</error>');
            return Command::FAILURE;
        }

        // Récupérer les trimestres
        $trimestres = $this->entityManager->getRepository(Trimestre::class)->findAll();
        
        if (empty($trimestres)) {
            $output->writeln('<error>Aucun trimestre trouvé!</error>');
            return Command::FAILURE;
        }

        // Récupérer la période active
        $periodeActive = $this->entityManager->getRepository(PeriodeAcademique::class)
            ->findOneBy(['isActive' => true]);

        if (!$periodeActive) {
            $output->writeln('<error>Aucune période active trouvée!</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Période active: ' . $periodeActive->getPeriode() . ' ' . $periodeActive->getAnneeScolaire() . '</info>');

        // Types d'évaluations
        $typesEvaluations = [
            'Devoir surveillé',
            'Interrogation écrite',
            'Examen final',
            'TP noté',
            'Exposé oral',
            'Composition'
        ];

        $createdCount = 0;

        // Créer des évaluations pour chaque enseignant
        foreach ($enseignants as $enseignant) {
            $matieres = $enseignant->getMatieres();
            
            if (count($matieres) === 0) {
                continue;
            }

            // Créer 1-2 évaluations par enseignant
            $nbEvaluations = rand(1, 2);
            
            for ($i = 0; $i < $nbEvaluations; $i++) {
                $evaluation = new Evaluation();
                
                // Choisir une matière au hasard parmi celles de l'enseignant
                $matiereArray = $matieres->toArray();
                $matiere = $matiereArray[array_rand($matiereArray)];
                
                // Choisir un type d'évaluation
                $typeEval = $typesEvaluations[array_rand($typesEvaluations)];
                
                // Générer une date dans les 30 derniers jours
                $dateEvaluation = new \DateTime('-' . rand(1, 30) . ' days');
                
                $evaluation->setNom($typeEval . ' - ' . $matiere->getNom());
                $evaluation->setDescription($typeEval . ' pour la matière ' . $matiere->getNom() . 
                    ' portant sur le chapitre ' . rand(1, 5) . ' du programme.');
                $evaluation->setDateEvaluation($dateEvaluation);
                $evaluation->setNoteMax(rand(15, 20));
                $evaluation->setCoefficient(rand(1, 3));
                $evaluation->setMatiere($matiere);
                $evaluation->setEnseignant($enseignant);
                $evaluation->setPeriodeAcademique($periodeActive);
                
                // Choisir un trimestre au hasard
                $trimestre = $trimestres[array_rand($trimestres)];
                $evaluation->setTrimestre($trimestre);
                
                $this->entityManager->persist($evaluation);
                $createdCount++;
                
                $output->writeln('<comment>Création: ' . $evaluation->getNom() . 
                    ' (Enseignant: ' . $enseignant->getNom() . ', Matière: ' . $matiere->getNom() . ')</comment>');
            }
        }

        $this->entityManager->flush();
        $output->writeln('<success>' . $createdCount . ' évaluations créées avec succès!</success>');

        return Command::SUCCESS;
    }
}
