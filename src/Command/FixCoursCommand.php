<?php

namespace App\Command;

use App\Entity\Cours;
use App\Entity\PeriodeAcademique;
use App\Repository\PeriodeAcademiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fix-cours',
    description: 'Corriger les cours avec des périodes invalides'
)]
class FixCoursCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PeriodeAcademiqueRepository $periodeRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>=== CORRECTION DES COURS ===</info>');

        // Récupérer la période active
        $periodeActive = $this->periodeRepository->findOneBy(['isActive' => true]);
        
        if (!$periodeActive) {
            $output->writeln('<error>Aucune période active trouvée!</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Période active: ' . $periodeActive->getPeriode() . ' ' . $periodeActive->getAnneeScolaire() . '</info>');

        // Récupérer tous les cours
        $coursRepository = $this->entityManager->getRepository(Cours::class);
        $cours = $coursRepository->findAll();

        $output->writeln('<comment>Cours à vérifier: ' . count($cours) . '</comment>');

        $fixedCount = 0;
        foreach ($cours as $c) {
            if ($c->getPeriodeAcademique() === null || $c->getPeriodeAcademique()->getId() === 0) {
                $output->writeln('<error>Cours invalide: ' . $c->getTitre() . ' (Période: ' . 
                    ($c->getPeriodeAcademique() ? $c->getPeriodeAcademique()->getId() : 'NULL') . ')</error>');
                
                // Assigner la période active
                $c->setPeriodeAcademique($periodeActive);
                $this->entityManager->persist($c);
                $fixedCount++;
            } else {
                $output->writeln('<info>Cours OK: ' . $c->getTitre() . ' (Période: ' . 
                    $c->getPeriodeAcademique()->getPeriode() . ')</info>');
            }
        }

        if ($fixedCount > 0) {
            $this->entityManager->flush();
            $output->writeln('<success>' . $fixedCount . ' cours corrigés!</success>');
        } else {
            $output->writeln('<info>Aucun cours à corriger.</info>');
        }

        return Command::SUCCESS;
    }
}
