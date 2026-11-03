<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Repository\EvaluationRepository;
use App\Repository\MatiereRepository;
use App\Repository\TrimestreRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/evaluations')]
#[IsGranted('ROLE_ADMIN')]
class AdminEvaluationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EvaluationRepository $evaluationRepository,
        private MatiereRepository $matiereRepository,
        private TrimestreRepository $trimestreRepository,
        private PeriodeAcademiqueService $periodeService
    ) {}

    #[Route('/', name: 'admin_evaluations_index', methods: ['GET'])]
    public function index(): Response
    {
        $periodeActive = $this->periodeService->getPeriodeActive();
        
        // Filtrer les évaluations par période active
        $criteria = [];
        if ($periodeActive) {
            $criteria['periodeAcademique'] = $periodeActive;
        }
        
        $evaluations = $this->evaluationRepository->findBy($criteria, ['dateEvaluation' => 'DESC']);
        $matieres = $this->matiereRepository->findAll();

        return $this->render('admin/evaluation/index.html.twig', [
            'evaluations' => $evaluations,
            'matieres' => $matieres,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/new', name: 'admin_evaluation_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $periodeActive = $this->periodeService->getPeriodeActive();
        
        if (!$periodeActive) {
            $this->addFlash('error', 'Aucune période académique active. Veuillez activer une période avant de créer une évaluation.');
            return $this->redirectToRoute('admin_periodes_academiques_index');
        }

        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $description = $request->request->get('description');
            $dateEvaluation = $request->request->get('date_evaluation');
            $noteMax = $request->request->get('note_max', 20);
            $coefficient = $request->request->get('coefficient', 1);
            $matiereId = $request->request->get('matiere');
            $trimestreId = $request->request->get('trimestre');

            if ($nom && $dateEvaluation && $matiereId && $trimestreId) {
                $matiere = $this->matiereRepository->find($matiereId);
                $trimestre = $this->trimestreRepository->find($trimestreId);

                if ($matiere && $trimestre) {
                    $evaluation = new Evaluation();
                    $evaluation->setNom($nom);
                    $evaluation->setDescription($description);
                    $evaluation->setDateEvaluation(new \DateTime($dateEvaluation));
                    $evaluation->setNoteMax((int)$noteMax);
                    $evaluation->setCoefficient((int)$coefficient);
                    $evaluation->setMatiere($matiere);
                    $evaluation->setTrimestre($trimestre);
                    $evaluation->setPeriodeAcademique($periodeActive);
                    $evaluation->setEnseignant($matiere->getEnseignant());

                    $this->entityManager->persist($evaluation);
                    $this->entityManager->flush();

                    $this->addFlash('success', 'Évaluation créée avec succès !');
                    return $this->redirectToRoute('admin_evaluations_index');
                }
            }

            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
        }

        $matieres = $this->matiereRepository->findAll();
        $trimestres = $this->trimestreRepository->findAll();

        return $this->render('admin/evaluation/new.html.twig', [
            'matieres' => $matieres,
            'trimestres' => $trimestres,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/{id}', name: 'admin_evaluation_show', methods: ['GET'])]
    public function show(Evaluation $evaluation): Response
    {
        $periodeActive = $this->periodeService->getPeriodeActive();
        
        // Récupérer les notes liées à cette évaluation
        $notes = $this->entityManager->getRepository(\App\Entity\Note::class)
            ->findBy(['evaluation' => $evaluation]);

        return $this->render('admin/evaluation/show.html.twig', [
            'evaluation' => $evaluation,
            'notes' => $notes,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_evaluation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evaluation $evaluation): Response
    {
        $periodeActive = $this->periodeService->getPeriodeActive();

        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $description = $request->request->get('description');
            $dateEvaluation = $request->request->get('date_evaluation');
            $noteMax = $request->request->get('note_max', 20);
            $coefficient = $request->request->get('coefficient', 1);
            $matiereId = $request->request->get('matiere');
            $trimestreId = $request->request->get('trimestre');

            if ($nom && $dateEvaluation && $matiereId && $trimestreId) {
                $matiere = $this->matiereRepository->find($matiereId);
                $trimestre = $this->trimestreRepository->find($trimestreId);

                if ($matiere && $trimestre) {
                    $evaluation->setNom($nom);
                    $evaluation->setDescription($description);
                    $evaluation->setDateEvaluation(new \DateTime($dateEvaluation));
                    $evaluation->setNoteMax((int)$noteMax);
                    $evaluation->setCoefficient((int)$coefficient);
                    $evaluation->setMatiere($matiere);
                    $evaluation->setTrimestre($trimestre);
                    $evaluation->setEnseignant($matiere->getEnseignant());
                    $evaluation->setDateModification(new \DateTime());

                    $this->entityManager->flush();

                    $this->addFlash('success', 'Évaluation modifiée avec succès !');
                    return $this->redirectToRoute('admin_evaluation_show', ['id' => $evaluation->getId()]);
                }
            }

            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
        }

        $matieres = $this->matiereRepository->findAll();
        $trimestres = $this->trimestreRepository->findAll();

        return $this->render('admin/evaluation/edit.html.twig', [
            'evaluation' => $evaluation,
            'matieres' => $matieres,
            'trimestres' => $trimestres,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_evaluation_delete', methods: ['POST'])]
    public function delete(Request $request, Evaluation $evaluation): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evaluation->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des notes liées
            $notes = $this->entityManager->getRepository(\App\Entity\Note::class)
                ->findBy(['evaluation' => $evaluation]);

            if (count($notes) > 0) {
                $this->addFlash('error', 'Impossible de supprimer cette évaluation car elle contient des notes.');
            } else {
                $this->entityManager->remove($evaluation);
                $this->entityManager->flush();
                $this->addFlash('success', 'Évaluation supprimée avec succès !');
            }
        }

        return $this->redirectToRoute('admin_evaluations_index');
    }

    #[Route('/matiere/{id}/evaluations', name: 'admin_evaluation_by_matiere', methods: ['GET'])]
    public function getEvaluationsByMatiere(int $id): Response
    {
        $periodeActive = $this->periodeService->getPeriodeActive();
        $matiere = $this->matiereRepository->find($id);

        if (!$matiere) {
            return $this->json(['error' => 'Matière non trouvée'], 404);
        }

        $criteria = ['matiere' => $matiere];
        if ($periodeActive) {
            $criteria['periodeAcademique'] = $periodeActive;
        }

        $evaluations = $this->evaluationRepository->findBy($criteria, ['dateEvaluation' => 'DESC']);

        $evaluationsData = array_map(function($evaluation) {
            return [
                'id' => $evaluation->getId(),
                'nom' => $evaluation->getNom(),
                'dateEvaluation' => $evaluation->getDateEvaluation()->format('Y-m-d'),
                'coefficient' => $evaluation->getCoefficient(),
                'noteMax' => $evaluation->getNoteMax(),
            ];
        }, $evaluations);

        return $this->json($evaluationsData);
    }
}
