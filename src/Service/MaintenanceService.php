<?php

namespace App\Service;

use App\Security\AdminUserProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer le mode maintenance avec authentification admin
 */
class MaintenanceService
{
    public function __construct(
        private SettingsService $settingsService,
        private Security $security,
        private AdminUserProvider $adminUserProvider,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Vérifie si l'application est en mode maintenance
     */
    public function isMaintenanceMode(): bool
    {
        return $this->settingsService->isMaintenanceMode();
    }

    /**
     * Vérifie si l'utilisateur actuel peut accéder à l'application en mode maintenance
     */
    public function canAccessDuringMaintenance(): bool
    {
        if (!$this->isMaintenanceMode()) {
            return true;
        }

        $user = $this->security->getUser();

        $this->logger->info('MaintenanceService: Checking access', [
            'user_exists' => $user !== null,
            'user_class' => $user ? get_class($user) : 'null',
            'user_identifier' => $user ? $user->getUserIdentifier() : 'null'
        ]);

        // Vérifier si l'utilisateur est connecté et n'est pas anonyme
        if (!$user || !($user instanceof \App\Entity\User)) {
            $this->logger->info('MaintenanceService: No user or not User instance');
            return false;
        }

        $this->logger->info('MaintenanceService: User details', [
            'ldap_username' => $user->getLdapUsername(),
            'user_identifier' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'admin_email' => AdminUserProvider::getAdminCredentials()['email']
        ]);

        // Vérifier que ce n'est pas l'utilisateur anonyme
        if ($user->getLdapUsername() === 'anonymous') {
            $this->logger->info('MaintenanceService: Anonymous user, access denied');
            return false;
        }

        // Seul l'utilisateur admin ou un utilisateur avec ROLE_ADMIN peut accéder en mode maintenance
        $isAdminEmail = $user->getUserIdentifier() === AdminUserProvider::getAdminCredentials()['email'];
        $hasAdminRole = in_array('ROLE_ADMIN', $user->getRoles());

        $this->logger->info('MaintenanceService: Access check', [
            'is_admin_email' => $isAdminEmail,
            'has_admin_role' => $hasAdminRole
        ]);

        if ($isAdminEmail || $hasAdminRole) {
            $this->logger->info('MaintenanceService: Access granted');
            return true;
        }

        $this->logger->info('MaintenanceService: Access denied');
        return false;
    }

    /**
     * Récupère le message de maintenance
     */
    public function getMaintenanceMessage(): string
    {
        $settings = $this->settingsService->getSettings();

        if ($settings && $settings->getMaintenanceMessage()) {
            return $settings->getMaintenanceMessage();
        }

        return 'Application en maintenance. Veuillez réessayer plus tard.';
    }

    /**
     * Active le mode maintenance
     */
    public function enableMaintenance(?string $message = null): void
    {
        $settings = $this->settingsService->getSettings();

        if ($settings) {
            $settings->setMaintenanceMode(true);

            if ($message) {
                $settings->setMaintenanceMessage($message);
            }
            $this->settingsService->saveSettings($settings);
        }
    }

    /**
     * Désactive le mode maintenance
     */
    public function disableMaintenance(): void
    {
        $settings = $this->settingsService->getSettings();

        if ($settings) {
            $settings->setMaintenanceMode(false);
            $this->settingsService->saveSettings($settings);
        }
    }
}