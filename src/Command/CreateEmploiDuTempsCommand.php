<?php

namespace App\Command;

use App\Entity\EmploiDuTemps;
use App\Entity\Jour;
use App\Entity\Matiere;
use App\Entity\Filiere;
use App\Entity\Enseignant;
use App\Entity\Cours;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-emploi-du-temps',
    description: 'Créer des données de test pour l\'emploi du temps'
)]
class CreateEmploiDuTempsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>=== CRÉATION EMPLOI DU TEMPS DE TEST ===</info>');

        // Récupérer les entités nécessaires
        $filieres = $this->entityManager->getRepository(Filiere::class)->findAll();
        $enseignants = $this->entityManager->getRepository(Enseignant::class)->findAll();
        $matieres = $this->entityManager->getRepository(Matiere::class)->findAll();
        $jours = $this->entityManager->getRepository(Jour::class)->findAll();
        $cours = $this->entityManager->getRepository(Cours::class)->findAll();

        if (empty($filieres) || empty($enseignants) || empty($matieres) || empty($jours)) {
            $output->writeln('<error>Données manquantes: filières, enseignants, matières ou jours</error>');
            return Command::FAILURE;
        }

        $createdCount = 0;

        // Créer des emplois du temps pour chaque filière
        foreach ($filieres as $filiere) {
            $output->writeln('<comment>Création pour la filière: ' . $filiere->getNom() . '</comment>');
            
            // Récupérer les matières de cette filière
            $matieresFiliere = array_filter($matieres, function($matiere) use ($filiere) {
                return $matiere->getFiliere() && $matiere->getFiliere()->getId() === $filiere->getId();
            });

            // Créer un emploi du temps par jour pour cette filière
            $joursSemaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
            $creneaux = [
                ['08:00', '10:00'],
                ['10:15', '12:00'],
                ['14:00', '16:00'],
                ['16:15', '18:00']
            ];

            foreach ($joursSemaine as $jourNom) {
                // Trouver l'entité Jour correspondante
                $jour = null;
                foreach ($jours as $j) {
                    if ($j->getNom() === $jourNom) {
                        $jour = $j;
                        break;
                    }
                }

                if (!$jour) continue;

                // Créer 2-3 cours par jour
                $nbCoursJour = rand(2, 3);
                $creneauxUtilises = [];

                for ($i = 0; $i < $nbCoursJour && $i < count($creneaux); $i++) {
                    $creneauIndex = array_rand($creneaux);
                    if (in_array($creneauIndex, $creneauxUtilises)) continue;
                    
                    $creneauxUtilises[] = $creneauIndex;
                    $creneau = $creneaux[$creneauIndex];

                    // Choisir une matière au hasard pour cette filière
                    if (empty($matieresFiliere)) continue;
                    
                    $matiere = $matieresFiliere[array_rand($matieresFiliere)];
                    $enseignant = $matiere->getEnseignant();

                    if (!$enseignant) {
                        // Si la matière n'a pas d'enseignant, en prendre un au hasard
                        $enseignant = $enseignants[array_rand($enseignants)];
                    }

                    // Créer l'emploi du temps
                    $emploi = new EmploiDuTemps();
                    $emploi->setJour($jour);
                    $emploi->setFiliere($filiere);
                    $emploi->setMatiere($matiere);
                    $emploi->setEnseignant($enseignant);
                    $emploi->setHeureDebut(new \DateTime($creneau[0]));
                    $emploi->setHeureFin(new \DateTime($creneau[1]));

                    // Associer un cours si disponible
                    $coursFiliere = array_filter($cours, function($c) use ($filiere, $matiere) {
                        return $c->getMatiere() && $c->getMatiere()->getId() === $matiere->getId();
                    });

                    if (!empty($coursFiliere)) {
                        $emploi->setCours(array_values($coursFiliere)[0]);
                    }

                    $this->entityManager->persist($emploi);
                    $createdCount++;

                    $output->writeln('<info>  - Créé: ' . $matiere->getNom() . ' le ' . $jourNom . ' (' . $creneau[0] . '-' . $creneau[1] . ')</info>');
                }
            }
        }

        $this->entityManager->flush();
        $output->writeln('<success>' . $createdCount . ' emplois du temps créés avec succès!</success>');

        return Command::SUCCESS;
    }
}
