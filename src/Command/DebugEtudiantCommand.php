<?php

namespace App\Command;

use App\Entity\Etudiant;
use App\Entity\Cours;
use App\Entity\Matiere;
use App\Entity\Filiere;
use App\Entity\PeriodeAcademique;
use App\Repository\CoursRepository;
use App\Repository\EtudiantRepository;
use App\Repository\PeriodeAcademiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:debug-etudiant',
    description: 'Débogage des données étudiant-cours'
)]
class DebugEtudiantCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EtudiantRepository $etudiantRepository,
        private CoursRepository $coursRepository,
        private PeriodeAcademiqueRepository $periodeRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>=== DÉBOGAGE ÉTUDIANT-COURS ===</info>');

        // 1. Vérifier les étudiants
        $etudiants = $this->etudiantRepository->findAll();
        $output->writeln('<comment>Étudiants trouvés: ' . count($etudiants) . '</comment>');

        foreach ($etudiants as $etudiant) {
            $output->writeln('  - ' . $etudiant->getNom() . ' ' . $etudiant->getPrenom() . 
                ' (Filière: ' . ($etudiant->getFiliere() ? $etudiant->getFiliere()->getNom() : 'N/A') . ')');
        }

        // 2. Vérifier les périodes académiques
        $periodes = $this->periodeRepository->findAll();
        $output->writeln('<comment>Périodes académiques: ' . count($periodes) . '</comment>');
        
        $periodeActive = null;
        foreach ($periodes as $periode) {
            $output->writeln('  - ' . $periode->getPeriode() . ' ' . $periode->getAnneeScolaire() . 
                ' (Active: ' . ($periode->isActive() ? 'Oui' : 'Non') . ')');
            if ($periode->isActive()) {
                $periodeActive = $periode;
            }
        }

        // 3. Vérifier les cours
        $cours = $this->coursRepository->findAll();
        $output->writeln('<comment>Cours trouvés: ' . count($cours) . '</comment>');

        foreach ($cours as $c) {
            $output->writeln('  - ' . $c->getTitre() . 
                ' (Matière: ' . ($c->getMatiere() ? $c->getMatiere()->getNom() : 'N/A') . 
                ', Filière: ' . ($c->getMatiere() && $c->getMatiere()->getFiliere() ? $c->getMatiere()->getFiliere()->getNom() : 'N/A') . 
                ', Période: ' . ($c->getPeriodeAcademique() ? $c->getPeriodeAcademique()->getPeriode() : 'N/A') . ')');
        }

        // 4. Test pour un étudiant spécifique
        if (!empty($etudiants) && $periodeActive) {
            $etudiantTest = $etudiants[0];
            $output->writeln('<info>=== TEST POUR: ' . $etudiantTest->getNom() . ' ===</info>');
            
            $coursEtudiant = $this->coursRepository->findByFiliereAndPeriode(
                $etudiantTest->getFiliere(), 
                $periodeActive
            );
            
            $output->writeln('<comment>Cours pour cet étudiant (période active): ' . count($coursEtudiant) . '</comment>');
            
            if (empty($coursEtudiant)) {
                $output->writeln('<error>Aucun cours trouvé! Test sans période...</error>');
                $coursEtudiantSansPeriode = $this->coursRepository->findByFiliere($etudiantTest->getFiliere());
                $output->writeln('<comment>Cours pour cet étudiant (sans période): ' . count($coursEtudiantSansPeriode) . '</comment>');
            }
        }

        return Command::SUCCESS;
    }
}
