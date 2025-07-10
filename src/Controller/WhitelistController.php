<?php

namespace App\Controller;

use App\Service\WhitelistService;
use App\Service\LdapTestService;
use App\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/whitelist')]
#[IsGranted('ROLE_ADMIN')]
class WhitelistController extends AbstractController
{
    public function __construct(
        private WhitelistService $whitelistService,
        private LdapTestService $ldapTestService,
        private SettingsService $settingsService
    ) {
    }

    #[Route('/', name: 'admin_whitelist_index')]
    public function index(): Response
    {
        $users = $this->whitelistService->getAllUsers();

        return $this->render('admin/whitelist/index.html.twig', [
            'users' => $users,
            'is_ldap_enabled' => $this->settingsService->getSettings()?->isLdapEnabled() ?? false
        ]);
    }

    #[Route('/api/users', name: 'admin_whitelist_api_users', methods: ['GET'])]
    public function getUsers(): JsonResponse
    {
        try {
            $users = $this->whitelistService->getAllUsers();

            return new JsonResponse([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors du chargement : ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/api/add', name: 'admin_whitelist_api_add', methods: ['POST'])]
    public function addUser(Request $request): JsonResponse
    {
        $ldapUsername = $request->request->get('ldapUsername');
        $name = $request->request->get('name');
        $email = $request->request->get('email');

        if (!$ldapUsername) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le nom d\'utilisateur LDAP est requis'
            ]);
        }

        try {
            // Vérifier si l'utilisateur existe dans LDAP (si LDAP est activé)
            if ($this->settingsService->getSettings()?->isLdapEnabled()) {
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

                $testResult = $this->ldapTestService->checkUserExists($ldapConfig, $ldapUsername);

                // Si l'utilisateur n'existe pas dans LDAP, on peut quand même l'ajouter
                // mais on affiche un avertissement
                if (!$testResult['success'] && str_contains($testResult['message'], 'non trouvé')) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Utilisateur non trouvé dans LDAP. Vérifiez le nom d\'utilisateur.'
                    ]);
                }

                // Si l'utilisateur existe dans LDAP, récupérer ses informations
                if ($testResult['success'] && isset($testResult['user_attributes'])) {
                    $attributes = $testResult['user_attributes'];
                    $name = $name ?: ($attributes['displayname'] ?? $attributes['cn'] ?? null);
                    $email = $email ?: ($attributes['mail'] ?? null);
                }
            }

            $whitelistEntry = $this->whitelistService->addUserToWhitelist(
                $ldapUsername,
                $name,
                $email,
                $this->getUser()
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Utilisateur ajouté à la whitelist avec succès',
                'user' => [
                    'ldap_username' => $whitelistEntry->getLdapUsername(),
                    'name' => $whitelistEntry->getName(),
                    'email' => $whitelistEntry->getEmail(),
                    'is_active' => $whitelistEntry->isActive(),
                    'createdAt' => $whitelistEntry->getCreatedAt()->format('d/m/Y H:i'),
                    'createdBy' => $this->getUser() ? $this->getUser()->getName() : '-'
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout : ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/remove/{ldapUsername}', name: 'admin_whitelist_remove', methods: ['POST'])]
    public function removeUser(string $ldapUsername): JsonResponse
    {
        try {
            $success = $this->whitelistService->removeUserFromWhitelist($ldapUsername);

            if ($success) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Utilisateur retiré de la whitelist avec succès'
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé dans la whitelist'
                ]);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/api/disable', name: 'admin_whitelist_api_disable', methods: ['POST'])]
    public function disableUser(Request $request): JsonResponse
    {
        $ldapUsername = $request->request->get('ldapUsername');

        if (!$ldapUsername) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le nom d\'utilisateur LDAP est requis'
            ]);
        }

        try {
            $success = $this->whitelistService->disableUser($ldapUsername);

            if ($success) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Utilisateur désactivé avec succès'
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé dans la whitelist'
                ]);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la désactivation : ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/api/reactivate', name: 'admin_whitelist_api_reactivate', methods: ['POST'])]
    public function reactivateUser(Request $request): JsonResponse
    {
        $ldapUsername = $request->request->get('ldapUsername');

        if (!$ldapUsername) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le nom d\'utilisateur LDAP est requis'
            ]);
        }

        try {
            $success = $this->whitelistService->reactivateUser($ldapUsername);

            if ($success) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Utilisateur réactivé avec succès'
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé dans la whitelist'
                ]);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la réactivation : ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/api/remove', name: 'admin_whitelist_api_remove', methods: ['POST'])]
    public function removeUserPermanently(Request $request): JsonResponse
    {
        $ldapUsername = $request->request->get('ldapUsername');

        if (!$ldapUsername) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le nom d\'utilisateur LDAP est requis'
            ]);
        }

        try {
            $success = $this->whitelistService->removeUserPermanently($ldapUsername);

            if ($success) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Utilisateur supprimé définitivement avec succès'
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé dans la whitelist'
                ]);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/activate/{ldapUsername}', name: 'admin_whitelist_activate', methods: ['POST'])]
    public function activateUser(string $ldapUsername): JsonResponse
    {
        try {
            $whitelistEntry = $this->whitelistService->addUserToWhitelist($ldapUsername);

            return new JsonResponse([
                'success' => true,
                'message' => 'Utilisateur réactivé avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la réactivation : ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/test-ldap-user', name: 'admin_whitelist_test_ldap', methods: ['POST'])]
    public function testLdapUser(Request $request): JsonResponse
    {
        $ldapUsername = $request->request->get('ldapUsername');

        if (!$ldapUsername) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le nom d\'utilisateur LDAP est requis'
            ]);
        }

        if (!$this->settingsService->getSettings()?->isLdapEnabled()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'LDAP n\'est pas activé'
            ]);
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

            // Vérifier l'existence de l'utilisateur sans tester l'authentification
            $testResult = $this->ldapTestService->checkUserExists($ldapConfig, $ldapUsername);

            if (str_contains($testResult['message'], 'non trouvé')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé dans LDAP',
                    'user_exists' => false
                ]);
            }

            // Si on arrive ici, l'utilisateur existe (même si l'auth échoue à cause du mauvais mot de passe)
            $userAttributes = $testResult['user_attributes'] ?? [];

            return new JsonResponse([
                'success' => true,
                'message' => 'Utilisateur trouvé dans LDAP',
                'user_exists' => true,
                'user_info' => [
                    'name' => $userAttributes['displayname'] ?? $userAttributes['cn'] ?? null,
                    'email' => $userAttributes['mail'] ?? null,
                    'ldap_username' => $ldapUsername
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors du test LDAP : ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/api/remove-all', name: 'admin_whitelist_api_remove_all', methods: ['POST'])]
    public function removeAllUsers(): JsonResponse
    {
        try {
            $count = $this->whitelistService->removeAllUsers();

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%d utilisateur(s) supprimé(s) de la whitelist avec succès', $count),
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression de tous les utilisateurs : ' . $e->getMessage()
            ]);
        }
    }

}