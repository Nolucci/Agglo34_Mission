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
        // Si l'utilisateur n'est pas connecté, il n'y a pas de données utilisateur à passer au template de login
        // Le template de login ne devrait pas avoir besoin des informations de l'utilisateur connecté
        // Cependant, si le template attend une variable 'user', nous pouvons la passer comme null ou un tableau vide.
        // Pour l'instant, je vais la passer comme null si non connecté, ou l'objet User si connecté.

        return $this->render('users/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
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