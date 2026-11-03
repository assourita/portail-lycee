<?php

namespace App\Command;

use App\Entity\Admin;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un compte administrateur (utile pour la première installation)',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('identifiant', InputArgument::REQUIRED, 'Identifiant de connexion')
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email')
            ->addArgument('nom', InputArgument::OPTIONAL, 'Nom', 'Administrateur')
            ->addArgument('prenom', InputArgument::OPTIONAL, 'Prénom', 'Principal');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $identifiant = $input->getArgument('identifiant');

        if ($this->entityManager->getRepository(User::class)->findOneBy(['username' => $identifiant])) {
            $io->error(sprintf('L\'identifiant « %s » est déjà utilisé.', $identifiant));

            return Command::FAILURE;
        }

        $motDePasse = $io->askHidden('Mot de passe (8 caractères minimum)', function (?string $valeur) {
            if (mb_strlen((string) $valeur) < 8) {
                throw new \RuntimeException('Le mot de passe doit contenir au moins 8 caractères.');
            }

            return $valeur;
        });

        $user = (new User())
            ->setUsername($identifiant)
            ->setEmail($input->getArgument('email'))
            ->setRoles(['ROLE_ADMIN'])
            ->setIsActivate(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, $motDePasse));

        $admin = (new Admin())
            ->setNom($input->getArgument('nom'))
            ->setPrenom($input->getArgument('prenom'))
            ->setEmail($input->getArgument('email'))
            ->setUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success(sprintf('Administrateur « %s » créé. Connectez-vous sur /login.', $identifiant));

        return Command::SUCCESS;
    }
}
