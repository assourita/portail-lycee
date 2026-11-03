<?php

namespace App\Command;

use App\Entity\Jour;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-jours',
    description: 'Initialise les jours de la semaine avec le bon ordre',
)]
class InitJoursCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $jours = [
            ['nom' => 'Lundi', 'ordre' => 1],
            ['nom' => 'Mardi', 'ordre' => 2],
            ['nom' => 'Mercredi', 'ordre' => 3],
            ['nom' => 'Jeudi', 'ordre' => 4],
            ['nom' => 'Vendredi', 'ordre' => 5],
            ['nom' => 'Samedi', 'ordre' => 6],
            ['nom' => 'Dimanche', 'ordre' => 7],
        ];

        $io->title('Initialisation des jours de la semaine');

        foreach ($jours as $jourData) {
            // Vérifier si le jour existe déjà
            $existingJour = $this->entityManager->getRepository(Jour::class)
                ->findOneBy(['nom' => $jourData['nom']]);

            if ($existingJour) {
                // Mettre à jour l'ordre si nécessaire
                if ($existingJour->getOrdre() !== $jourData['ordre']) {
                    $existingJour->setOrdre($jourData['ordre']);
                    $this->entityManager->flush();
                    $io->text(sprintf('✓ Ordre mis à jour pour %s (ordre: %d)', $jourData['nom'], $jourData['ordre']));
                } else {
                    $io->text(sprintf('- %s existe déjà avec le bon ordre', $jourData['nom']));
                }
            } else {
                // Créer le nouveau jour
                $jour = new Jour();
                $jour->setNom($jourData['nom']);
                $jour->setOrdre($jourData['ordre']);
                
                $this->entityManager->persist($jour);
                $this->entityManager->flush();
                
                $io->text(sprintf('✓ Créé %s (ordre: %d)', $jourData['nom'], $jourData['ordre']));
            }
        }

        $io->success('Initialisation des jours terminée avec succès !');

        return Command::SUCCESS;
    }
}