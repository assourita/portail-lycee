<?php

namespace App\Command;

use App\Entity\PeriodeAcademique;
use App\Repository\PeriodeAcademiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-periodes-academiques',
    description: 'Crée les trimestres et semestres d\'une année scolaire (par défaut, l\'année en cours)',
)]
class InitPeriodesAcademiquesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PeriodeAcademiqueRepository $periodeRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('annee', InputArgument::OPTIONAL, 'Année scolaire au format AAAA-AAAA (ex. 2026-2027)')
            ->addOption('activer', null, InputOption::VALUE_NONE, 'Active la période correspondant à la date du jour, en désactivant les autres');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $anneeScolaire = $input->getArgument('annee') ?? $this->anneeScolaireCourante();
        if (!preg_match('/^(\d{4})-(\d{4})$/', $anneeScolaire, $m) || (int) $m[2] !== (int) $m[1] + 1) {
            $io->error('L\'année doit être au format AAAA-AAAA, avec deux années consécutives (ex. 2026-2027).');

            return Command::INVALID;
        }
        $debut = (int) $m[1];
        $fin = $debut + 1;

        $periodesData = [
            ['1er Trimestre', "$debut-09-01", "$debut-12-20"],
            ['2ème Trimestre', "$fin-01-06", "$fin-03-28"],
            ['3ème Trimestre', "$fin-04-07", "$fin-06-30"],
            ['1er Semestre', "$debut-09-01", "$fin-01-31"],
            ['2ème Semestre', "$fin-02-03", "$fin-06-30"],
        ];

        $creees = 0;
        $periodes = [];
        foreach ($periodesData as [$nom, $dateDebut, $dateFin]) {
            $periode = $this->periodeRepository->findOneBy(['anneeScolaire' => $anneeScolaire, 'periode' => $nom]);
            if ($periode) {
                $io->text(sprintf('Période « %s » déjà présente', $nom));
            } else {
                $periode = (new PeriodeAcademique())
                    ->setAnneeScolaire($anneeScolaire)
                    ->setPeriode($nom)
                    ->setDateDebut(new \DateTime($dateDebut))
                    ->setDateFin(new \DateTime($dateFin))
                    ->setIsActive(false);
                $this->entityManager->persist($periode);
                $creees++;
                $io->text(sprintf('Période « %s » créée (%s au %s)', $nom, $dateDebut, $dateFin));
            }
            $periodes[] = $periode;
        }

        if ($input->getOption('activer') || !$this->periodeRepository->findActive()) {
            $aujourdhui = new \DateTime('today');
            $aActiver = $periodes[0];
            foreach (array_slice($periodes, 0, 3) as $trimestre) {
                if ($trimestre->getDateDebut() <= $aujourdhui && $aujourdhui <= $trimestre->getDateFin()) {
                    $aActiver = $trimestre;
                }
            }
            $this->periodeRepository->desactiverToutes();
            $aActiver->setIsActive(true);
            $io->note(sprintf('Période active : %s %s', $aActiver->getPeriode(), $anneeScolaire));
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d période(s) créée(s) pour l\'année %s.', $creees, $anneeScolaire));

        return Command::SUCCESS;
    }

    private function anneeScolaireCourante(): string
    {
        $annee = (int) date('Y');
        $debut = (int) date('n') >= 9 ? $annee : $annee - 1;

        return sprintf('%d-%d', $debut, $debut + 1);
    }
}
