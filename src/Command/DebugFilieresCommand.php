<?php

namespace App\Command;

use App\Entity\Etudiant;
use App\Entity\Cours;
use App\Entity\Matiere;
use App\Entity\Filiere;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:debug-filieres',
    description: 'Débogage des filières'
)]
class DebugFilieresCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>=== DÉBOGAGE FILIÈRES ===</info>');

        // 1. Vérifier toutes les filières
        $filieres = $this->entityManager->getRepository(Filiere::class)->findAll();
        $output->writeln('<comment>Filières trouvées: ' . count($filieres) . '</comment>');

        foreach ($filieres as $filiere) {
            $output->writeln('  - ID: ' . $filiere->getId() . ' | Nom: ' . $filiere->getNom());
        }

        // 2. Vérifier les filières des étudiants
        $etudiants = $this->entityManager->getRepository(Etudiant::class)->findAll();
        $output->writeln('<comment>Filières des étudiants:</comment>');

        foreach ($etudiants as $etudiant) {
            $output->writeln('  - ' . $etudiant->getNom() . ' ' . $etudiant->getPrenom() . 
                ' -> Filière ID: ' . ($etudiant->getFiliere() ? $etudiant->getFiliere()->getId() : 'NULL') . 
                ' | Nom: ' . ($etudiant->getFiliere() ? $etudiant->getFiliere()->getNom() : 'N/A'));
        }

        // 3. Vérifier les filières des cours (via matières)
        $cours = $this->entityManager->getRepository(Cours::class)->findAll();
        $output->writeln('<comment>Filières des cours (via matières):</comment>');

        foreach ($cours as $c) {
            $output->writeln('  - ' . $c->getTitre() . 
                ' -> Matière: ' . ($c->getMatiere() ? $c->getMatiere()->getNom() : 'N/A') . 
                ' -> Filière ID: ' . ($c->getMatiere() && $c->getMatiere()->getFiliere() ? $c->getMatiere()->getFiliere()->getId() : 'NULL') . 
                ' | Nom: ' . ($c->getMatiere() && $c->getMatiere()->getFiliere() ? $c->getMatiere()->getFiliere()->getNom() : 'N/A'));
        }

        return Command::SUCCESS;
    }
}
