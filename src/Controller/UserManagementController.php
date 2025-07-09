<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserPermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
class UserManagementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPermissionService $permissionService
    ) {
    }

    #[Route('/update-role', name: 'user_update_role', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateRole(Request $request): JsonResponse
    {
        $userId = $request->request->get('userId');
        $role = $request->request->get('role');

        if (!$userId || !$role) {
            return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants']);
        }

        $user = $this->userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non trouvé']);
        }

        // Vérifier que ce n'est pas l'utilisateur admin par défaut
        if ($user->getEmail() === 'admin@agglo34.local') {
            return new JsonResponse(['success' => false, 'message' => 'Impossible de modifier les rôles de l\'administrateur par défaut']);
        }

        // Valider le rôle
        $validRoles = [
            UserPermissionService::ROLE_ADMIN,
            UserPermissionService::ROLE_MODIFIEUR,
            UserPermissionService::ROLE_VISITEUR_TOUT,
            UserPermissionService::ROLE_VISITEUR_LIGNES,
            UserPermissionService::ROLE_VISITEUR_PARC,
            UserPermissionService::ROLE_VISITEUR_BOXS,
            UserPermissionService::ROLE_DISABLED
        ];

        if (!in_array($role, $validRoles)) {
            return new JsonResponse(['success' => false, 'message' => 'Rôle invalide']);
        }

        try {
            // Réinitialiser les rôles et assigner le nouveau
            $user->setRoles(['ROLE_USER']);
            $this->permissionService->assignRole($user, $role);

            return new JsonResponse(['success' => true, 'message' => 'Rôle mis à jour avec succès']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
    }
}