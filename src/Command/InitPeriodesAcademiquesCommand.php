<?php

namespace App\Command;

use App\Entity\PeriodeAcademique;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-periodes-academiques',
    description: 'Initialiser les périodes académiques pour l\'année scolaire 2024-2025',
)]
class InitPeriodesAcademiquesCommand extends Command
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

        $periodesData = [
            [
                'periode' => '1er Trimestre',
                'debut' => '2024-09-01',
                'fin' => '2024-12-20',
                'active' => true
            ],
            [
                'periode' => '2ème Trimestre',
                'debut' => '2025-01-06',
                'fin' => '2025-03-28',
                'active' => false
            ],
            [
                'periode' => '3ème Trimestre',
                'debut' => '2025-04-07',
                'fin' => '2025-06-30',
                'active' => false
            ],
            [
                'periode' => '1er Semestre',
                'debut' => '2024-09-01',
                'fin' => '2025-01-31',
                'active' => false
            ],
            [
                'periode' => '2ème Semestre',
                'debut' => '2025-02-03',
                'fin' => '2025-06-30',
                'active' => false
            ],
        ];

        $periodeRepository = $this->entityManager->getRepository(PeriodeAcademique::class);
        $periodesCreees = 0;

        foreach ($periodesData as $data) {
            // Vérifier si la période existe déjà
            $periodeExistante = $periodeRepository->findOneBy([
                'anneeScolaire' => $anneeScolaire,
                'periode' => $data['periode']
            ]);

            if (!$periodeExistante) {
                $periode = new PeriodeAcademique();
                $periode->setAnneeScolaire($anneeScolaire);
                $periode->setPeriode($data['periode']);
                $periode->setDateDebut(new \DateTime($data['debut']));
                $periode->setDateFin(new \DateTime($data['fin']));
                $periode->setIsActive($data['active']);

                $this->entityManager->persist($periode);
                $periodesCreees++;
                
                $io->text(sprintf('Période "%s" créée', $data['periode']));
            } else {
                $io->text(sprintf('Période "%s" existe déjà', $data['periode']));
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d période(s) académique(s) initialisée(s) pour l\'année %s !', $periodesCreees, $anneeScolaire));
        $io->note('La première période (1er Trimestre) est activée par défaut.');

        return Command::SUCCESS;
    }
}
