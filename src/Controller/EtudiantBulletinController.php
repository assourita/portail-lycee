<?php

namespace App\Controller;

use App\Entity\Bulletin;
use App\Entity\Matiere;
use App\Entity\Note;
use App\Repository\BulletinRepository;
use App\Repository\MatiereRepository;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/etudiant/bulletins')]
#[IsGranted('ROLE_ETUDIANT')]
class EtudiantBulletinController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BulletinRepository $bulletinRepository,
        private MatiereRepository $matiereRepository,
        private NoteRepository $noteRepository
    ) {}

    #[Route('/', name: 'etudiant_bulletins_index', methods: ['GET'])]
    public function index(): Response
    {
        $etudiant = $this->getUser()->getEtudiant();
        
        if (!$etudiant) {
            throw $this->createNotFoundException('Étudiant non trouvé.');
        }

        // Récupérer tous les bulletins de l'étudiant
        $bulletins = $this->bulletinRepository->findBy(
            ['etudiant' => $etudiant],
            ['dateCreation' => 'DESC']
        );

        return $this->render('etudiant/bulletin/index.html.twig', [
            'bulletins' => $bulletins,
            'etudiant' => $etudiant,
        ]);
    }

    #[Route('/{id}', name: 'etudiant_bulletin_show', methods: ['GET'])]
    public function show(Bulletin $bulletin): Response
    {
        $etudiant = $this->getUser()->getEtudiant();
        
        if (!$etudiant || $bulletin->getEtudiant() !== $etudiant) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce bulletin.');
        }

        $filiere = $etudiant->getFiliere();
        
        // Récupérer toutes les matières de la filière
        $matieres = $this->matiereRepository->findBy(['filiere' => $filiere]);

        // Récupérer toutes les notes de l'étudiant
        $notes = $this->noteRepository->findBy(['etudiant' => $etudiant]);

        // Organiser les notes par matière
        $notesParMatiere = [];
        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($matieres as $matiere) {
            $notesMatiere = array_filter($notes, function($note) use ($matiere) {
                return $note->getMatiere() === $matiere;
            });

            if (!empty($notesMatiere)) {
                $sommeNotes = array_sum(array_map(function($note) { return $note->getValeur(); }, $notesMatiere));
                $moyenne = $sommeNotes / count($notesMatiere);
                $coefficient = $matiere->getCoeficient();
                
                $notesParMatiere[] = [
                    'matiere' => $matiere,
                    'notes' => $notesMatiere,
                    'moyenne' => $moyenne,
                    'coefficient' => $coefficient
                ];

                $totalPoints += $moyenne * $coefficient;
                $totalCoefficients += $coefficient;
            }
        }

        $moyenneGenerale = $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : 0;

        // Calculer le rang dans la classe (approximatif)
        $rang = $this->calculerRangEtudiant($etudiant, $moyenneGenerale);

        return $this->render('etudiant/bulletin/show.html.twig', [
            'bulletin' => $bulletin,
            'etudiant' => $etudiant,
            'filiere' => $filiere,
            'notesParMatiere' => $notesParMatiere,
            'moyenneGenerale' => $moyenneGenerale,
            'totalCoefficients' => $totalCoefficients,
            'totalPoints' => $totalPoints,
            'rang' => $rang,
        ]);
    }

    /**
     * Calculer le rang approximatif de l'étudiant dans sa classe
     */
    private function calculerRangEtudiant($etudiant, float $moyenne): int
    {
        $filiere = $etudiant->getFiliere();
        if (!$filiere) {
            return 0;
        }

        // Récupérer tous les étudiants de la filière
        $etudiantsFiliere = $this->entityManager->getRepository(\App\Entity\Etudiant::class)
            ->findBy(['filiere' => $filiere]);

        $moyennesEtudiants = [];
        
        foreach ($etudiantsFiliere as $etudiantFiliere) {
            $moyenneEtudiant = $this->calculerMoyenneEtudiant($etudiantFiliere);
            $moyennesEtudiants[] = [
                'etudiant' => $etudiantFiliere,
                'moyenne' => $moyenneEtudiant
            ];
        }

        // Trier par moyenne décroissante
        usort($moyennesEtudiants, function($a, $b) {
            return $b['moyenne'] <=> $a['moyenne'];
        });

        // Trouver le rang
        foreach ($moyennesEtudiants as $index => $data) {
            if ($data['etudiant']->getId() === $etudiant->getId()) {
                return $index + 1;
            }
        }

        return count($moyennesEtudiants);
    }

    /**
     * Calculer la moyenne d'un étudiant
     */
    private function calculerMoyenneEtudiant($etudiant): float
    {
        $filiere = $etudiant->getFiliere();
        if (!$filiere) {
            return 0;
        }

        $matieres = $this->matiereRepository->findBy(['filiere' => $filiere]);
        $notes = $this->noteRepository->findBy(['etudiant' => $etudiant]);

        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($matieres as $matiere) {
            $notesMatiere = array_filter($notes, function($note) use ($matiere) {
                return $note->getMatiere() === $matiere;
            });

            if (!empty($notesMatiere)) {
                $sommeNotes = array_sum(array_map(function($note) { return $note->getValeur(); }, $notesMatiere));
                $moyenne = $sommeNotes / count($notesMatiere);
                $coefficient = $matiere->getCoeficient();
                
                $totalPoints += $moyenne * $coefficient;
                $totalCoefficients += $coefficient;
            }
        }

        return $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : 0;
    }
}
