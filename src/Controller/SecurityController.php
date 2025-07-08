<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $currentUser = $this->getUser();

        // Vérifier si l'erreur est liée à la whitelist
        $isWhitelistError = false;
        if ($error && str_contains($error->getMessage(), 'not whitelisted')) {
            $isWhitelistError = true;
        }

        return $this->render('users/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'is_whitelist_error' => $isWhitelistError,
            'page_title' => 'Connexion',
            'user' => $currentUser,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be empty - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}