<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Ldap\Ldap;

class TestLdapCommand extends Command
{
    public static function getDefaultName(): string
    {
        return 'app:test-ldap';
    }

    public static function getDefaultDescription(): string
    {
        return 'Test la connexion LDAP';
    }

    private string $ldapHost;
    private int $ldapPort;
    private string $ldapEncryption;
    private string $ldapBaseDn;
    private string $ldapSearchDn;
    private string $ldapSearchPassword;
    private string $ldapUidKey;

    public function __construct(
        string $ldapHost,
        int $ldapPort,
        string $ldapEncryption,
        string $ldapBaseDn,
        string $ldapSearchDn,
        string $ldapSearchPassword,
        string $ldapUidKey
    ) {
        parent::__construct();
        $this->ldapHost = $ldapHost;
        $this->ldapPort = $ldapPort;
        $this->ldapEncryption = $ldapEncryption;
        $this->ldapBaseDn = $ldapBaseDn;
        $this->ldapSearchDn = $ldapSearchDn;
        $this->ldapSearchPassword = $ldapSearchPassword;
        $this->ldapUidKey = $ldapUidKey;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $ldap = Ldap::create('ext_ldap', [
                'host' => $this->ldapHost,
                'port' => $this->ldapPort,
                'encryption' => $this->ldapEncryption,
                'options' => [
                    'protocol_version' => 3,
                    'referrals' => false
                ]
            ]);

            $io->info('Tentative de connexion au serveur LDAP...');
            $ldap->bind($this->ldapSearchDn, $this->ldapSearchPassword);
            $io->success('Connexion LDAP réussie !');

            $io->info('Test de recherche LDAP...');

            // Test 1 : Recherche d'un utilisateur spécifique
            $testUsername = 'nathanfranceskin';
            $query = sprintf('(&(objectClass=person)(%s=%s))', $this->ldapUidKey, $testUsername);

            $io->info(sprintf('Test 1 - Recherche utilisateur spécifique:'));
            $io->info(sprintf('Base DN: %s', $this->ldapBaseDn));
            $io->info(sprintf('Filtre: %s', $query));

            $search = $ldap->query($this->ldapBaseDn, $query);
            $results = $search->execute();

            if ($results->count() > 0) {
                $io->success(sprintf('Utilisateur "%s" trouvé!', $testUsername));
                $user = $results[0];
                $io->info('Détails de l\'utilisateur:');
                foreach ($user->getAttributes() as $key => $value) {
                    if (!in_array($key, ['thumbnailPhoto', 'jpegPhoto', 'userPassword'])) {
                        $io->info(sprintf('- %s: %s', $key, implode(', ', $value)));
                    }
                }
            } else {
                $io->warning(sprintf('Utilisateur "%s" non trouvé', $testUsername));
            }

            // Test 2 : Recherche globale
            $io->info("\nTest 2 - Recherche globale:");
            $query = '(objectClass=person)';
            $io->info(sprintf('Filtre: %s', $query));

            $search = $ldap->query($this->ldapBaseDn, $query);
            $results = $search->execute();

            if ($results->count() > 0) {
                $io->success(sprintf('Recherche LDAP réussie ! %d utilisateurs trouvés.', $results->count()));
                $firstUser = $results[0];
                $io->info('Premier utilisateur trouvé :');
                foreach ($firstUser->getAttributes() as $key => $value) {
                    if ($key !== 'thumbnailPhoto' && $key !== 'jpegPhoto') {
                        $io->info(sprintf('- %s: %s', $key, implode(', ', $value)));
                    }
                }
            } else {
                $io->warning('Aucun utilisateur trouvé. Vérifiez le Base DN et le filtre de recherche.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Erreur LDAP : %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}