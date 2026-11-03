<?php

namespace App\Controller;

use App\Entity\Bulletin;
use App\Entity\Classe;
use App\Entity\Etudiant;
use App\Entity\Filiere;
use App\Entity\Matiere;
use App\Entity\Note;
use App\Entity\Trimestre;
use App\Repository\BulletinRepository;
use App\Repository\ClasseRepository;
use App\Repository\EtudiantRepository;
use App\Repository\FiliereRepository;
use App\Repository\MatiereRepository;
use App\Repository\NoteRepository;
use App\Repository\TrimestreRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/bulletins')]
#[IsGranted('ROLE_ADMIN')]
class AdminBulletinController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BulletinRepository $bulletinRepository,
        private EtudiantRepository $etudiantRepository,
        private FiliereRepository $filiereRepository,
        private ClasseRepository $classeRepository,
        private TrimestreRepository $trimestreRepository,
        private MatiereRepository $matiereRepository,
        private NoteRepository $noteRepository,
        private PeriodeAcademiqueService $periodeService
    ) {}

    #[Route('/', name: 'admin_bulletins_index', methods: ['GET'])]
    public function index(): Response
    {
        $periodeActive = $this->periodeService->getPeriodeActive();
        $bulletins = $this->bulletinRepository->findAll();
        $filieres = $this->filiereRepository->findAll();

        return $this->render('admin/bulletin/index.html.twig', [
            'bulletins' => $bulletins,
            'filieres' => $filieres,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/new', name: 'admin_bulletin_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $filiereId = $request->query->get('filiere');
        $filiere = null;
        $etudiants = [];

        if ($filiereId) {
            $filiere = $this->filiereRepository->find($filiereId);
            if ($filiere) {
                $etudiants = $this->etudiantRepository->findBy(['filiere' => $filiere]);
            }
        }

        if ($request->isMethod('POST')) {
            $etudiantId = $request->request->get('etudiant');
            $periode = $request->request->get('periode');
            $anneeScolaire = $request->request->get('annee_scolaire');

            if ($etudiantId && $periode && $anneeScolaire) {
                $etudiant = $this->etudiantRepository->find($etudiantId);
                
                if ($etudiant) {
                    // Vérifier si un bulletin existe déjà pour cet étudiant, cette période et cette année
                    $existingBulletin = $this->bulletinRepository->findOneBy([
                        'etudiant' => $etudiant,
                        'periode' => $periode,
                        'anneeScolaire' => $anneeScolaire
                    ]);

                    if (!$existingBulletin) {
                        $bulletin = new Bulletin();
                        $bulletin->setEtudiant($etudiant);
                        $bulletin->setPeriode($periode);
                        $bulletin->setAnneeScolaire($anneeScolaire);
                        $bulletin->setDateCreation(new \DateTime());

                        $this->entityManager->persist($bulletin);
                        $this->entityManager->flush();

                        $this->addFlash('success', 'Bulletin créé avec succès !');
                        return $this->redirectToRoute('admin_bulletin_show', ['id' => $bulletin->getId()]);
                    } else {
                        $this->addFlash('error', 'Un bulletin existe déjà pour cet étudiant pour cette période et cette année.');
                    }
                }
            }
        }

        $filieres = $this->filiereRepository->findAll();

        return $this->render('admin/bulletin/new.html.twig', [
            'filieres' => $filieres,
            'selectedFiliere' => $filiere,
            'etudiants' => $etudiants,
        ]);
    }

    #[Route('/generer', name: 'admin_bulletin_generer', methods: ['GET', 'POST'])]
    public function generer(Request $request): Response
    {
        $periodeActive = $this->periodeService->getPeriodeActive();
        $classes = $this->classeRepository->findAll();
        $trimestres = $this->trimestreRepository->findAll();

        if ($request->isMethod('POST')) {
            $classeId = $request->request->get('classe');
            $trimestreId = $request->request->get('trimestre');
            $genererCommentaires = $request->request->get('generer_commentaires') === '1';

            $trimestre = $this->trimestreRepository->find($trimestreId);
            
            if (!$trimestre) {
                $this->addFlash('error', 'Trimestre invalide.');
                return $this->redirectToRoute('admin_bulletin_generer');
            }

            $etudiants = [];

            if ($classeId === 'all') {
                // Générer pour toutes les classes
                $etudiants = $this->etudiantRepository->findAll();
            } else {
                // Générer pour une classe spécifique
                $classe = $this->classeRepository->find($classeId);
                if ($classe) {
                    // Récupérer toutes les filières de cette classe
                    $filieres = $classe->getFilieres();
                    foreach ($filieres as $filiere) {
                        $etudiantsFiliere = $this->etudiantRepository->findBy(['filiere' => $filiere]);
                        $etudiants = array_merge($etudiants, $etudiantsFiliere);
                    }
                }
            }

            $bulletinsGeneres = 0;
            $bulletinsExistants = 0;

            foreach ($etudiants as $etudiant) {
                // Vérifier si un bulletin existe déjà
                $existingBulletin = $this->bulletinRepository->findOneBy([
                    'etudiant' => $etudiant,
                    'periode' => $trimestre->getNom(),
                    'anneeScolaire' => $trimestre->getAnneeScolaire()
                ]);

                if (!$existingBulletin) {
                    $bulletin = new Bulletin();
                    $bulletin->setEtudiant($etudiant);
                    $bulletin->setPeriode($trimestre->getNom());
                    $bulletin->setAnneeScolaire($trimestre->getAnneeScolaire());
                    $bulletin->setDateCreation(new \DateTime());

                    // Générer un commentaire automatique si demandé
                    if ($genererCommentaires) {
                        $moyenneGenerale = $this->calculerMoyenneGenerale($etudiant);
                        $commentaire = $this->genererCommentaireAutomatique($moyenneGenerale);
                        $bulletin->setCommentaire($commentaire);
                    }

                    $this->entityManager->persist($bulletin);
                    $bulletinsGeneres++;
                } else {
                    $bulletinsExistants++;
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                '%d bulletin(s) généré(s) avec succès pour le trimestre "%s" ! %s',
                $bulletinsGeneres,
                $trimestre->getNom(),
                $bulletinsExistants > 0 ? sprintf('(%d bulletin(s) existait(ent) déjà)', $bulletinsExistants) : ''
            ));

            return $this->redirectToRoute('admin_bulletins_index');
        }

        return $this->render('admin/bulletin/generer.html.twig', [
            'classes' => $classes,
            'trimestres' => $trimestres,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/api/etudiants/{filiereId}', name: 'admin_bulletin_api_etudiants', methods: ['GET'])]
    public function getEtudiantsByFiliere(int $filiereId): Response
    {
        $filiere = $this->filiereRepository->find($filiereId);
        
        if (!$filiere) {
            return $this->json(['error' => 'Filière non trouvée'], 404);
        }

        $etudiants = $this->etudiantRepository->findBy(['filiere' => $filiere]);
        
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

    #[Route('/{id}', name: 'admin_bulletin_show', methods: ['GET'])]
    public function show(Bulletin $bulletin): Response
    {
        $periodeActive = $this->periodeService->getPeriodeActive();
        $etudiant = $bulletin->getEtudiant();
        $filiere = $etudiant->getFiliere();
        
        // Récupérer toutes les matières de la filière
        $matieres = $this->entityManager->getRepository(Matiere::class)
            ->findBy(['filiere' => $filiere]);

        // Récupérer toutes les notes de l'étudiant pour la période active
        $notesCriteria = ['etudiant' => $etudiant];
        if ($periodeActive) {
            $notesCriteria['periodeAcademique'] = $periodeActive;
        }
        $notes = $this->entityManager->getRepository(Note::class)
            ->findBy($notesCriteria);

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

        return $this->render('admin/bulletin/show.html.twig', [
            'bulletin' => $bulletin,
            'etudiant' => $etudiant,
            'filiere' => $filiere,
            'notesParMatiere' => $notesParMatiere,
            'moyenneGenerale' => $moyenneGenerale,
            'totalCoefficients' => $totalCoefficients,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_bulletin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Bulletin $bulletin): Response
    {
        $periodeActive = $this->periodeService->getPeriodeActive();
        
        if ($request->isMethod('POST')) {
            $periode = $request->request->get('periode');
            $anneeScolaire = $request->request->get('annee_scolaire');
            $commentaire = $request->request->get('commentaire');

            $bulletin->setPeriode($periode);
            $bulletin->setAnneeScolaire($anneeScolaire);
            $bulletin->setCommentaire($commentaire);

            $this->entityManager->flush();

            $this->addFlash('success', 'Bulletin modifié avec succès !');
            return $this->redirectToRoute('admin_bulletin_show', ['id' => $bulletin->getId()]);
        }

        return $this->render('admin/bulletin/edit.html.twig', [
            'bulletin' => $bulletin,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_bulletin_delete', methods: ['POST'])]
    public function delete(Request $request, Bulletin $bulletin): Response
    {
        if ($this->isCsrfTokenValid('delete-bulletin', $request->request->get('_token'))) {
            $this->entityManager->remove($bulletin);
            $this->entityManager->flush();

            $this->addFlash('success', 'Bulletin supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_bulletins_index');
    }

    /**
     * Calculer la moyenne générale d'un étudiant pour la période active
     */
    private function calculerMoyenneGenerale(Etudiant $etudiant): float
    {
        $periodeActive = $this->periodeService->getPeriodeActive();
        $filiere = $etudiant->getFiliere();
        if (!$filiere) {
            return 0;
        }

        $matieres = $this->matiereRepository->findBy(['filiere' => $filiere]);
        
        // Filtrer par période active
        $notesCriteria = ['etudiant' => $etudiant];
        if ($periodeActive) {
            $notesCriteria['periodeAcademique'] = $periodeActive;
        }
        $notes = $this->noteRepository->findBy($notesCriteria);

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

    /**
     * Générer un commentaire automatique basé sur la moyenne
     */
    private function genererCommentaireAutomatique(float $moyenne): string
    {
        if ($moyenne >= 16) {
            return "Excellent travail ! L'étudiant démontre une maîtrise remarquable des matières. Continue comme ça !";
        } elseif ($moyenne >= 14) {
            return "Très bon niveau. L'étudiant montre de solides compétences. Quelques efforts supplémentaires permettraient d'atteindre l'excellence.";
        } elseif ($moyenne >= 12) {
            return "Bon niveau général. L'étudiant progresse bien. Il est encouragé à maintenir ses efforts pour améliorer ses résultats.";
        } elseif ($moyenne >= 10) {
            return "Niveau passable. L'étudiant doit fournir des efforts supplémentaires pour consolider ses acquis et améliorer ses performances.";
        } else {
            return "Résultats insuffisants. Des efforts importants et un travail régulier sont nécessaires pour progresser. Un soutien pédagogique est recommandé.";
        }
    }
}
