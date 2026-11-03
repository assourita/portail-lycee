<?php

namespace App\Controller;

use App\Entity\Trimestre;
use App\Repository\ClasseRepository;
use App\Repository\TrimestreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/trimestres')]
#[IsGranted('ROLE_ADMIN')]
class AdminTrimestreController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TrimestreRepository $trimestreRepository,
        private ClasseRepository $classeRepository
    ) {}

    #[Route('/', name: 'admin_trimestres_index', methods: ['GET'])]
    public function index(): Response
    {
        $trimestres = $this->trimestreRepository->findAll();
        $classes = $this->classeRepository->findAll();

        return $this->render('admin/trimestre/index.html.twig', [
            'trimestres' => $trimestres,
            'classes' => $classes,
        ]);
    }

    #[Route('/new', name: 'admin_trimestre_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $classes = $this->classeRepository->findAll();

        if ($request->isMethod('POST')) {
            $classeId = $request->request->get('classe');
            $nom = $request->request->get('nom');
            $dateDebut = $request->request->get('date_debut');
            $dateFin = $request->request->get('date_fin');
            $anneeScolaire = $request->request->get('annee_scolaire');
            $description = $request->request->get('description');

            $classe = $this->classeRepository->find($classeId);

            if ($classe && $nom && $dateDebut && $dateFin && $anneeScolaire) {
                $trimestre = new Trimestre();
                $trimestre->setClasse($classe);
                $trimestre->setNom($nom);
                $trimestre->setDateDebut(new \DateTime($dateDebut));
                $trimestre->setDateFin(new \DateTime($dateFin));
                $trimestre->setAnneeScolaire($anneeScolaire);
                $trimestre->setDescription($description);

                $this->entityManager->persist($trimestre);
                $this->entityManager->flush();

                $this->addFlash('success', 'Trimestre créé avec succès !');
                return $this->redirectToRoute('admin_trimestres_index');
            }
        }

        return $this->render('admin/trimestre/new.html.twig', [
            'classes' => $classes,
        ]);
    }

    #[Route('/{id}', name: 'admin_trimestre_show', methods: ['GET'])]
    public function show(Trimestre $trimestre): Response
    {
        return $this->render('admin/trimestre/show.html.twig', [
            'trimestre' => $trimestre,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_trimestre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Trimestre $trimestre): Response
    {
        $classes = $this->classeRepository->findAll();

        if ($request->isMethod('POST')) {
            $classeId = $request->request->get('classe');
            $nom = $request->request->get('nom');
            $dateDebut = $request->request->get('date_debut');
            $dateFin = $request->request->get('date_fin');
            $anneeScolaire = $request->request->get('annee_scolaire');
            $description = $request->request->get('description');

            $classe = $this->classeRepository->find($classeId);

            if ($classe) {
                $trimestre->setClasse($classe);
                $trimestre->setNom($nom);
                $trimestre->setDateDebut(new \DateTime($dateDebut));
                $trimestre->setDateFin(new \DateTime($dateFin));
                $trimestre->setAnneeScolaire($anneeScolaire);
                $trimestre->setDescription($description);

                $this->entityManager->flush();

                $this->addFlash('success', 'Trimestre modifié avec succès !');
                return $this->redirectToRoute('admin_trimestre_show', ['id' => $trimestre->getId()]);
            }
        }

        return $this->render('admin/trimestre/edit.html.twig', [
            'trimestre' => $trimestre,
            'classes' => $classes,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_trimestre_delete', methods: ['POST'])]
    public function delete(Request $request, Trimestre $trimestre): Response
    {
        if ($this->isCsrfTokenValid('delete-trimestre', $request->request->get('_token'))) {
            $this->entityManager->remove($trimestre);
            $this->entityManager->flush();

            $this->addFlash('success', 'Trimestre supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_trimestres_index');
    }
}

