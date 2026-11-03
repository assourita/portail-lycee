<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Repository\AbsenceRepository;
use App\Repository\BulletinRepository;
use App\Repository\NoteRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parent')]
#[IsGranted('ROLE_PARENT')]
class ParentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NoteRepository $noteRepository,
        private BulletinRepository $bulletinRepository,
        private AbsenceRepository $absenceRepository,
        private PeriodeAcademiqueService $periodeService
    ) {}

    #[Route('/dashboard', name: 'parent_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $parent = $user->getParentEleve();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$parent) {
            $this->addFlash('error', 'Vous devez être un parent pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        $enfants = $parent->getEnfants();

        // Statistiques par enfant
        $enfantsStats = [];
        foreach ($enfants as $enfant) {
            $criteres = ['etudiant' => $enfant];
            if ($periodeActive) {
                $criteres['periodeAcademique'] = $periodeActive;
            }
            $notes = $this->noteRepository->findBy($criteres, ['dateNote' => 'DESC']);

            $moyenneGenerale = $notes
                ? round(array_sum(array_map(fn ($note) => (float) $note->getValeur(), $notes)) / count($notes), 2)
                : null;

            $enfantsStats[] = [
                'enfant' => $enfant,
                'moyenneGenerale' => $moyenneGenerale,
                'totalNotes' => count($notes),
                'dernieresNotes' => array_slice($notes, 0, 5),
                'totalAbsences' => $this->absenceRepository->countByEtudiant($enfant, $periodeActive),
                'absencesNonJustifiees' => $this->absenceRepository->countByEtudiant($enfant, $periodeActive, false),
                'absencesRecentes' => array_slice($this->absenceRepository->findByEtudiant($enfant, $periodeActive), 0, 4),
                'tauxAssiduite' => $this->absenceRepository->calculateTauxAssiduite($enfant, $periodeActive),
                'dernierBulletin' => $this->bulletinRepository->findOneBy(['etudiant' => $enfant], ['dateCreation' => 'DESC']),
            ];
        }

        return $this->render('parent/dashboard.html.twig', [
            'parent' => $parent,
            'enfantsStats' => $enfantsStats,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/enfant/{id}/notes', name: 'parent_enfant_notes')]
    public function enfantNotes(int $id): Response
    {
        $user = $this->getUser();
        $parent = $user->getParentEleve();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$parent) {
            throw $this->createAccessDeniedException();
        }

        $enfant = $this->entityManager->getRepository(Etudiant::class)->find($id);

        if (!$enfant || !$parent->getEnfants()->contains($enfant)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès aux informations de cet étudiant.');
        }

        $notes = $this->noteRepository->findBy([
            'etudiant' => $enfant,
            'periodeAcademique' => $periodeActive
        ], ['dateNote' => 'DESC']);

        // Organiser par matière
        $notesParMatiere = [];
        foreach ($notes as $note) {
            $matiereNom = $note->getMatiere()->getNom();
            if (!isset($notesParMatiere[$matiereNom])) {
                $notesParMatiere[$matiereNom] = [
                    'matiere' => $note->getMatiere(),
                    'notes' => [],
                    'moyenne' => 0,
                ];
            }
            $notesParMatiere[$matiereNom]['notes'][] = $note;
        }

        // Calculer les moyennes
        foreach ($notesParMatiere as $matiere => &$data) {
            $somme = array_sum(array_map(fn($note) => $note->getValeur(), $data['notes']));
            $data['moyenne'] = count($data['notes']) > 0 ? round($somme / count($data['notes']), 2) : 0;
        }

        return $this->render('parent/enfant/notes.html.twig', [
            'enfant' => $enfant,
            'notesParMatiere' => $notesParMatiere,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/enfant/{id}/bulletins', name: 'parent_enfant_bulletins')]
    public function enfantBulletins(int $id): Response
    {
        $user = $this->getUser();
        $parent = $user->getParentEleve();

        if (!$parent) {
            throw $this->createAccessDeniedException();
        }

        $enfant = $this->entityManager->getRepository(Etudiant::class)->find($id);

        if (!$enfant || !$parent->getEnfants()->contains($enfant)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès aux informations de cet étudiant.');
        }

        $bulletins = $this->bulletinRepository->findBy(['etudiant' => $enfant], ['dateCreation' => 'DESC']);

        return $this->render('parent/enfant/bulletins.html.twig', [
            'enfant' => $enfant,
            'bulletins' => $bulletins,
        ]);
    }

    #[Route('/enfant/{id}/absences', name: 'parent_enfant_absences')]
    public function enfantAbsences(int $id): Response
    {
        $user = $this->getUser();
        $parent = $user->getParentEleve();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$parent) {
            throw $this->createAccessDeniedException();
        }

        $enfant = $this->entityManager->getRepository(Etudiant::class)->find($id);

        if (!$enfant || !$parent->getEnfants()->contains($enfant)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès aux informations de cet étudiant.');
        }

        $absences = $this->absenceRepository->findByEtudiant($enfant, $periodeActive);

        $stats = [
            'total' => count($absences),
            'justifiees' => $this->absenceRepository->countByEtudiant($enfant, $periodeActive, true),
            'non_justifiees' => $this->absenceRepository->countByEtudiant($enfant, $periodeActive, false),
            'taux_assiduite' => $this->absenceRepository->calculateTauxAssiduite($enfant, $periodeActive),
        ];

        return $this->render('parent/enfant/absences.html.twig', [
            'enfant' => $enfant,
            'absences' => $absences,
            'stats' => $stats,
            'periodeActive' => $periodeActive,
        ]);
    }
}

