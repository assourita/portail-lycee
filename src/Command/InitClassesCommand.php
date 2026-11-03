<?php

namespace App\Command;

use App\Entity\Classe;
use App\Entity\Trimestre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-classes',
    description: 'Initialiser les classes (10ème, 11ème, Terminale) avec leurs trimestres',
)]
class InitClassesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $anneeScolaire = '2024-2025';

        $classesData = [
            [
                'nom' => '10ème',
                'description' => 'Classe de 10ème année (Seconde)',
                'trimestres' => [
                    ['nom' => '1er Trimestre', 'debut' => '2024-09-01', 'fin' => '2024-12-20'],
                    ['nom' => '2ème Trimestre', 'debut' => '2025-01-06', 'fin' => '2025-03-28'],
                    ['nom' => '3ème Trimestre', 'debut' => '2025-04-07', 'fin' => '2025-06-30'],
                    ['nom' => '1er Semestre', 'debut' => '2024-09-01', 'fin' => '2025-01-31'],
                    ['nom' => '2ème Semestre', 'debut' => '2025-02-03', 'fin' => '2025-06-30'],
                ],
            ],
            [
                'nom' => '11ème',
                'description' => 'Classe de 11ème année (Première)',
                'trimestres' => [
                    ['nom' => '1er Trimestre', 'debut' => '2024-09-01', 'fin' => '2024-12-20'],
                    ['nom' => '2ème Trimestre', 'debut' => '2025-01-06', 'fin' => '2025-03-28'],
                    ['nom' => '3ème Trimestre', 'debut' => '2025-04-07', 'fin' => '2025-06-30'],
                    ['nom' => '1er Semestre', 'debut' => '2024-09-01', 'fin' => '2025-01-31'],
                    ['nom' => '2ème Semestre', 'debut' => '2025-02-03', 'fin' => '2025-06-30'],
                ],
            ],
            [
                'nom' => 'Terminale',
                'description' => 'Classe de Terminale',
                'trimestres' => [
                    ['nom' => '1er Trimestre', 'debut' => '2024-09-01', 'fin' => '2024-12-20'],
                    ['nom' => '2ème Trimestre', 'debut' => '2025-01-06', 'fin' => '2025-03-28'],
                    ['nom' => '3ème Trimestre', 'debut' => '2025-04-07', 'fin' => '2025-06-15'], // Plus tôt pour les examens
                    ['nom' => '1er Semestre', 'debut' => '2024-09-01', 'fin' => '2025-01-31'],
                    ['nom' => '2ème Semestre', 'debut' => '2025-02-03', 'fin' => '2025-06-15'],
                ],
            ],
        ];

        $classeRepository = $this->entityManager->getRepository(Classe::class);
        $trimestreRepository = $this->entityManager->getRepository(Trimestre::class);

        foreach ($classesData as $data) {
            // Vérifier si la classe existe déjà
            $classe = $classeRepository->findOneBy(['nom' => $data['nom']]);

            if (!$classe) {
                $classe = new Classe();
                $classe->setNom($data['nom']);
                $classe->setDescription($data['description']);
                $this->entityManager->persist($classe);
                $io->success(sprintf('Classe "%s" créée avec succès !', $data['nom']));
            } else {
                $classe->setDescription($data['description']);
                $io->info(sprintf('Classe "%s" mise à jour !', $data['nom']));
            }

            // Créer ou mettre à jour les trimestres
            foreach ($data['trimestres'] as $trimestreData) {
                $trimestre = $trimestreRepository->findOneBy([
                    'classe' => $classe,
                    'nom' => $trimestreData['nom'],
                    'anneeScolaire' => $anneeScolaire
                ]);

                if (!$trimestre) {
                    $trimestre = new Trimestre();
                    $trimestre->setClasse($classe);
                    $trimestre->setNom($trimestreData['nom']);
                    $trimestre->setAnneeScolaire($anneeScolaire);
                    $this->entityManager->persist($trimestre);
                    $io->text(sprintf('  → Trimestre "%s" créé', $trimestreData['nom']));
                } else {
                    $io->text(sprintf('  → Trimestre "%s" mis à jour', $trimestreData['nom']));
                }

                $trimestre->setDateDebut(new \DateTime($trimestreData['debut']));
                $trimestre->setDateFin(new \DateTime($trimestreData['fin']));
            }
        }

        $this->entityManager->flush();

        $io->success('Toutes les classes et trimestres ont été initialisés avec succès !');

        return Command::SUCCESS;
    }
}

