<?php

namespace App\Command;

use App\Entity\Matiere;
use App\Entity\Enseignant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AssignMatieresToEnseignantsCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:assign-matieres-to-enseignants')
            ->setDescription('Associe les matières aux enseignants');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer tous les enseignants
        $enseignants = $this->entityManager->getRepository(Enseignant::class)->findAll();
        
        if (empty($enseignants)) {
            $io->error('Aucun enseignant trouvé dans la base de données.');
            return Command::FAILURE;
        }

        // Récupérer toutes les matières
        $matieres = $this->entityManager->getRepository(Matiere::class)->findAll();

        if (empty($matieres)) {
            $io->error('Aucune matière trouvée dans la base de données.');
            return Command::FAILURE;
        }

        // Associer les matières aux enseignants en fonction de leur spécialité
        foreach ($enseignants as $enseignant) {
            $specialite = strtolower($enseignant->getSpecialite());
            
            foreach ($matieres as $matiere) {
                $nomMatiere = strtolower($matiere->getNom());
                
                // Vérifier si la matière correspond à la spécialité de l'enseignant
                if ($this->matiereCorrespondSpecialite($nomMatiere, $specialite)) {
                    $matiere->setEnseignant($enseignant);
                    $this->entityManager->persist($matiere);
                }
            }
        }

        $this->entityManager->flush();

        $io->success('Les matières ont été associées aux enseignants avec succès !');

        return Command::SUCCESS;
    }

    private function matiereCorrespondSpecialite(string $nomMatiere, string $specialite): bool
    {
        // Tableau de correspondance entre les matières et les spécialités
        $correspondances = [
            'mathématiques' => ['mathématiques', 'sciences exactes', 'physique'],
            'physique' => ['physique', 'sciences exactes', 'sciences expérimentales'],
            'chimie' => ['chimie', 'sciences exactes', 'sciences expérimentales'],
            'sciences de la vie et de la terre' => ['sciences de la vie et de la terre', 'sciences expérimentales', 'biologie'],
            'français' => ['français', 'lettres', 'littérature'],
            'anglais' => ['anglais', 'langues', 'lettres'],
            'histoire-géographie' => ['histoire', 'géographie', 'sciences sociales'],
            'philosophie' => ['philosophie', 'lettres', 'sciences sociales'],
            'sciences économiques et sociales' => ['économie', 'sociologie', 'sciences sociales'],
            'informatique' => ['informatique', 'technologie', 'sciences exactes'],
            'arts plastiques' => ['arts', 'arts plastiques', 'histoire de l\'art'],
            'latin' => ['latin', 'lettres', 'langues anciennes']
        ];

        // Vérifier si la spécialité correspond à une des matières
        foreach ($correspondances as $matiere => $specialites) {
            if (in_array($specialite, $specialites) && strpos($nomMatiere, $matiere) !== false) {
                return true;
            }
        }

        return false;
    }
} 