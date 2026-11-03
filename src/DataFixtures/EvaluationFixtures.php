<?php

namespace App\DataFixtures;

use App\Entity\Evaluation;
use App\Entity\Enseignant;
use App\Entity\Matiere;
use App\Entity\Trimestre;
use App\Entity\PeriodeAcademique;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class EvaluationFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer les enseignants existants
        $enseignants = $manager->getRepository(Enseignant::class)->findAll();
        
        if (empty($enseignants)) {
            return;
        }

        // Récupérer les trimestres
        $trimestres = $manager->getRepository(Trimestre::class)->findAll();
        
        if (empty($trimestres)) {
            return;
        }

        // Récupérer la période active
        $periodeActive = $manager->getRepository(PeriodeAcademique::class)
            ->findOneBy(['isActive' => true]);

        if (!$periodeActive) {
            return;
        }

        // Types d'évaluations
        $typesEvaluations = [
            'Devoir surveillé',
            'Interrogation écrite',
            'Examen final',
            'TP noté',
            'Exposé oral',
            'Composition'
        ];

        // Créer des évaluations pour chaque enseignant
        foreach ($enseignants as $index => $enseignant) {
            $matieres = $enseignant->getMatieres();
            
            if (count($matieres) === 0) {
                continue;
            }

            // Créer 1-3 évaluations par enseignant
            $nbEvaluations = rand(1, 3);
            
            for ($i = 0; $i < $nbEvaluations; $i++) {
                $evaluation = new Evaluation();
                
                // Choisir une matière au hasard parmi celles de l'enseignant
                $matiere = $matieres[array_rand($matieres->toArray())];
                
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
                
                $manager->persist($evaluation);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
