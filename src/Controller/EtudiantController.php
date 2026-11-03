<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\Cours;
use App\Entity\Note;
use App\Repository\CoursRepository;
use App\Repository\NoteRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ETUDIANT')]
class EtudiantController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function dashboard(CoursRepository $coursRepository, NoteRepository $noteRepository, PeriodeAcademiqueService $periodeService): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();
        $periodeActive = $periodeService->getPeriodeActive();

        if (!$etudiant) {
            $this->addFlash('error', 'Vous devez être un étudiant pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les cours de la filière de l'étudiant pour la période active
        $cours = $coursRepository->findByFiliereAndPeriode($etudiant->getFiliere(), $periodeActive);
        
        // Récupérer les notes de l'étudiant pour la période active
        $notesCriteria = ['etudiant' => $etudiant];
        if ($periodeActive) {
            $notesCriteria['periodeAcademique'] = $periodeActive;
        }
        $notes = $noteRepository->findBy($notesCriteria, ['dateNote' => 'DESC']);
        
        // Calculer les statistiques
        $totalCours = count($cours);
        $totalNotes = count($notes);
        $moyenneGenerale = 0;
        
        if ($totalNotes > 0) {
            $total = 0;
            foreach ($notes as $note) {
                $total += $note->getValeur();
            }
            $moyenneGenerale = round($total / $totalNotes, 2);
        }

        return $this->render('etudiant/dashboard.html.twig', [
            'etudiant' => $etudiant,
            'periodeActive' => $periodeActive,
            'totalCours' => $totalCours,
            'totalNotes' => $totalNotes,
            'moyenneGenerale' => $moyenneGenerale,
            'derniersCours' => array_slice($cours, 0, 5),
            'dernieresNotes' => array_slice($notes, 0, 5)
        ]);
    }

    #[Route('/etudiant/cours', name: 'etudiant_cours_index')]
    public function coursIndex(PeriodeAcademiqueService $periodeService): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();
        $periodeActive = $periodeService->getPeriodeActive();

        if (!$etudiant) {
            $this->addFlash('error', 'Vous devez être un étudiant pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        $coursRepository = $this->entityManager->getRepository(Cours::class);
        $cours = $coursRepository->findByFiliereAndPeriode($etudiant->getFiliere(), $periodeActive);

        return $this->render('etudiant/cours/index.html.twig', [
            'cours' => $cours,
            'etudiant' => $etudiant,
            'periodeActive' => $periodeActive
        ]);
    }

    #[Route('/etudiant/notes', name: 'etudiant_notes_index')]
    public function notesIndex(PeriodeAcademiqueService $periodeService): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();
        $periodeActive = $periodeService->getPeriodeActive();

        if (!$etudiant) {
            $this->addFlash('error', 'Vous devez être un étudiant pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        $noteRepository = $this->entityManager->getRepository(Note::class);
        $notesCriteria = ['etudiant' => $etudiant];
        if ($periodeActive) {
            $notesCriteria['periodeAcademique'] = $periodeActive;
        }
        $notes = $noteRepository->findBy($notesCriteria, ['dateNote' => 'DESC']);

        return $this->render('etudiant/notes/index.html.twig', [
            'notes' => $notes,
            'etudiant' => $etudiant,
            'periodeActive' => $periodeActive
        ]);
    }

    #[Route('/etudiant/evaluations', name: 'etudiant_evaluations_index')]
    public function evaluationsIndex(PeriodeAcademiqueService $periodeService): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();
        $periodeActive = $periodeService->getPeriodeActive();

        if (!$etudiant) {
            $this->addFlash('error', 'Vous devez être un étudiant pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        $evaluations = $this->entityManager->getRepository(\App\Entity\Evaluation::class)
            ->createQueryBuilder('e')
            ->join('e.matiere', 'm')
            ->where('m.filiere = :filiere')
            ->setParameter('filiere', $etudiant->getFiliere())
            ->orderBy('e.dateEvaluation', 'DESC');
                
        if ($periodeActive) {
            $evaluations->andWhere('e.periodeAcademique = :periode')
                       ->setParameter('periode', $periodeActive);
        }
            
        $evaluations = $evaluations->getQuery()->getResult();

        return $this->render('etudiant/evaluations/index.html.twig', [
            'evaluations' => $evaluations,
            'etudiant' => $etudiant,
            'periodeActive' => $periodeActive
        ]);
    }

    #[Route('/etudiant/matieres', name: 'etudiant_matieres_index')]
    public function matieresIndex(PeriodeAcademiqueService $periodeService): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();
        $periodeActive = $periodeService->getPeriodeActive();

        if (!$etudiant) {
            $this->addFlash('error', 'Vous devez être un étudiant pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les matières de la filière de l'étudiant
        $matieres = $etudiant->getFiliere()->getMatieres();

        return $this->render('etudiant/matieres/index.html.twig', [
            'matieres' => $matieres,
            'etudiant' => $etudiant,
            'periodeActive' => $periodeActive
        ]);
    }

    #[Route('/etudiant/devoirs', name: 'etudiant_devoirs_index')]
    public function devoirsIndex(PeriodeAcademiqueService $periodeService): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();
        $periodeActive = $periodeService->getPeriodeActive();

        if (!$etudiant) {
            $this->addFlash('error', 'Vous devez être un étudiant pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        // Pour l'instant, simulons des devoirs
        $devoirs = [
            ['id' => 1, 'matiere' => 'Mathématiques', 'titre' => 'Exercices sur les fonctions', 'dateLimite' => new \DateTime('+3 days'), 'rendu' => false],
            ['id' => 2, 'matiere' => 'Français', 'titre' => 'Rédaction dissertation', 'dateLimite' => new \DateTime('+5 days'), 'rendu' => false],
            ['id' => 3, 'matiere' => 'Physique-Chimie', 'titre' => 'TP laboratoire', 'dateLimite' => new \DateTime('+7 days'), 'rendu' => true],
        ];

        return $this->render('etudiant/devoirs/index.html.twig', [
            'devoirs' => $devoirs,
            'etudiant' => $etudiant,
            'periodeActive' => $periodeActive
        ]);
    }

    #[Route('/etudiant/absences', name: 'etudiant_absences_index')]
    public function absencesIndex(PeriodeAcademiqueService $periodeService): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();
        $periodeActive = $periodeService->getPeriodeActive();

        if (!$etudiant) {
            $this->addFlash('error', 'Vous devez être un étudiant pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        // Pour l'instant, simulons des absences
        $absences = [
            ['id' => 1, 'date' => new \DateTime('-2 days'), 'matiere' => 'Mathématiques', 'motif' => 'Maladie', 'justifiee' => true],
            ['id' => 2, 'date' => new \DateTime('-1 week'), 'matiere' => 'Histoire', 'motif' => 'Rendez-vous médical', 'justifiee' => true],
            ['id' => 3, 'date' => new \DateTime('-2 weeks'), 'matiere' => 'Anglais', 'motif' => 'Retard', 'justifiee' => false],
        ];

        return $this->render('etudiant/absences/index.html.twig', [
            'absences' => $absences,
            'etudiant' => $etudiant,
            'periodeActive' => $periodeActive
        ]);
    }

    #[Route('/etudiant/bulletins', name: 'etudiant_bulletins_index')]
    public function bulletinsIndex(PeriodeAcademiqueService $periodeService): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();
        $periodeActive = $periodeService->getPeriodeActive();

        if (!$etudiant) {
            $this->addFlash('error', 'Vous devez être un étudiant pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        // Pour l'instant, simulons des bulletins
        $bulletins = [
            ['id' => 1, 'trimestre' => '1', 'periode' => '2023-2024', 'moyenne' => 14.5, 'date' => new \DateTime('-2 months')],
            ['id' => 2, 'trimestre' => '2', 'periode' => '2023-2024', 'moyenne' => 15.2, 'date' => new \DateTime('-1 month')],
        ];

        return $this->render('etudiant/bulletins/index.html.twig', [
            'bulletins' => $bulletins,
            'etudiant' => $etudiant,
            'periodeActive' => $periodeActive
        ]);
    }

    #[Route('/etudiant/parcours', name: 'etudiant_parcours_index')]
    public function parcoursIndex(PeriodeAcademiqueService $periodeService): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();
        $periodeActive = $periodeService->getPeriodeActive();

        if (!$etudiant) {
            $this->addFlash('error', 'Vous devez être un étudiant pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        // Informations sur le parcours scolaire
        $parcours = [
            'filiere' => $etudiant->getFiliere(),
            'dateInscription' => $etudiant->getDateInscription(),
            'niveau' => $etudiant->getFiliere()->getNiveau(),
            'specialite' => $etudiant->getFiliere()->getSpecialite() ?? 'Non spécifiée',
            'objectifs' => [
                'Acquisition des compétences fondamentales',
                'Développement de l\'autonomie',
                'Préparation aux études supérieures'
            ],
            'competences' => [
                'Maîtrise des savoirs disciplinaires',
                'Compétences méthodologiques',
                'Compétences numériques',
                'Compétences sociales et civiques'
            ]
        ];

        return $this->render('etudiant/parcours/index.html.twig', [
            'parcours' => $parcours,
            'etudiant' => $etudiant,
            'periodeActive' => $periodeActive
        ]);
    }

    #[Route('/etudiant/emploi-du-temps', name: 'etudiant_emploi_du_temps')]
    public function emploiDuTemps(PeriodeAcademiqueService $periodeService): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();
        $periodeActive = $periodeService->getPeriodeActive();

        if (!$etudiant) {
            $this->addFlash('error', 'Vous devez être un étudiant pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('etudiant/emploi_du_temps.html.twig', [
            'etudiant' => $etudiant,
            'periodeActive' => $periodeActive
        ]);
    }
}
