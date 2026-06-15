<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Déjà connecté : on renvoie vers la liste des projets.
        if ($this->getUser()) {
            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        // Interceptée par la clé « logout » du firewall ; ce code n'est jamais exécuté.
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/', name: 'app_home')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_project_index');
    }
}
