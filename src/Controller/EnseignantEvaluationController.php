<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Repository\EvaluationRepository;
use App\Repository\TrimestreRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/enseignant/evaluations')]
#[IsGranted('ROLE_ENSEIGNANT')]
class EnseignantEvaluationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EvaluationRepository $evaluationRepository,
        private TrimestreRepository $trimestreRepository,
        private PeriodeAcademiqueService $periodeService
    ) {}

    #[Route('/', name: 'enseignant_evaluations_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }

        // Récupérer les évaluations de l'enseignant pour la période active
        $qb = $this->evaluationRepository->createQueryBuilder('e')
            ->andWhere('e.enseignant = :enseignant')
            ->setParameter('enseignant', $enseignant)
            ->orderBy('e.dateEvaluation', 'DESC');

        if ($periodeActive) {
            $qb->andWhere('e.periodeAcademique = :periode')
               ->setParameter('periode', $periodeActive);
        }

        $evaluations = $qb->getQuery()->getResult();

        return $this->render('enseignant/evaluation/index.html.twig', [
            'evaluations' => $evaluations,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/new', name: 'enseignant_evaluation_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }

        if (!$periodeActive) {
            $this->addFlash('error', 'Aucune période académique active. Veuillez contacter l\'administration.');
            return $this->redirectToRoute('enseignant_evaluations_index');
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
                $matiere = $this->entityManager->getRepository(\App\Entity\Matiere::class)->find($matiereId);
                $trimestre = $this->trimestreRepository->find($trimestreId);

                // Vérifier que la matière appartient à l'enseignant
                if ($matiere && $matiere->getEnseignant() === $enseignant && $trimestre) {
                    $evaluation = new Evaluation();
                    $evaluation->setNom($nom);
                    $evaluation->setDescription($description);
                    $evaluation->setDateEvaluation(new \DateTime($dateEvaluation));
                    $evaluation->setNoteMax((int)$noteMax);
                    $evaluation->setCoefficient((int)$coefficient);
                    $evaluation->setMatiere($matiere);
                    $evaluation->setTrimestre($trimestre);
                    $evaluation->setPeriodeAcademique($periodeActive);
                    $evaluation->setEnseignant($enseignant);

                    $this->entityManager->persist($evaluation);
                    $this->entityManager->flush();

                    $this->addFlash('success', 'Évaluation créée avec succès !');
                    return $this->redirectToRoute('enseignant_evaluations_index');
                } else {
                    $this->addFlash('error', 'Vous ne pouvez créer une évaluation que pour vos propres matières.');
                }
            }

            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
        }

        $matieres = $enseignant->getMatieres();
        $trimestres = $this->trimestreRepository->findAll();

        return $this->render('enseignant/evaluation/new.html.twig', [
            'matieres' => $matieres,
            'trimestres' => $trimestres,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/{id}', name: 'enseignant_evaluation_show', methods: ['GET'])]
    public function show(Evaluation $evaluation): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();

        // Vérifier que l'évaluation appartient à l'enseignant
        if ($evaluation->getEnseignant() !== $enseignant) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette évaluation.');
        }

        // Récupérer les notes liées à cette évaluation
        $notes = $this->entityManager->getRepository(\App\Entity\Note::class)
            ->findBy(['evaluation' => $evaluation]);

        return $this->render('enseignant/evaluation/show.html.twig', [
            'evaluation' => $evaluation,
            'notes' => $notes,
        ]);
    }

    #[Route('/{id}/edit', name: 'enseignant_evaluation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evaluation $evaluation): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();

        if ($evaluation->getEnseignant() !== $enseignant) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette évaluation.');
        }

        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $description = $request->request->get('description');
            $dateEvaluation = $request->request->get('date_evaluation');
            $noteMax = $request->request->get('note_max', 20);
            $coefficient = $request->request->get('coefficient', 1);
            $trimestreId = $request->request->get('trimestre');

            if ($nom && $dateEvaluation && $trimestreId) {
                $trimestre = $this->trimestreRepository->find($trimestreId);

                if ($trimestre) {
                    $evaluation->setNom($nom);
                    $evaluation->setDescription($description);
                    $evaluation->setDateEvaluation(new \DateTime($dateEvaluation));
                    $evaluation->setNoteMax((int)$noteMax);
                    $evaluation->setCoefficient((int)$coefficient);
                    $evaluation->setTrimestre($trimestre);
                    $evaluation->setDateModification(new \DateTime());

                    $this->entityManager->flush();

                    $this->addFlash('success', 'Évaluation modifiée avec succès !');
                    return $this->redirectToRoute('enseignant_evaluation_show', ['id' => $evaluation->getId()]);
                }
            }

            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
        }

        $trimestres = $this->trimestreRepository->findAll();

        return $this->render('enseignant/evaluation/edit.html.twig', [
            'evaluation' => $evaluation,
            'trimestres' => $trimestres,
        ]);
    }

    #[Route('/{id}/delete', name: 'enseignant_evaluation_delete', methods: ['POST'])]
    public function delete(Request $request, Evaluation $evaluation): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();

        if ($evaluation->getEnseignant() !== $enseignant) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette évaluation.');
        }

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

        return $this->redirectToRoute('enseignant_evaluations_index');
    }
}

