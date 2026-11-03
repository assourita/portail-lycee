<?php

namespace App\Command;

use App\Entity\EmploiDuTemps;
use App\Entity\Filiere;
use App\Entity\Jour;
use App\Entity\Matiere;
use App\Entity\Enseignant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-emploi-du-temps',
    description: 'Teste la création d\'un emploi du temps',
)]
class TestEmploiDuTempsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test de création d\'emploi du temps');

        try {
            // Vérifier si la table existe
            $connection = $this->entityManager->getConnection();
            $schemaManager = $connection->createSchemaManager();
            $tables = $schemaManager->listTableNames();
            
            $io->text('Tables existantes :');
            foreach ($tables as $table) {
                $io->text('- ' . $table);
            }

            if (in_array('emploi_du_temps', $tables)) {
                $io->success('La table emploi_du_temps existe !');
                
                // Tester la création d'un emploi du temps
                $filiere = $this->entityManager->getRepository(Filiere::class)->findOneBy(['nom' => 'Terminale Sciences Exactes']);
                $jour = $this->entityManager->getRepository(Jour::class)->findOneBy(['nom' => 'Lundi']);
                $matiere = $this->entityManager->getRepository(Matiere::class)->findOneBy(['nom' => 'Mathématiques']);
                $enseignant = $this->entityManager->getRepository(Enseignant::class)->findOneBy([]);

                if ($filiere && $jour && $matiere && $enseignant) {
                    $emploi = new EmploiDuTemps();
                    $emploi->setFiliere($filiere);
                    $emploi->setJour($jour);
                    $emploi->setMatiere($matiere);
                    $emploi->setEnseignant($enseignant);
                    $emploi->setHeureDebut(new \DateTime('08:00:00'));
                    $emploi->setHeureFin(new \DateTime('09:30:00'));

                    $this->entityManager->persist($emploi);
                    $this->entityManager->flush();

                    $io->success('Emploi du temps créé avec succès !');
                } else {
                    $io->error('Données manquantes pour créer l\'emploi du temps');
                    if (!$filiere) $io->text('- Filière "Terminale Sciences Exactes" non trouvée');
                    if (!$jour) $io->text('- Jour "Lundi" non trouvé');
                    if (!$matiere) $io->text('- Matière "Mathématiques" non trouvée');
                    if (!$enseignant) $io->text('- Aucun enseignant trouvé');
                }
            } else {
                $io->error('La table emploi_du_temps n\'existe pas !');
                $io->text('Tentative de création...');
                
                // Créer la table manuellement
                $sql = "
                    CREATE TABLE emploi_du_temps (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        heure_debut TIME NOT NULL,
                        heure_fin TIME NOT NULL,
                        date DATE DEFAULT NULL,
                        jour_id INT NOT NULL,
                        matiere_id INT NOT NULL,
                        cours_id INT DEFAULT NULL,
                        filiere_id INT NOT NULL,
                        enseignant_id INT NOT NULL,
                        INDEX IDX_emploi_du_temps_jour (jour_id),
                        INDEX IDX_emploi_du_temps_matiere (matiere_id),
                        INDEX IDX_emploi_du_temps_cours (cours_id),
                        INDEX IDX_emploi_du_temps_filiere (filiere_id),
                        INDEX IDX_emploi_du_temps_enseignant (enseignant_id)
                    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
                ";
                
                $connection->executeStatement($sql);
                $io->success('Table emploi_du_temps créée manuellement !');
            }

        } catch (\Exception $e) {
            $io->error('Erreur : ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}