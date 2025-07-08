<?php

namespace App\Command;

use App\Entity\Settings;
use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande pour initialiser les paramètres LDAP par défaut
 */
#[AsCommand(
    name: 'app:init-ldap-settings',
    description: 'Initialise les paramètres LDAP par défaut dans la base de données'
)]
class InitLdapSettingsCommand extends Command
{
    private SettingsRepository $settingsRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        SettingsRepository $settingsRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->settingsRepository = $settingsRepository;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initialisation des paramètres LDAP');

        // Vérifier si des paramètres existent déjà
        $settings = $this->settingsRepository->findOneBy([]);

        if (!$settings) {
            $io->info('Aucun paramètre trouvé, création des paramètres par défaut...');

            $settings = new Settings();
            $settings->setDarkTheme(false);
            $settings->setCrudEnabled(false);
            $settings->setDisplayMode('liste');
            $settings->setItemsPerPage(10);
            $settings->setAppName('Agglo34 Mission');
            $settings->setWelcomeMessage('Bienvenue sur l\'application Agglo34 Mission');
            $settings->setAlertThreshold(5);
            $settings->setFeatureEnabled(false);
            $settings->setMaintenanceMode(false);
            $settings->setMaintenanceMessage('Application en maintenance. Veuillez réessayer plus tard.');
        }

        // Initialiser les paramètres LDAP s'ils ne sont pas définis
        if ($settings->isLdapEnabled() === null) {
            $settings->setLdapEnabled(false); // LDAP désactivé par défaut
            $io->info('LDAP configuré comme désactivé par défaut');
        }

        if ($settings->getLdapPort() === null) {
            $settings->setLdapPort(389);
        }

        if ($settings->getLdapEncryption() === null) {
            $settings->setLdapEncryption('none');
        }

        if ($settings->getLdapUidKey() === null) {
            $settings->setLdapUidKey('nomcompte');
        }

        // Sauvegarder les paramètres
        $this->entityManager->persist($settings);
        $this->entityManager->flush();

        $io->success('Paramètres LDAP initialisés avec succès !');

        $io->section('État actuel des paramètres LDAP :');
        $io->table(
            ['Paramètre', 'Valeur'],
            [
                ['LDAP Activé', $settings->isLdapEnabled() ? 'Oui' : 'Non'],
                ['Serveur LDAP', $settings->getLdapHost() ?: 'Non défini'],
                ['Port LDAP', $settings->getLdapPort()],
                ['Chiffrement', $settings->getLdapEncryption()],
                ['Base DN', $settings->getLdapBaseDn() ?: 'Non défini'],
                ['Search DN', $settings->getLdapSearchDn() ?: 'Non défini'],
                ['Clé UID', $settings->getLdapUidKey()],
            ]
        );

        if (!$settings->isLdapEnabled()) {
            $io->note('LDAP est actuellement désactivé. L\'accès à l\'application se fait sans authentification.');
            $io->note('Pour activer LDAP, rendez-vous dans les paramètres administrateur.');
        }

        return Command::SUCCESS;
    }
}