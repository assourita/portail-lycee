<?php

namespace App\Command;

use App\Entity\Filiere;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateFilieresCommand extends Command
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
            ->setName('app:create-filieres')
            ->setDescription('Crée les filières du lycée');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filieres = [
            // 10ème Générale
            '10ème Générale',
            
            // 11ème
            '11ème Sciences',
            '11ème Lettres',
            '11ème Économique et Social',
            
            // Terminale
            'Terminale Sciences Exactes',
            'Terminale Sciences Expérimentales',
            'Terminale Économique',
            'Terminale Lettres',
            'Terminale Arts',
            'Terminale Social'
        ];

        foreach ($filieres as $nomFiliere) {
            $filiere = new Filiere();
            $filiere->setNom($nomFiliere);
            $this->entityManager->persist($filiere);
        }

        $this->entityManager->flush();

        $io->success('Les filières ont été créées avec succès !');

        return Command::SUCCESS;
    }
} 