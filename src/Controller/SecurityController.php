<?php

namespace App\Controller;

use App\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Contrôleur pour la gestion de la sécurité et de l'authentification
 */
class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, SettingsService $settingsService): Response
    {
        // Si l'utilisateur est déjà connecté, rediriger vers le dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        // Récupérer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        // Dernier nom d'utilisateur saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        $settings = $settingsService->getSettings();
        $isMaintenanceMode = $settingsService->isMaintenanceMode();
        $isLdapEnabled = $settings ? $settings->isLdapEnabled() : false;

        return $this->render('users/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'is_maintenance_mode' => $isMaintenanceMode,
            'is_ldap_enabled' => $isLdapEnabled,
            'maintenance_message' => $isMaintenanceMode ? $settings?->getMaintenanceMessage() : null,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut être vide - elle sera interceptée par la clé de déconnexion de votre firewall.');
    }
}