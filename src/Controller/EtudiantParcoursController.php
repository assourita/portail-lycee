<?php

namespace App\Controller;

use App\Repository\ParcoursRepository;
use App\Repository\NoteRepository;
use App\Repository\AbsenceRepository;
use App\Repository\BulletinRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/etudiant/parcours')]
#[IsGranted('ROLE_ETUDIANT')]
class EtudiantParcoursController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParcoursRepository $parcoursRepository,
        private NoteRepository $noteRepository,
        private AbsenceRepository $absenceRepository,
        private BulletinRepository $bulletinRepository,
        private PeriodeAcademiqueService $periodeService
    ) {}

    #[Route('/', name: 'etudiant_parcours_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$etudiant) {
            $this->addFlash('error', 'Vous devez être un étudiant pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'historique du parcours
        $parcours = $this->parcoursRepository->findByEtudiant($etudiant);

        // Calculer les statistiques de l'année en cours
        $statsAnneeEnCours = $this->calculerStatistiquesAnnee($etudiant, $periodeActive);

        return $this->render('etudiant/parcours/index.html.twig', [
            'etudiant' => $etudiant,
            'parcours' => $parcours,
            'statsAnneeEnCours' => $statsAnneeEnCours,
            'periodeActive' => $periodeActive,
        ]);
    }

    private function calculerStatistiquesAnnee($etudiant, $periodeActive): array
    {
        // Notes
        $notes = $this->noteRepository->findBy([
            'etudiant' => $etudiant,
            'periodeAcademique' => $periodeActive
        ]);

        $moyenneGenerale = 0;
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

        // Calculer les moyennes par matière
        foreach ($notesParMatiere as $matiere => &$data) {
            $somme = array_sum(array_map(fn($note) => $note->getValeur(), $data['notes']));
            $data['moyenne'] = count($data['notes']) > 0 ? round($somme / count($data['notes']), 2) : 0;
        }

        if (count($notes) > 0) {
            $somme = array_sum(array_map(fn($note) => $note->getValeur(), $notes));
            $moyenneGenerale = round($somme / count($notes), 2);
        }

        // Absences
        $totalAbsences = $this->absenceRepository->countByEtudiant($etudiant, $periodeActive);
        $absencesJustifiees = $this->absenceRepository->countByEtudiant($etudiant, $periodeActive, true);
        $tauxAssiduite = $this->absenceRepository->calculateTauxAssiduite($etudiant, $periodeActive);

        // Bulletins
        $bulletins = $this->bulletinRepository->findBy(['etudiant' => $etudiant]);

        return [
            'moyenne_generale' => $moyenneGenerale,
            'total_notes' => count($notes),
            'notes_par_matiere' => $notesParMatiere,
            'total_absences' => $totalAbsences,
            'absences_justifiees' => $absencesJustifiees,
            'absences_non_justifiees' => $totalAbsences - $absencesJustifiees,
            'taux_assiduite' => $tauxAssiduite,
            'total_bulletins' => count($bulletins),
        ];
    }
}

