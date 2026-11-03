<?php

namespace App\Command;

use App\Entity\Matiere;
use App\Entity\Filiere;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateMatieresCommand extends Command
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
            ->setName('app:create-matieres')
            ->setDescription('Crée les matières pour chaque filière');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Matières communes à toutes les filières
        $matieresCommunes = [
            ['nom' => 'Français', 'coefficient' => 4],
            ['nom' => 'Anglais', 'coefficient' => 3],
            ['nom' => 'Histoire-Géographie', 'coefficient' => 3],
            ['nom' => 'Éducation Civique', 'coefficient' => 2],
            ['nom' => 'Éducation Physique et Sportive', 'coefficient' => 2]
        ];

        // Matières spécifiques par filière
        $matieresParFiliere = [
            '10ème Générale' => [
                ['nom' => 'Mathématiques', 'coefficient' => 4],
                ['nom' => 'Physique-Chimie', 'coefficient' => 3],
                ['nom' => 'Sciences de la Vie et de la Terre', 'coefficient' => 3],
                ['nom' => 'Technologie', 'coefficient' => 2]
            ],
            '11ème Sciences' => [
                ['nom' => 'Mathématiques', 'coefficient' => 5],
                ['nom' => 'Physique-Chimie', 'coefficient' => 4],
                ['nom' => 'Sciences de la Vie et de la Terre', 'coefficient' => 4],
                ['nom' => 'Informatique', 'coefficient' => 2]
            ],
            '11ème Lettres' => [
                ['nom' => 'Littérature', 'coefficient' => 5],
                ['nom' => 'Philosophie', 'coefficient' => 4],
                ['nom' => 'Langue Vivante 2', 'coefficient' => 3],
                ['nom' => 'Latin', 'coefficient' => 2]
            ],
            '11ème Économique et Social' => [
                ['nom' => 'Mathématiques', 'coefficient' => 4],
                ['nom' => 'Sciences Économiques et Sociales', 'coefficient' => 5],
                ['nom' => 'Histoire-Géographie', 'coefficient' => 4],
                ['nom' => 'Langue Vivante 2', 'coefficient' => 3]
            ],
            'Terminale Sciences Exactes' => [
                ['nom' => 'Mathématiques', 'coefficient' => 6],
                ['nom' => 'Physique', 'coefficient' => 5],
                ['nom' => 'Chimie', 'coefficient' => 4],
                ['nom' => 'Informatique', 'coefficient' => 3]
            ],
            'Terminale Sciences Expérimentales' => [
                ['nom' => 'Mathématiques', 'coefficient' => 5],
                ['nom' => 'Physique-Chimie', 'coefficient' => 5],
                ['nom' => 'Sciences de la Vie et de la Terre', 'coefficient' => 6],
                ['nom' => 'Informatique', 'coefficient' => 2]
            ],
            'Terminale Économique' => [
                ['nom' => 'Mathématiques', 'coefficient' => 5],
                ['nom' => 'Sciences Économiques et Sociales', 'coefficient' => 6],
                ['nom' => 'Histoire-Géographie', 'coefficient' => 4],
                ['nom' => 'Langue Vivante 2', 'coefficient' => 3]
            ],
            'Terminale Lettres' => [
                ['nom' => 'Littérature', 'coefficient' => 6],
                ['nom' => 'Philosophie', 'coefficient' => 5],
                ['nom' => 'Langue Vivante 2', 'coefficient' => 4],
                ['nom' => 'Latin', 'coefficient' => 3]
            ],
            'Terminale Arts' => [
                ['nom' => 'Arts Plastiques', 'coefficient' => 6],
                ['nom' => 'Histoire de l\'Art', 'coefficient' => 4],
                ['nom' => 'Philosophie', 'coefficient' => 4],
                ['nom' => 'Langue Vivante 2', 'coefficient' => 3]
            ],
            'Terminale Social' => [
                ['nom' => 'Sciences Sociales', 'coefficient' => 6],
                ['nom' => 'Philosophie', 'coefficient' => 4],
                ['nom' => 'Histoire-Géographie', 'coefficient' => 4],
                ['nom' => 'Langue Vivante 2', 'coefficient' => 3]
            ]
        ];

        $repository = $this->entityManager->getRepository(Filiere::class);

        foreach ($matieresParFiliere as $nomFiliere => $matieres) {
            $filiere = $repository->findOneBy(['nom' => $nomFiliere]);
            
            if (!$filiere) {
                $io->warning("Filière non trouvée : $nomFiliere");
                continue;
            }

            // Ajouter les matières communes
            foreach ($matieresCommunes as $matiereCommune) {
                $matiere = new Matiere();
                $matiere->setNom($matiereCommune['nom']);
                $matiere->setCoeficient($matiereCommune['coefficient']);
                $matiere->setFiliere($filiere);
                $this->entityManager->persist($matiere);
            }

            // Ajouter les matières spécifiques
            foreach ($matieres as $matiereSpecifique) {
                $matiere = new Matiere();
                $matiere->setNom($matiereSpecifique['nom']);
                $matiere->setCoeficient($matiereSpecifique['coefficient']);
                $matiere->setFiliere($filiere);
                $this->entityManager->persist($matiere);
            }
        }

        $this->entityManager->flush();

        $io->success('Les matières ont été créées avec succès pour chaque filière !');

        return Command::SUCCESS;
    }
} 