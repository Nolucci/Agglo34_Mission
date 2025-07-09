<?php

namespace App\Command;

use App\Security\AdminUserProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande pour créer l'utilisateur admin par défaut
 */
#[AsCommand(
    name: 'app:create-admin-user',
    description: 'Crée l\'utilisateur administrateur par défaut'
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private AdminUserProvider $adminUserProvider
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe pour l\'utilisateur admin')
            ->setHelp('Cette commande crée un utilisateur administrateur par défaut avec les identifiants admin@agglo34.local');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $password = $input->getArgument('password');

        if (strlen($password) < 8) {
            $io->error('Le mot de passe doit contenir au moins 8 caractères.');
            return Command::FAILURE;
        }

        try {
            if ($this->adminUserProvider->defaultAdminExists()) {
                $io->warning('L\'utilisateur admin existe déjà.');
                return Command::SUCCESS;
            }

            $user = $this->adminUserProvider->createDefaultAdminUser($password);
            $credentials = $this->adminUserProvider::getAdminCredentials();

            $io->success('Utilisateur administrateur créé avec succès !');
            $io->table(
                ['Propriété', 'Valeur'],
                [
                    ['Email', $credentials['email']],
                    ['Nom', $credentials['name']],
                    ['Nom d\'utilisateur LDAP', $credentials['username']],
                    ['Rôles', 'ROLE_ADMIN']
                ]
            );

            $io->note('Utilisez ces identifiants pour vous connecter en mode maintenance.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la création de l\'utilisateur admin : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}