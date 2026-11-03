<?php

namespace App\Controller;

use App\Entity\Chapitre;
use App\Entity\Cours;
use App\Entity\EmploiDuTemps;
use App\Entity\Etudiant;
use App\Entity\Evaluation;
use App\Entity\Note;
use App\Entity\PieceJointe;
use App\Repository\CoursRepository;
use App\Repository\NoteRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/etudiant')]
#[IsGranted('ROLE_ETUDIANT')]
class EtudiantController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PeriodeAcademiqueService $periodeService
    ) {}

    #[Route('/dashboard', name: 'etudiant_dashboard', methods: ['GET'])]
    public function dashboard(CoursRepository $coursRepository, NoteRepository $noteRepository): Response
    {
        $etudiant = $this->getEtudiant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        $cours = $coursRepository->findByFiliereAndPeriode($etudiant->getFiliere(), $periodeActive);
        $notes = $this->findNotes($etudiant);

        $totalNotes = count($notes);
        $moyenneGenerale = $totalNotes > 0
            ? round(array_sum(array_map(fn (Note $note) => $note->getValeur(), $notes)) / $totalNotes, 2)
            : 0;

        return $this->render('etudiant/dashboard.html.twig', [
            'etudiant' => $etudiant,
            'periodeActive' => $periodeActive,
            'totalCours' => count($cours),
            'totalNotes' => $totalNotes,
            'moyenneGenerale' => $moyenneGenerale,
            'derniersCours' => array_slice($cours, 0, 5),
            'dernieresNotes' => array_slice($notes, 0, 5),
        ]);
    }

    #[Route('/cours', name: 'etudiant_cours_index', methods: ['GET'])]
    public function coursIndex(CoursRepository $coursRepository): Response
    {
        $etudiant = $this->getEtudiant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        return $this->render('etudiant/cours/index.html.twig', [
            'cours' => $coursRepository->findByFiliereAndPeriode($etudiant->getFiliere(), $periodeActive),
            'etudiant' => $etudiant,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/cours/{id}', name: 'etudiant_cours_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function coursShow(Cours $cours): Response
    {
        $this->denyUnlessCoursAccessible($cours);

        return $this->render('etudiant/cours/show.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/chapitre/{id}', name: 'etudiant_chapitre_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function chapitreShow(Chapitre $chapitre): Response
    {
        $this->denyUnlessCoursAccessible($chapitre->getCours());

        return $this->render('etudiant/chapitre/show.html.twig', [
            'chapitre' => $chapitre,
            'cours' => $chapitre->getCours(),
        ]);
    }

    #[Route('/piece-jointe/{id}/download', name: 'etudiant_piece_jointe_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadPieceJointe(PieceJointe $pieceJointe): BinaryFileResponse
    {
        $this->denyUnlessCoursAccessible($pieceJointe->getChapitre()?->getCours());

        $filePath = $this->getParameter('pieces_jointes_directory').'/'.$pieceJointe->getFichier();
        if (!$pieceJointe->getFichier() || !is_file($filePath)) {
            throw $this->createNotFoundException('Le fichier n\'existe pas.');
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $nom = $pieceJointe->getNom() ?: 'document';
        if ($extension && !str_ends_with(strtolower($nom), '.'.strtolower($extension))) {
            $nom .= '.'.$extension;
        }

        return $this->file($filePath, $nom, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    #[Route('/notes', name: 'etudiant_notes_index', methods: ['GET'])]
    public function notesIndex(): Response
    {
        $etudiant = $this->getEtudiant();
        $notes = $this->findNotes($etudiant);

        $statistiquesParMatiere = [];
        foreach ($notes as $note) {
            $nomMatiere = $note->getMatiere()?->getNom() ?? 'Sans matière';
            $statistiquesParMatiere[$nomMatiere]['valeurs'][] = $note->getValeur();
        }
        foreach ($statistiquesParMatiere as $nomMatiere => $data) {
            $statistiquesParMatiere[$nomMatiere] = [
                'total' => count($data['valeurs']),
                'moyenne' => round(array_sum($data['valeurs']) / count($data['valeurs']), 2),
            ];
        }
        ksort($statistiquesParMatiere);

        return $this->render('etudiant/notes/index.html.twig', [
            'notes' => $notes,
            'etudiant' => $etudiant,
            'statistiquesParMatiere' => $statistiquesParMatiere,
            'periodeActive' => $this->periodeService->getPeriodeActive(),
        ]);
    }

    #[Route('/evaluations', name: 'etudiant_evaluations_index', methods: ['GET'])]
    public function evaluationsIndex(): Response
    {
        $etudiant = $this->getEtudiant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        $qb = $this->entityManager->getRepository(Evaluation::class)
            ->createQueryBuilder('e')
            ->join('e.matiere', 'm')
            ->where('m.filiere = :filiere')
            ->setParameter('filiere', $etudiant->getFiliere())
            ->orderBy('e.dateEvaluation', 'DESC');

        if ($periodeActive) {
            $qb->andWhere('e.periodeAcademique = :periode')
                ->setParameter('periode', $periodeActive);
        }

        return $this->render('etudiant/evaluations/index.html.twig', [
            'evaluations' => $qb->getQuery()->getResult(),
            'etudiant' => $etudiant,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/matieres', name: 'etudiant_matieres_index', methods: ['GET'])]
    public function matieresIndex(): Response
    {
        $etudiant = $this->getEtudiant();

        return $this->render('etudiant/matieres/index.html.twig', [
            'matieres' => $etudiant->getFiliere()?->getMatieres() ?? [],
            'etudiant' => $etudiant,
            'periodeActive' => $this->periodeService->getPeriodeActive(),
        ]);
    }

    #[Route('/emploi-du-temps', name: 'etudiant_emploi_du_temps', methods: ['GET'])]
    public function emploiDuTemps(): Response
    {
        $etudiant = $this->getEtudiant();

        $emplois = $etudiant->getFiliere()
            ? $this->entityManager->getRepository(EmploiDuTemps::class)->createQueryBuilder('e')
                ->leftJoin('e.jour', 'j')
                ->addSelect('j')
                ->where('e.filiere = :filiere')
                ->setParameter('filiere', $etudiant->getFiliere())
                ->orderBy('j.ordre', 'ASC')
                ->addOrderBy('e.heureDebut', 'ASC')
                ->getQuery()
                ->getResult()
            : [];

        $emploisParJour = [];
        $heures = [];
        $totalHeures = 0;
        foreach ($emplois as $emploi) {
            if (!$emploi->getJour() || !$emploi->getHeureDebut()) {
                continue;
            }
            $emploisParJour[$emploi->getJour()->getNom()][] = $emploi;
            $heures[] = $emploi->getHeureDebut()->format('H:i');
            if ($emploi->getHeureFin()) {
                $totalHeures += ($emploi->getHeureFin()->getTimestamp() - $emploi->getHeureDebut()->getTimestamp()) / 3600;
            }
        }
        $heures = array_values(array_unique($heures));
        sort($heures);

        return $this->render('etudiant/emploi_du_temps.html.twig', [
            'etudiant' => $etudiant,
            'emplois_par_jour' => $emploisParJour,
            'heures' => $heures,
            'total_cours' => array_sum(array_map('count', $emploisParJour)),
            'total_heures' => $totalHeures,
            'periodeActive' => $this->periodeService->getPeriodeActive(),
        ]);
    }

    private function getEtudiant(): Etudiant
    {
        $etudiant = $this->getUser()?->getEtudiant();
        if (!$etudiant) {
            throw $this->createAccessDeniedException('Aucun profil étudiant n\'est associé à ce compte.');
        }

        return $etudiant;
    }

    /**
     * @return Note[]
     */
    private function findNotes(Etudiant $etudiant): array
    {
        $criteres = ['etudiant' => $etudiant];
        if ($periodeActive = $this->periodeService->getPeriodeActive()) {
            $criteres['periodeAcademique'] = $periodeActive;
        }

        return $this->entityManager->getRepository(Note::class)->findBy($criteres, ['dateNote' => 'DESC']);
    }

    private function denyUnlessCoursAccessible(?Cours $cours): void
    {
        $filiere = $this->getEtudiant()->getFiliere();
        if (!$cours || !$filiere || $cours->getMatiere()?->getFiliere() !== $filiere) {
            throw $this->createAccessDeniedException('Ce cours n\'est pas accessible depuis votre filière.');
        }
    }
}
