<?php

namespace App\Controller;

use App\Entity\Enseignant;
use App\Entity\Filiere;
use App\Entity\Matiere;
use App\Entity\PeriodeAcademique;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $em): Response
    {
        $niveaux = [];
        foreach ($em->getRepository(Filiere::class)->findBy([], ['nom' => 'ASC']) as $filiere) {
            if (!$filiere->getClasse()) {
                continue;
            }
            $classe = $filiere->getClasse()->getNom();
            $niveaux[$classe]['filieres'][] = [
                'nom' => $filiere->getNom(),
                'matieres' => $filiere->getMatieres()->count(),
            ];
        }
        uksort($niveaux, fn (string $a, string $b) => $this->ordreNiveau($a) <=> $this->ordreNiveau($b));

        $derniereAnnee = $em->getRepository(PeriodeAcademique::class)->findOneBy([], ['dateDebut' => 'DESC'])?->getAnneeScolaire();
        $calendrier = $derniereAnnee
            ? $em->getRepository(PeriodeAcademique::class)->findBy(['anneeScolaire' => $derniereAnnee], ['dateDebut' => 'ASC'])
            : [];
        $trimestres = array_values(array_filter($calendrier, fn (PeriodeAcademique $p) => stripos($p->getPeriode(), 'trimestre') !== false));
        $vus = [];
        $trimestres = array_values(array_filter($trimestres, function (PeriodeAcademique $p) use (&$vus) {
            return !isset($vus[$p->getPeriode()]) && ($vus[$p->getPeriode()] = true);
        }));

        return $this->render('accueil/index.html.twig', [
            'niveaux' => $niveaux,
            'stats' => [
                'niveaux' => count($niveaux),
                'filieres' => array_sum(array_map(fn (array $n) => count($n['filieres']), $niveaux)),
                'matieres' => $em->getRepository(Matiere::class)->count([]),
                'enseignants' => $em->getRepository(Enseignant::class)->count([]),
            ],
            'calendrier' => array_slice($trimestres, 0, 3),
            'anneeScolaire' => $derniereAnnee,
        ]);
    }

    private function ordreNiveau(string $niveau): int
    {
        return preg_match('/^(\d+)/', $niveau, $m) ? (int) $m[1] : 99;
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
