<?php

namespace App\Controller;

use App\Entity\Note;
use App\Repository\EtudiantRepository;
use App\Repository\EvaluationRepository;
use App\Repository\MatiereRepository;
use App\Repository\NoteRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/enseignant/notes')]
#[IsGranted('ROLE_ENSEIGNANT')]
class EnseignantNoteController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NoteRepository $noteRepository,
        private EtudiantRepository $etudiantRepository,
        private MatiereRepository $matiereRepository,
        private EvaluationRepository $evaluationRepository,
        private PeriodeAcademiqueService $periodeService
    ) {}

    #[Route('/', name: 'enseignant_notes_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }

        // Récupérer les notes saisies par cet enseignant
        $qb = $this->noteRepository->createQueryBuilder('n')
            ->andWhere('n.enseignant = :enseignant')
            ->setParameter('enseignant', $enseignant)
            ->orderBy('n.dateNote', 'DESC');

        if ($periodeActive) {
            $qb->andWhere('n.periodeAcademique = :periode')
               ->setParameter('periode', $periodeActive);
        }

        $notes = $qb->getQuery()->getResult();

        return $this->render('enseignant/note/index.html.twig', [
            'notes' => $notes,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/saisir', name: 'enseignant_note_saisir', methods: ['GET', 'POST'])]
    public function saisir(Request $request): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }

        if (!$periodeActive) {
            $this->addFlash('error', 'Aucune période académique active.');
            return $this->redirectToRoute('enseignant_notes_index');
        }

        if ($request->isMethod('POST')) {
            $matiereId = $request->request->get('matiere');
            $evaluationId = $request->request->get('evaluation');
            $etudiantId = $request->request->get('etudiant');
            $valeur = $request->request->get('valeur');
            $type = $request->request->get('type', 'devoir');
            $commentaire = $request->request->get('commentaire');

            if ($matiereId && $etudiantId && $valeur !== null) {
                $matiere = $this->matiereRepository->find($matiereId);
                $etudiant = $this->etudiantRepository->find($etudiantId);
                $evaluation = $evaluationId ? $this->evaluationRepository->find($evaluationId) : null;

                $noteMax = $evaluation?->getNoteMax() ?? 20;
                $valeur = str_replace(',', '.', (string) $valeur);

                if (!is_numeric($valeur) || $valeur < 0 || $valeur > $noteMax) {
                    $this->addFlash('error', sprintf('La note doit être comprise entre 0 et %d.', $noteMax));
                } elseif ($matiere && $matiere->getEnseignant() === $enseignant && $etudiant
                    && $etudiant->getFiliere() === $matiere->getFiliere()
                    && (!$evaluation || $evaluation->getMatiere() === $matiere)) {
                    $note = new Note();
                    $note->setValeur($valeur);
                    $note->setType($type);
                    $note->setCommentaire($commentaire);
                    $note->setEtudiant($etudiant);
                    $note->setMatiere($matiere);
                    $note->setEnseignant($enseignant);
                    $note->setPeriodeAcademique($periodeActive);
                    
                    if ($evaluation) {
                        $note->setEvaluation($evaluation);
                    }

                    $this->entityManager->persist($note);
                    $this->entityManager->flush();

                    $this->addFlash('success', 'Note enregistrée avec succès !');
                    return $this->redirectToRoute('enseignant_notes_index');
                } else {
                    $this->addFlash('error', 'Cet étudiant ou cette évaluation ne correspond pas à la matière choisie.');
                }
            } else {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            }
        }

        $matieres = $enseignant->getMatieres();

        return $this->render('enseignant/note/saisir.html.twig', [
            'matieres' => $matieres,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/saisir-groupe', name: 'enseignant_note_saisir_groupe', methods: ['GET', 'POST'])]
    public function saisirGroupe(Request $request): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }

        if (!$periodeActive) {
            $this->addFlash('error', 'Aucune période académique active.');
            return $this->redirectToRoute('enseignant_notes_index');
        }

        $matiereId = $request->query->get('matiere') ?? $request->request->get('matiere');
        $evaluationId = $request->query->get('evaluation') ?? $request->request->get('evaluation');

        $matiere = null;
        $evaluation = null;
        $etudiants = [];

        if ($matiereId) {
            $matiere = $this->matiereRepository->find($matiereId);

            if (!$matiere || $matiere->getEnseignant() !== $enseignant) {
                $matiere = null;
                $this->addFlash('error', 'Cette matière ne vous est pas attribuée.');
            } else {
                if ($matiere->getFiliere()) {
                    $etudiants = $this->etudiantRepository->findBy(['filiere' => $matiere->getFiliere()]);
                }

                if ($evaluationId) {
                    $evaluation = $this->evaluationRepository->find($evaluationId);
                    if ($evaluation && $evaluation->getMatiere() !== $matiere) {
                        $evaluation = null;
                    }
                }
            }
        }

        if ($request->isMethod('POST') && $matiere) {
            $notesData = $request->request->all('notes') ?? [];
            $type = $request->request->get('type', 'devoir');
            $commentaireGeneral = $request->request->get('commentaire_general');

            $notesCreees = 0;

            foreach ($notesData as $etudiantId => $noteData) {
                $valeur = $noteData['valeur'] ?? null;
                $commentaire = $noteData['commentaire'] ?? $commentaireGeneral;

                if ($valeur !== null && $valeur !== '') {
                    $etudiant = $this->etudiantRepository->find($etudiantId);
                    $valeur = str_replace(',', '.', (string) $valeur);
                    $noteMax = $evaluation?->getNoteMax() ?? 20;

                    if (!is_numeric($valeur) || $valeur < 0 || $valeur > $noteMax) {
                        $this->addFlash('warning', sprintf('Note ignorée pour %s : elle doit être comprise entre 0 et %d.', $etudiant ? $etudiant->getPrenom().' '.$etudiant->getNom() : '#'.$etudiantId, $noteMax));
                        continue;
                    }

                    if ($etudiant && $etudiant->getFiliere() === $matiere->getFiliere()) {
                        $note = new Note();
                        $note->setValeur($valeur);
                        $note->setType($type);
                        $note->setCommentaire($commentaire);
                        $note->setEtudiant($etudiant);
                        $note->setMatiere($matiere);
                        $note->setEnseignant($enseignant);
                        $note->setPeriodeAcademique($periodeActive);
                        
                        if ($evaluation) {
                            $note->setEvaluation($evaluation);
                        }

                        $this->entityManager->persist($note);
                        $notesCreees++;
                    }
                }
            }

            if ($notesCreees > 0) {
                $this->entityManager->flush();
                $this->addFlash('success', sprintf('%d note(s) enregistrée(s) avec succès !', $notesCreees));
                return $this->redirectToRoute('enseignant_notes_index');
            } else {
                $this->addFlash('error', 'Aucune note n\'a été saisie.');
            }
        }

        $matieres = $enseignant->getMatieres();

        return $this->render('enseignant/note/saisir_groupe.html.twig', [
            'matieres' => $matieres,
            'matiere' => $matiere,
            'evaluation' => $evaluation,
            'etudiants' => $etudiants,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/{id}/edit', name: 'enseignant_note_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Note $note): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();

        if ($note->getEnseignant() !== $enseignant) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette note.');
        }

        if ($request->isMethod('POST')) {
            $valeur = str_replace(',', '.', (string) $request->request->get('valeur'));
            $commentaire = $request->request->get('commentaire');
            $noteMax = $note->getEvaluation()?->getNoteMax() ?? 20;

            if (is_numeric($valeur) && $valeur >= 0 && $valeur <= $noteMax) {
                $note->setValeur($valeur);
                $note->setCommentaire($commentaire);

                $this->entityManager->flush();

                $this->addFlash('success', 'Note modifiée avec succès !');
                return $this->redirectToRoute('enseignant_notes_index');
            }

            $this->addFlash('error', sprintf('Veuillez saisir une note comprise entre 0 et %d.', $noteMax));
        }

        return $this->render('enseignant/note/edit.html.twig', [
            'note' => $note,
        ]);
    }

    #[Route('/{id}/delete', name: 'enseignant_note_delete', methods: ['POST'])]
    public function delete(Request $request, Note $note): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();

        if ($note->getEnseignant() !== $enseignant) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette note.');
        }

        if ($this->isCsrfTokenValid('delete-note', $request->request->get('_token'))) {
            $this->entityManager->remove($note);
            $this->entityManager->flush();

            $this->addFlash('success', 'Note supprimée avec succès !');
        }

        return $this->redirectToRoute('enseignant_notes_index');
    }

    #[Route('/api/evaluations/{matiereId}', name: 'enseignant_note_api_evaluations', methods: ['GET'])]
    public function getEvaluationsByMatiere(int $matiereId): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        $matiere = $this->matiereRepository->find($matiereId);

        if (!$matiere || $matiere->getEnseignant() !== $enseignant) {
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

    #[Route('/api/etudiants/{matiereId}', name: 'enseignant_note_api_etudiants', methods: ['GET'])]
    public function getEtudiantsByMatiere(int $matiereId): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();

        $matiere = $this->matiereRepository->find($matiereId);

        if (!$matiere || $matiere->getEnseignant() !== $enseignant) {
            return $this->json(['error' => 'Matière non trouvée'], 404);
        }

        if (!$matiere->getFiliere()) {
            return $this->json([]);
        }

        $etudiants = $this->etudiantRepository->findBy(['filiere' => $matiere->getFiliere()]);

        $etudiantsData = array_map(function($etudiant) {
            return [
                'id' => $etudiant->getId(),
                'nom' => $etudiant->getNom(),
                'prenom' => $etudiant->getPrenom(),
                'email' => $etudiant->getEmail(),
            ];
        }, $etudiants);

        return $this->json($etudiantsData);
    }
}
