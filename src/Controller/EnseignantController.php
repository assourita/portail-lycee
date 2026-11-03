<?php
// src/Controller/EnseignantController.php
namespace App\Controller;

use App\Repository\CoursRepository;
use App\Service\PeriodeAcademiqueService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\EmploiDuTemps;
use App\Entity\Filiere;
use App\Entity\Matiere;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/enseignant')]
#[IsGranted('ROLE_ENSEIGNANT')]
class EnseignantController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/dashboard', name: 'enseignant_dashboard')]
    public function dashboard(CoursRepository $coursRepository, PeriodeAcademiqueService $periodeService): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        $periodeActive = $periodeService->getPeriodeActive();
        
        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }
        
        // Récupérer les cours de l'enseignant pour la période active
        $coursCriteria = ['enseignant' => $enseignant];
        if ($periodeActive) {
            $coursCriteria['periodeAcademique'] = $periodeActive;
        }
        $cours = $coursRepository->findBy($coursCriteria, ['dateAjout' => 'DESC']);

        // Calculer les statistiques
        $totalEtudiants = 0;
        $matieres = $enseignant->getMatieres();
        foreach ($matieres as $matiere) {
            if ($matiere->getFiliere()) {
                $totalEtudiants += $matiere->getFiliere()->getEtudiants()->count();
            }
        }

        // Cours cette semaine (exemple - à adapter selon vos besoins)
        $coursCetteSemaine = 12; // À calculer selon l'emploi du temps

        // Devoirs à corriger (exemple - à adapter selon vos besoins)
        $devoirsACorriger = 18; // À calculer selon les notes en attente

        return $this->render('enseignant/dashboard.html.twig', [
            'cours' => $cours,
            'totalEtudiants' => $totalEtudiants,
            'coursCetteSemaine' => $coursCetteSemaine,
            'devoirsACorriger' => $devoirsACorriger,
            'periodeActive' => $periodeActive,
        ]);
    }

    /**
     * Affiche l'emploi du temps de l'enseignant
     */
    #[Route('/emploi-du-temps', name: 'app_enseignant_emploi_du_temps')]
    public function emploiDuTemps(Request $request): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        
        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }
        
        $emploiDuTemps = $this->entityManager->getRepository(EmploiDuTemps::class)
            ->findBy(['enseignant' => $enseignant], ['jour' => 'ASC', 'heureDebut' => 'ASC']);

        return $this->render('enseignant/emploi_du_temps.html.twig', [
            'emploiDuTemps' => $emploiDuTemps,
        ]);
    }

    /**
     * Affiche les filières et matières de l'enseignant
     */
    #[Route('/mes-filieres', name: 'app_enseignant_mes_filieres')]
    public function mesFilieres(Request $request): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        
        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }
        
        $matieres = $this->entityManager->getRepository(Matiere::class)
            ->findBy(['enseignant' => $enseignant]);

        // Récupérer les filières uniques
        $filieres = [];
        foreach ($matieres as $matiere) {
            if ($matiere->getFiliere() && !in_array($matiere->getFiliere(), $filieres)) {
                $filieres[] = $matiere->getFiliere();
            }
        }

        return $this->render('enseignant/mes_filieres.html.twig', [
            'filieres' => $filieres,
            'matieres' => $matieres,
        ]);
    }

    /**
     * Affiche la liste des étudiants d'une filière
     */
    #[Route('/filiere/{id}/etudiants', name: 'enseignant_filiere_etudiants')]
    public function listeEtudiants(Request $request, int $id): Response
    {
        $filiere = $this->entityManager->getRepository(Filiere::class)->find($id);
        
        if (!$filiere) {
            throw $this->createNotFoundException('Filière non trouvée');
        }

        $etudiants = $filiere->getEtudiants();

        return $this->render('enseignant/liste_etudiants.html.twig', [
            'filiere' => $filiere,
            'etudiants' => $etudiants,
        ]);
    }

    /**
     * Affiche les matières assignées à l'enseignant
     */
    #[Route('/mes-matieres', name: 'enseignant_mes_matieres')]
    public function mesMatieres(): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        
        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }
        
        $matieres = $this->entityManager->getRepository(Matiere::class)
            ->findBy(['enseignant' => $enseignant]);

        return $this->render('enseignant/mes_matieres.html.twig', [
            'matieres' => $matieres,
        ]);
    }

    /**
     * Affiche les cours d'une matière spécifique
     */
    #[Route('/matiere/{id}/cours', name: 'enseignant_matiere_cours')]
    public function matiereCours(int $id): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        
        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }

        $matiere = $this->entityManager->getRepository(Matiere::class)->find($id);
        
        if (!$matiere || $matiere->getEnseignant() !== $enseignant) {
            throw $this->createNotFoundException('Matière non trouvée ou non assignée à cet enseignant');
        }

        $cours = $this->entityManager->getRepository(\App\Entity\Cours::class)
            ->findBy(['matiere' => $matiere], ['dateAjout' => 'DESC']);

        return $this->render('enseignant/matiere/cours.html.twig', [
            'matiere' => $matiere,
            'cours' => $cours,
        ]);
    }

    /**
     * Affiche les notes d'une matière spécifique
     */
    #[Route('/matiere/{id}/notes', name: 'enseignant_matiere_notes')]
    public function matiereNotes(int $id): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        
        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }

        $matiere = $this->entityManager->getRepository(Matiere::class)->find($id);
        
        if (!$matiere || $matiere->getEnseignant() !== $enseignant) {
            throw $this->createNotFoundException('Matière non trouvée ou non assignée à cet enseignant');
        }

        $notes = $this->entityManager->getRepository(\App\Entity\Note::class)
            ->findBy(['matiere' => $matiere, 'enseignant' => $enseignant], ['dateNote' => 'DESC']);

        return $this->render('enseignant/matiere/notes.html.twig', [
            'matiere' => $matiere,
            'notes' => $notes,
        ]);
    }

    /**
     * Ajoute des notes en masse pour une matière
     */
    #[Route('/matiere/{id}/notes/add', name: 'enseignant_matiere_notes_add', methods: ['POST'])]
    public function matiereNotesAdd(Request $request, int $id): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        
        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }

        $matiere = $this->entityManager->getRepository(Matiere::class)->find($id);
        
        if (!$matiere || $matiere->getEnseignant() !== $enseignant) {
            throw $this->createNotFoundException('Matière non trouvée ou non assignée à cet enseignant');
        }

        $typeNote = $request->request->get('typeNote');
        $dateNote = $request->request->get('dateNote');
        $commentaireGlobal = $request->request->get('commentaireGlobal');
        $notes = $request->request->all('notes');
        $commentaires = $request->request->all('commentaires');

        if (!$typeNote || !$dateNote) {
            $this->addFlash('error', 'Le type de note et la date sont obligatoires.');
            return $this->redirectToRoute('enseignant_matiere_notes', ['id' => $id]);
        }

        $dateNoteObj = new \DateTime($dateNote);
        $notesAjoutees = 0;

        foreach ($notes as $etudiantId => $valeurNote) {
            if (!empty($valeurNote) && is_numeric($valeurNote)) {
                $etudiant = $this->entityManager->getRepository(\App\Entity\Etudiant::class)->find($etudiantId);
                
                if ($etudiant && $etudiant->getFiliere() === $matiere->getFiliere()) {
                    $note = new \App\Entity\Note();
                    $note->setValeur($valeurNote);
                    $note->setType($typeNote);
                    $note->setDateNote($dateNoteObj);
                    $note->setEtudiant($etudiant);
                    $note->setMatiere($matiere);
                    $note->setEnseignant($enseignant);
                    
                    // Commentaire individuel ou global
                    $commentaire = $commentaires[$etudiantId] ?? $commentaireGlobal;
                    if (!empty($commentaire)) {
                        $note->setCommentaire($commentaire);
                    }
                    
                    $this->entityManager->persist($note);
                    $notesAjoutees++;
                }
            }
        }

        if ($notesAjoutees > 0) {
            $this->entityManager->flush();
            $this->addFlash('success', "{$notesAjoutees} note(s) ajoutée(s) avec succès.");
        } else {
            $this->addFlash('warning', 'Aucune note valide n\'a été ajoutée.');
        }

        return $this->redirectToRoute('enseignant_matiere_notes', ['id' => $id]);
    }
}
