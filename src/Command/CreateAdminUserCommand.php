<?php

namespace App\Command;

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
    name: 'app:create-admin-user',
    description: 'Crée un utilisateur administrateur',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::OPTIONAL, 'Nom d\'utilisateur')
            ->addArgument('email', InputArgument::OPTIONAL, 'Adresse email')
            ->addArgument('password', InputArgument::OPTIONAL, 'Mot de passe')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        // Si les arguments ne sont pas fournis, les demander interactivement
        if (!$username) {
            $username = $io->ask('Nom d\'utilisateur', 'admin');
        }

        if (!$email) {
            $email = $io->ask('Adresse email', 'admin@agglo34.local');
        }

        if (!$password) {
            $password = $io->askHidden('Mot de passe');
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser) {
            $io->warning(sprintf('L\'utilisateur "%s" existe déjà.', $username));

            if ($io->confirm('Voulez-vous mettre à jour cet utilisateur ?', false)) {
                $user = $existingUser;
            } else {
                return Command::SUCCESS;
            }
        } else {
            $user = new User();
        }

        // Configuration de l'utilisateur
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);

        // Hashage du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Sauvegarde en base de données
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Utilisateur administrateur "%s" créé avec succès !', $username));

        return Command::SUCCESS;
    }
}