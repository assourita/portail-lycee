<?php

namespace App\Command;

use App\Entity\Etudiant;
use App\Entity\Filiere;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fix-etudiants',
    description: 'Unifier les étudiants dans les bonnes filières'
)]
class FixEtudiantsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>=== UNIFICATION DES ÉTUDIANTS ===</info>');

        // Récupérer toutes les filières "Terminale Sciences Exactes"
        $filieresRepository = $this->entityManager->getRepository(Filiere::class);
        $filieresSciencesExactes = $filieresRepository->findBy(['nom' => 'Terminale Sciences Exactes']);
        
        $output->writeln('<comment>Filières "Terminale Sciences Exactes" trouvées: ' . count($filieresSciencesExactes) . '</comment>');
        
        if (count($filieresSciencesExactes) < 2) {
            $output->writeln('<info>Pas assez de filières à unifier.</info>');
            return Command::SUCCESS;
        }

        // Utiliser la première filière (ID 5) comme référence
        $filiereReference = $filieresSciencesExactes[0];
        $output->writeln('<info>Filière de référence: ID ' . $filiereReference->getId() . '</info>');

        // Déplacer tous les étudiants vers la filière de référence
        $etudiantsRepository = $this->entityManager->getRepository(Etudiant::class);
        $etudiants = $etudiantsRepository->findAll();
        
        $updatedCount = 0;
        foreach ($etudiants as $etudiant) {
            if ($etudiant->getFiliere() && 
                $etudiant->getFiliere()->getNom() === 'Terminale Sciences Exactes' &&
                $etudiant->getFiliere()->getId() !== $filiereReference->getId()) {
                
                $output->writeln('<comment>Déplacement: ' . $etudiant->getNom() . ' ' . $etudiant->getPrenom() . 
                    ' (Filière ' . $etudiant->getFiliere()->getId() . ' -> ' . $filiereReference->getId() . ')</comment>');
                
                $etudiant->setFiliere($filiereReference);
                $this->entityManager->persist($etudiant);
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
            $output->writeln('<success>' . $updatedCount . ' étudiants déplacés!</success>');
        } else {
            $output->writeln('<info>Aucun étudiant à déplacer.</info>');
        }

        return Command::SUCCESS;
    }
}
