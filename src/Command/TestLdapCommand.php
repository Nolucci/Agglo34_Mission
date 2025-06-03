<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Exception\ConnectionException;

#[AsCommand(
    name: 'app:test-ldap',
    description: 'Test la connexion au serveur LDAP',
)]
class TestLdapCommand extends Command
{
    private $host;
    private $port;
    private $encryption;
    private $baseDn;
    private $searchDn;
    private $searchPassword;
    private $uidKey;

    public function __construct(
        string $ldapHost,
        int $ldapPort,
        string $ldapEncryption,
        string $ldapBaseDn,
        string $ldapSearchDn,
        string $ldapSearchPassword,
        string $ldapUidKey
    ) {
        $this->host = $ldapHost;
        $this->port = $ldapPort;
        $this->encryption = $ldapEncryption;
        $this->baseDn = $ldapBaseDn;
        $this->searchDn = $ldapSearchDn;
        $this->searchPassword = $ldapSearchPassword;
        $this->uidKey = $ldapUidKey;

        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Test de connexion LDAP');
        
        // Affichage des paramètres de connexion (sans le mot de passe)
        $io->section('Paramètres de connexion');
        $io->table(
            ['Paramètre', 'Valeur'],
            [
                ['Host', $this->host],
                ['Port', $this->port],
                ['Encryption', $this->encryption],
                ['Base DN', $this->baseDn],
                ['Search DN', $this->searchDn],
                ['UID Key', $this->uidKey],
            ]
        );
        
        try {
            // Création de l'adaptateur LDAP
            $ldap = Ldap::create('ext_ldap', [
                'host' => $this->host,
                'port' => $this->port,
                'encryption' => $this->encryption,
                'options' => [
                    'protocol_version' => 3,
                    'referrals' => false,
                ],
            ]);
            
            $io->info('Connexion au serveur LDAP...');
            
            // Test de connexion avec bind
            try {
                $ldap->bind($this->searchDn, $this->searchPassword);
                $io->success('Connexion réussie au serveur LDAP avec les identifiants fournis.');
                
                // Test de recherche
                $io->info('Test de recherche LDAP...');
                
                $query = sprintf('(objectClass=person)');
                $search = $ldap->query($this->baseDn, $query, ['limit' => 5]);
                $results = $search->execute();
                
                if (count($results) > 0) {
                    $io->success(sprintf('Recherche réussie. %d utilisateurs trouvés.', count($results)));
                    
                    $tableData = [];
                    foreach ($results as $entry) {
                        $attrs = $entry->getAttributes();
                        $uid = $attrs[$this->uidKey][0] ?? 'N/A';
                        $cn = $attrs['cn'][0] ?? 'N/A';
                        $dn = $entry->getDn();
                        
                        $tableData[] = [$uid, $cn, $dn];
                    }
                    
                    $io->table(['UID', 'CN', 'DN'], $tableData);
                } else {
                    $io->warning('Aucun utilisateur trouvé avec la requête.');
                }
                
                return Command::SUCCESS;
            } catch (ConnectionException $e) {
                $io->error(sprintf('Erreur lors de la connexion au serveur LDAP : %s', $e->getMessage()));
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de l\'initialisation du client LDAP : %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}