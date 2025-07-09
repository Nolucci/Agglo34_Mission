<?php

namespace App\Service;

use App\Entity\Settings;
use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Service pour gérer les paramètres de l'application
 */
class SettingsService
{
    public function __construct(
        private SettingsRepository $settingsRepository,
        private ParameterBagInterface $parameterBag,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Récupère les paramètres de l'application
     */
    public function getSettings(): ?\App\Entity\Settings
    {
        return $this->settingsRepository->findOneBy([]);
    }

    /**
     * Vérifie si le mode maintenance est activé
     */
    public function isMaintenanceMode(): bool
    {
        $settings = $this->getSettings();
        return $settings ? $settings->isMaintenanceMode() : false;
    }

    /**
     * Récupère les paramètres LDAP depuis la base de données ou les variables d'environnement
     */
    public function getLdapConfiguration(): array
    {
        $settings = $this->getSettings();

        if ($settings && $settings->isLdapEnabled()) {
            return [
                'enabled' => true,
                'host' => $settings->getLdapHost() ?: $this->parameterBag->get('env(LDAP_HOST)'),
                'port' => $settings->getLdapPort() ?: (int)$this->parameterBag->get('env(LDAP_PORT)'),
                'encryption' => $settings->getLdapEncryption() ?: $this->parameterBag->get('env(LDAP_ENCRYPTION)'),
                'base_dn' => $settings->getLdapBaseDn() ?: $this->parameterBag->get('env(LDAP_BASE_DN)'),
                'search_dn' => $settings->getLdapSearchDn() ?: $this->parameterBag->get('env(LDAP_SEARCH_DN)'),
                'search_password' => $settings->getLdapSearchPassword() ?: $this->parameterBag->get('env(LDAP_SEARCH_PASSWORD)'),
                'uid_key' => $settings->getLdapUidKey() ?: $this->parameterBag->get('env(LDAP_UID_KEY)')
            ];
        }

        // Utiliser les paramètres par défaut des variables d'environnement
        return [
            'enabled' => false,
            'host' => $this->parameterBag->get('env(LDAP_HOST)'),
            'port' => (int)$this->parameterBag->get('env(LDAP_PORT)'),
            'encryption' => $this->parameterBag->get('env(LDAP_ENCRYPTION)'),
            'base_dn' => $this->parameterBag->get('env(LDAP_BASE_DN)'),
            'search_dn' => $this->parameterBag->get('env(LDAP_SEARCH_DN)'),
            'search_password' => $this->parameterBag->get('env(LDAP_SEARCH_PASSWORD)'),
            'uid_key' => $this->parameterBag->get('env(LDAP_UID_KEY)')
        ];
    }

    /**
     * Récupère l'URL de la base de données depuis les paramètres ou les variables d'environnement
     */
    public function getDatabaseUrl(): string
    {
        $settings = $this->getSettings();

        if ($settings && $settings->getDatabaseUrl()) {
            return $settings->getDatabaseUrl();
        }

        return $this->parameterBag->get('env(DATABASE_URL)');
    }

    /**
     * Met à jour un fichier .env.local avec les nouveaux paramètres
     */
    public function updateEnvironmentFile(): void
    {
        $settings = $this->getSettings();
        if (!$settings) {
            return;
        }

        $envLocalPath = $this->parameterBag->get('kernel.project_dir') . '/.env.local';
        $envContent = [];

        // Lire le fichier existant s'il existe
        if (file_exists($envLocalPath)) {
            $envContent = file($envLocalPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }

        // Supprimer les anciennes valeurs LDAP et DATABASE_URL
        $envContent = array_filter($envContent, function($line) {
            return !str_starts_with($line, 'LDAP_') && !str_starts_with($line, 'DATABASE_URL=');
        });

        // Ajouter les nouveaux paramètres LDAP si activés
        if ($settings->isLdapEnabled()) {
            if ($settings->getLdapHost()) {
                $envContent[] = 'LDAP_HOST=' . $settings->getLdapHost();
            }
            if ($settings->getLdapPort()) {
                $envContent[] = 'LDAP_PORT=' . $settings->getLdapPort();
            }
            if ($settings->getLdapEncryption()) {
                $envContent[] = 'LDAP_ENCRYPTION=' . $settings->getLdapEncryption();
            }
            if ($settings->getLdapBaseDn()) {
                $envContent[] = 'LDAP_BASE_DN="' . $settings->getLdapBaseDn() . '"';
            }
            if ($settings->getLdapSearchDn()) {
                $envContent[] = 'LDAP_SEARCH_DN="' . $settings->getLdapSearchDn() . '"';
            }
            if ($settings->getLdapSearchPassword()) {
                $envContent[] = 'LDAP_SEARCH_PASSWORD=' . $settings->getLdapSearchPassword();
            }
            if ($settings->getLdapUidKey()) {
                $envContent[] = 'LDAP_UID_KEY=' . $settings->getLdapUidKey();
            }
        }

        // Ajouter l'URL de la base de données si définie
        if ($settings->getDatabaseUrl()) {
            $envContent[] = 'DATABASE_URL="' . $settings->getDatabaseUrl() . '"';
        }

        // Écrire le fichier
        file_put_contents($envLocalPath, implode("\n", $envContent) . "\n");
    }

    /**
     * Sauvegarde les paramètres de l'application
     */
    public function saveSettings(Settings $settings): void
    {
        $this->entityManager->persist($settings);
        $this->entityManager->flush();
    }
}