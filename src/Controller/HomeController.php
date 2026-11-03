<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('accueil/index.html.twig');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function dashboard(): Response
    {
        $routesParRole = [
            'ROLE_ADMIN' => 'admin_dashboard',
            'ROLE_ENSEIGNANT' => 'enseignant_dashboard',
            'ROLE_ETUDIANT' => 'etudiant_dashboard',
            'ROLE_COMPTABLE' => 'comptable_dashboard',
            'ROLE_PARENT' => 'parent_dashboard',
        ];

        foreach ($routesParRole as $role => $route) {
            if ($this->isGranted($role)) {
                return $this->redirectToRoute($route);
            }
        }

        return $this->redirectToRoute('app_profil');
    }
}
