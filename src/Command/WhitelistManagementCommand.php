<?php

namespace App\Command;

use App\Service\WhitelistService;
use App\Service\LdapTestService;
use App\Service\SettingsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:whitelist:manage',
    description: 'Gère la whitelist des utilisateurs LDAP'
)]
class WhitelistManagementCommand extends Command
{
    public function __construct(
        private WhitelistService $whitelistService,
        private LdapTestService $ldapTestService,
        private SettingsService $settingsService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action à effectuer (list, add, remove, activate, test)')
            ->addArgument('username', InputArgument::OPTIONAL, 'Nom d\'utilisateur LDAP')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Nom complet de l\'utilisateur')
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Email de l\'utilisateur')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Afficher tous les utilisateurs (actifs et inactifs)')
            ->setHelp('
Cette commande permet de gérer la whitelist des utilisateurs LDAP.

Actions disponibles:
  list      - Affiche la liste des utilisateurs dans la whitelist
  add       - Ajoute un utilisateur à la whitelist
  remove    - Désactive un utilisateur de la whitelist
  activate  - Réactive un utilisateur dans la whitelist
  test      - Teste l\'existence d\'un utilisateur dans LDAP

Exemples:
  php bin/console app:whitelist:manage list
  php bin/console app:whitelist:manage add jdupont --name="Jean Dupont" --email="jean.dupont@example.com"
  php bin/console app:whitelist:manage remove jdupont
  php bin/console app:whitelist:manage test jdupont
');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $username = $input->getArgument('username');

        switch ($action) {
            case 'list':
                return $this->listUsers($io, $input->getOption('all'));

            case 'add':
                if (!$username) {
                    $io->error('Le nom d\'utilisateur est requis pour l\'action "add"');
                    return Command::FAILURE;
                }
                return $this->addUser($io, $username, $input->getOption('name'), $input->getOption('email'));

            case 'remove':
                if (!$username) {
                    $io->error('Le nom d\'utilisateur est requis pour l\'action "remove"');
                    return Command::FAILURE;
                }
                return $this->removeUser($io, $username);

            case 'activate':
                if (!$username) {
                    $io->error('Le nom d\'utilisateur est requis pour l\'action "activate"');
                    return Command::FAILURE;
                }
                return $this->activateUser($io, $username);

            case 'test':
                if (!$username) {
                    $io->error('Le nom d\'utilisateur est requis pour l\'action "test"');
                    return Command::FAILURE;
                }
                return $this->testUser($io, $username);

            default:
                $io->error('Action non reconnue. Actions disponibles: list, add, remove, activate, test');
                return Command::FAILURE;
        }
    }

    private function listUsers(SymfonyStyle $io, bool $showAll): int
    {
        $users = $showAll ? $this->whitelistService->getAllUsers() : $this->whitelistService->getActiveUsers();

        if (empty($users)) {
            $io->info('Aucun utilisateur dans la whitelist');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                $user->getLdapUsername(),
                $user->getName() ?? '-',
                $user->getEmail() ?? '-',
                $user->isActive() ? 'Actif' : 'Désactivé',
                $user->getCreatedAt()->format('d/m/Y H:i'),
                $user->getCreatedBy() ? $user->getCreatedBy()->getName() : '-'
            ];
        }

        $io->table(
            ['Nom d\'utilisateur', 'Nom', 'Email', 'Statut', 'Créé le', 'Créé par'],
            $rows
        );

        $io->info(sprintf('Total: %d utilisateur(s)', count($users)));

        return Command::SUCCESS;
    }

    private function addUser(SymfonyStyle $io, string $username, ?string $name, ?string $email): int
    {
        try {
            // Vérifier si LDAP est activé et tester l'utilisateur
            if ($this->settingsService->getSettings()?->isLdapEnabled()) {
                $io->info('LDAP activé, vérification de l\'utilisateur...');

                $settings = $this->settingsService->getSettings();
                $ldapConfig = [
                    'host' => $settings->getLdapHost(),
                    'port' => $settings->getLdapPort(),
                    'search_dn' => $settings->getLdapSearchDn(),
                    'search_password' => $settings->getLdapSearchPassword(),
                    'base_dn' => $settings->getLdapBaseDn(),
                    'uid_key' => $settings->getLdapUidKey(),
                    'encryption' => $settings->getLdapEncryption()
                ];

                $testResult = $this->ldapTestService->checkUserExists($ldapConfig, $username);

                if (!$testResult['success']) {
                    $io->warning('Utilisateur non trouvé dans LDAP: ' . $testResult['message']);
                    if (!$io->confirm('Voulez-vous quand même l\'ajouter à la whitelist ?')) {
                        return Command::FAILURE;
                    }
                } else {
                    $io->success('Utilisateur trouvé dans LDAP');

                    // Récupérer les informations LDAP si disponibles
                    if (isset($testResult['user_attributes'])) {
                        $attributes = $testResult['user_attributes'];
                        $ldapName = $attributes['displayname'] ?? $attributes['cn'] ?? null;
                        $ldapEmail = $attributes['mail'] ?? null;

                        if (!$name && $ldapName) {
                            $name = $ldapName;
                            $io->info('Nom récupéré depuis LDAP: ' . $name);
                        }

                        if (!$email && $ldapEmail) {
                            $email = $ldapEmail;
                            $io->info('Email récupéré depuis LDAP: ' . $email);
                        }
                    }
                }
            }

            $whitelistEntry = $this->whitelistService->addUserToWhitelist($username, $name, $email);

            $io->success(sprintf('Utilisateur "%s" ajouté à la whitelist avec succès', $username));

            $io->table(
                ['Propriété', 'Valeur'],
                [
                    ['ID', $whitelistEntry->getId()],
                    ['Nom d\'utilisateur LDAP', $whitelistEntry->getLdapUsername()],
                    ['Nom', $whitelistEntry->getName() ?? '-'],
                    ['Email', $whitelistEntry->getEmail() ?? '-'],
                    ['Statut', $whitelistEntry->isActive() ? 'Actif' : 'Désactivé'],
                    ['Créé le', $whitelistEntry->getCreatedAt()->format('d/m/Y H:i')]
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'ajout: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function removeUser(SymfonyStyle $io, string $username): int
    {
        try {
            $success = $this->whitelistService->removeUserFromWhitelist($username);

            if ($success) {
                $io->success(sprintf('Utilisateur "%s" désactivé de la whitelist', $username));
                return Command::SUCCESS;
            } else {
                $io->error(sprintf('Utilisateur "%s" non trouvé dans la whitelist', $username));
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('Erreur lors de la désactivation: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function activateUser(SymfonyStyle $io, string $username): int
    {
        try {
            $whitelistEntry = $this->whitelistService->addUserToWhitelist($username);
            $io->success(sprintf('Utilisateur "%s" réactivé dans la whitelist', $username));
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la réactivation: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function testUser(SymfonyStyle $io, string $username): int
    {
        if (!$this->settingsService->getSettings()?->isLdapEnabled()) {
            $io->warning('LDAP n\'est pas activé');
            return Command::FAILURE;
        }

        try {
            $settings = $this->settingsService->getSettings();
            $ldapConfig = [
                'host' => $settings->getLdapHost(),
                'port' => $settings->getLdapPort(),
                'search_dn' => $settings->getLdapSearchDn(),
                'search_password' => $settings->getLdapSearchPassword(),
                'base_dn' => $settings->getLdapBaseDn(),
                'uid_key' => $settings->getLdapUidKey(),
                'encryption' => $settings->getLdapEncryption()
            ];

            $testResult = $this->ldapTestService->checkUserExists($ldapConfig, $username);

            if ($testResult['success']) {
                $io->success('Utilisateur trouvé dans LDAP');

                if (isset($testResult['user_attributes'])) {
                    $attributes = $testResult['user_attributes'];
                    $io->table(
                        ['Attribut', 'Valeur'],
                        [
                            ['DN', $testResult['user_dn'] ?? '-'],
                            ['Nom complet', $attributes['displayname'] ?? $attributes['cn'] ?? '-'],
                            ['Email', $attributes['mail'] ?? '-'],
                            ['Nom de compte', $attributes['samaccountname'] ?? '-'],
                            ['Prénom', $attributes['givenname'] ?? '-'],
                            ['Nom de famille', $attributes['sn'] ?? '-']
                        ]
                    );
                }

                // Vérifier si l'utilisateur est dans la whitelist
                $isWhitelisted = $this->whitelistService->isUserAuthorized($username);
                $io->info('Statut whitelist: ' . ($isWhitelisted ? 'Autorisé' : 'Non autorisé'));

                return Command::SUCCESS;
            } else {
                $io->error('Utilisateur non trouvé dans LDAP: ' . $testResult['message']);
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('Erreur lors du test LDAP: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}