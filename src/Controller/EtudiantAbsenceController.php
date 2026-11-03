<?php

namespace App\Controller;

use App\Repository\AbsenceRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/etudiant/absences')]
#[IsGranted('ROLE_ETUDIANT')]
class EtudiantAbsenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AbsenceRepository $absenceRepository,
        private PeriodeAcademiqueService $periodeService
    ) {}

    #[Route('/', name: 'etudiant_absences_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$etudiant) {
            $this->addFlash('error', 'Vous devez être un étudiant pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les absences de l'étudiant
        $absences = $this->absenceRepository->findByEtudiant($etudiant, $periodeActive);

        // Calculer les statistiques
        $stats = [
            'total' => count($absences),
            'justifiees' => $this->absenceRepository->countByEtudiant($etudiant, $periodeActive, true),
            'non_justifiees' => $this->absenceRepository->countByEtudiant($etudiant, $periodeActive, false),
            'taux_assiduite' => $this->absenceRepository->calculateTauxAssiduite($etudiant, $periodeActive),
        ];

        // Calculer les heures d'absence
        $heuresAbsence = 0;
        foreach ($absences as $absence) {
            $heuresAbsence += $absence->getDureeHeures();
        }

        return $this->render('etudiant/absence/index.html.twig', [
            'absences' => $absences,
            'stats' => $stats,
            'heuresAbsence' => $heuresAbsence,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/{id}', name: 'etudiant_absence_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();

        if (!$etudiant) {
            throw $this->createAccessDeniedException();
        }

        $absence = $this->absenceRepository->find($id);

        if (!$absence || $absence->getEtudiant() !== $etudiant) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette absence.');
        }

        return $this->render('etudiant/absence/show.html.twig', [
            'absence' => $absence,
        ]);
    }

    #[Route('/{id}/justifier', name: 'etudiant_absence_justifier', methods: ['GET', 'POST'])]
    public function justifier(Request $request, int $id): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();

        if (!$etudiant) {
            throw $this->createAccessDeniedException();
        }

        $absence = $this->absenceRepository->find($id);

        if (!$absence || $absence->getEtudiant() !== $etudiant) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette absence.');
        }

        if ($request->isMethod('POST')) {
            $justification = $request->request->get('justification');

            if ($justification) {
                $absence->setJustification($justification);
                $absence->setDateModification(new \DateTime());

                // TODO: Gérer l'upload du document justificatif
                // Pour l'instant, on sauvegarde juste la justification texte

                $this->entityManager->flush();

                $this->addFlash('success', 'Justification envoyée avec succès ! Elle sera examinée par l\'administration.');
                return $this->redirectToRoute('etudiant_absences_index');
            }

            $this->addFlash('error', 'Veuillez fournir une justification.');
        }

        return $this->render('etudiant/absence/justifier.html.twig', [
            'absence' => $absence,
        ]);
    }
}

