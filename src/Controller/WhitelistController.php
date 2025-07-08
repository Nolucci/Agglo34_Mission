<?php

namespace App\Controller;

use App\Entity\Whitelist;
use App\Repository\WhitelistRepository;
use App\Service\UserPermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/whitelist')]
class WhitelistController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WhitelistRepository $whitelistRepository,
        private UserPermissionService $permissionService
    ) {
    }

    #[Route('/add', name: 'whitelist_add', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function add(Request $request): JsonResponse
    {
        $ldapUsername = $request->request->get('ldapUsername');
        $email = $request->request->get('email');
        $name = $request->request->get('name');

        if (!$ldapUsername) {
            return new JsonResponse(['success' => false, 'message' => 'Le nom d\'utilisateur LDAP est requis']);
        }

        // Vérifier si l'utilisateur est déjà dans la whitelist
        $existingEntry = $this->whitelistRepository->findOneBy(['ldapUsername' => $ldapUsername]);
        if ($existingEntry) {
            if ($existingEntry->isActive()) {
                return new JsonResponse(['success' => false, 'message' => 'Cet utilisateur est déjà dans la whitelist']);
            } else {
                // Réactiver l'entrée existante
                $existingEntry->setIsActive(true);
                $this->entityManager->flush();
                return new JsonResponse(['success' => true, 'id' => $existingEntry->getId(), 'message' => 'Utilisateur réactivé dans la whitelist']);
            }
        }

        try {
            $currentUser = $this->getUser();
            $whitelistEntry = $this->permissionService->addToWhitelist($ldapUsername, $email, $name, $currentUser);

            return new JsonResponse([
                'success' => true,
                'id' => $whitelistEntry->getId(),
                'message' => 'Utilisateur ajouté à la whitelist avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'ajout: ' . $e->getMessage()]);
        }
    }

    #[Route('/remove', name: 'whitelist_remove', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function remove(Request $request): JsonResponse
    {
        $ldapUsername = $request->request->get('ldapUsername');

        if (!$ldapUsername) {
            return new JsonResponse(['success' => false, 'message' => 'Le nom d\'utilisateur LDAP est requis']);
        }

        try {
            $success = $this->permissionService->removeFromWhitelist($ldapUsername);

            if ($success) {
                return new JsonResponse(['success' => true, 'message' => 'Utilisateur supprimé de la whitelist avec succès']);
            } else {
                return new JsonResponse(['success' => false, 'message' => 'Utilisateur non trouvé dans la whitelist']);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
        }
    }
}