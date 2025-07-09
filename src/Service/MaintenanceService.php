<?php

namespace App\Service;

use App\Security\AdminUserProvider;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Service pour gérer le mode maintenance avec authentification admin
 */
class MaintenanceService
{
    public function __construct(
        private SettingsService $settingsService,
        private Security $security,
        private AdminUserProvider $adminUserProvider
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

        // Seul l'utilisateur admin peut accéder en mode maintenance
        if ($user && $user->getUserIdentifier() === AdminUserProvider::getAdminCredentials()['email']) {
            return true;
        }

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
    public function enableMaintenance(string $message = null): void
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