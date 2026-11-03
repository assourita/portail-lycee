<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Repository\ClasseRepository;
use App\Repository\EtudiantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/classes')]
#[IsGranted('ROLE_ADMIN')]
class AdminClasseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ClasseRepository $classeRepository,
        private EtudiantRepository $etudiantRepository
    ) {}

    #[Route('/', name: 'admin_classes_index', methods: ['GET'])]
    public function index(): Response
    {
        $classes = $this->classeRepository->findAll();
        
        // Calculer le nombre d'élèves par classe
        $classesWithStats = [];
        foreach ($classes as $classe) {
            $nombreEtudiants = 0;
            
            // Compter les étudiants dans toutes les filières de cette classe
            foreach ($classe->getFilieres() as $filiere) {
                $etudiantsFiliere = $this->etudiantRepository->findBy(['filiere' => $filiere]);
                $nombreEtudiants += count($etudiantsFiliere);
            }
            
            $classesWithStats[] = [
                'classe' => $classe,
                'nombreEtudiants' => $nombreEtudiants,
                'nombreFilieres' => $classe->getFilieres()->count(),
                'nombreTrimestres' => $classe->getTrimestres()->count()
            ];
        }

        return $this->render('admin/classe/index.html.twig', [
            'classesWithStats' => $classesWithStats,
        ]);
    }

    #[Route('/new', name: 'admin_classe_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $description = $request->request->get('description');

            if ($nom) {
                $classe = new Classe();
                $classe->setNom($nom);
                $classe->setDescription($description);

                $this->entityManager->persist($classe);
                $this->entityManager->flush();

                $this->addFlash('success', 'Classe créée avec succès !');
                return $this->redirectToRoute('admin_classes_index');
            }
        }

        return $this->render('admin/classe/new.html.twig');
    }

    #[Route('/{id}', name: 'admin_classe_show', methods: ['GET'])]
    public function show(Classe $classe): Response
    {
        return $this->render('admin/classe/show.html.twig', [
            'classe' => $classe,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_classe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Classe $classe): Response
    {
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $description = $request->request->get('description');

            $classe->setNom($nom);
            $classe->setDescription($description);

            $this->entityManager->flush();

            $this->addFlash('success', 'Classe modifiée avec succès !');
            return $this->redirectToRoute('admin_classe_show', ['id' => $classe->getId()]);
        }

        return $this->render('admin/classe/edit.html.twig', [
            'classe' => $classe,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_classe_delete', methods: ['POST'])]
    public function delete(Request $request, Classe $classe): Response
    {
        if ($this->isCsrfTokenValid('delete-classe', $request->request->get('_token'))) {
            $this->entityManager->remove($classe);
            $this->entityManager->flush();

            $this->addFlash('success', 'Classe supprimée avec succès !');
        }

        return $this->redirectToRoute('admin_classes_index');
    }
}

