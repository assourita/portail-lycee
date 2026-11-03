<?php

namespace App\Command;

use App\Entity\Bulletin;
use App\Entity\Classe;
use App\Entity\Etudiant;
use App\Entity\Trimestre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-test-bulletins',
    description: 'Générer des bulletins de test pour tous les étudiants',
)]
class GenerateTestBulletinsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer tous les étudiants
        $etudiants = $this->entityManager->getRepository(Etudiant::class)->findAll();
        
        if (empty($etudiants)) {
            $io->error('Aucun étudiant trouvé. Créez d\'abord des étudiants.');
            return Command::FAILURE;
        }

        // Récupérer le premier trimestre disponible
        $trimestre = $this->entityManager->getRepository(Trimestre::class)->findOneBy([]);
        
        if (!$trimestre) {
            $io->error('Aucun trimestre trouvé. Exécutez d\'abord: php bin/console app:init-classes');
            return Command::FAILURE;
        }

        $bulletinsCrees = 0;

        foreach ($etudiants as $etudiant) {
            // Vérifier si un bulletin existe déjà
            $existingBulletin = $this->entityManager->getRepository(Bulletin::class)->findOneBy([
                'etudiant' => $etudiant,
                'periode' => $trimestre->getNom(),
                'anneeScolaire' => $trimestre->getAnneeScolaire()
            ]);

            if (!$existingBulletin) {
                $bulletin = new Bulletin();
                $bulletin->setEtudiant($etudiant);
                $bulletin->setPeriode($trimestre->getNom());
                $bulletin->setAnneeScolaire($trimestre->getAnneeScolaire());
                $bulletin->setDateCreation(new \DateTime());
                $bulletin->setCommentaire('Bulletin de test généré automatiquement.');

                $this->entityManager->persist($bulletin);
                $bulletinsCrees++;
                
                $io->text(sprintf('Bulletin créé pour %s %s', $etudiant->getNom(), $etudiant->getPrenom()));
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d bulletins de test créés avec succès !', $bulletinsCrees));
        $io->note('Vous pouvez maintenant accéder à: http://localhost:8000/admin/bulletins/');

        return Command::SUCCESS;
    }
}
