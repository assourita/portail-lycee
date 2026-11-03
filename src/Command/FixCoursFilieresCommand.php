<?php

namespace App\Command;

use App\Entity\Cours;
use App\Entity\Matiere;
use App\Entity\Filiere;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fix-cours-filieres',
    description: 'Unifier les cours dans les bonnes filières'
)]
class FixCoursFilieresCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>=== UNIFICATION DES COURS ===</info>');

        // Récupérer toutes les filières "Terminale Sciences Exactes"
        $filieresRepository = $this->entityManager->getRepository(Filiere::class);
        $filieresSciencesExactes = $filieresRepository->findBy(['nom' => 'Terminale Sciences Exactes']);
        
        $output->writeln('<comment>Filières "Terminale Sciences Exactes" trouvées: ' . count($filieresSciencesExactes) . '</comment>');
        
        if (count($filieresSciencesExactes) < 2) {
            $output->writeln('<info>Pas assez de filières à unifier.</info>');
            return Command::SUCCESS;
        }

        // Utiliser la première filière (ID 5) comme référence
        $filiereReference = $filieresSciencesExactes[0];
        $output->writeln('<info>Filière de référence: ID ' . $filiereReference->getId() . '</info>');

        // Déplacer toutes les matières vers la filière de référence
        $matieresRepository = $this->entityManager->getRepository(Matiere::class);
        $matieres = $matieresRepository->findAll();
        
        $updatedMatieresCount = 0;
        foreach ($matieres as $matiere) {
            if ($matiere->getFiliere() && 
                $matiere->getFiliere()->getNom() === 'Terminale Sciences Exactes' &&
                $matiere->getFiliere()->getId() !== $filiereReference->getId()) {
                
                $output->writeln('<comment>Déplacement matière: ' . $matiere->getNom() . 
                    ' (Filière ' . $matiere->getFiliere()->getId() . ' -> ' . $filiereReference->getId() . ')</comment>');
                
                $matiere->setFiliere($filiereReference);
                $this->entityManager->persist($matiere);
                $updatedMatieresCount++;
            }
        }

        if ($updatedMatieresCount > 0) {
            $this->entityManager->flush();
            $output->writeln('<success>' . $updatedMatieresCount . ' matières déplacées!</success>');
        } else {
            $output->writeln('<info>Aucune matière à déplacer.</info>');
        }

        return Command::SUCCESS;
    }
}
