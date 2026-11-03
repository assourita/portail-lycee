<?php

namespace App\Controller;

use App\Entity\Devoir;
use App\Entity\Soumission;
use App\Repository\DevoirRepository;
use App\Repository\SoumissionRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/enseignant/devoirs')]
#[IsGranted('ROLE_ENSEIGNANT')]
class EnseignantDevoirController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DevoirRepository $devoirRepository,
        private SoumissionRepository $soumissionRepository,
        private PeriodeAcademiqueService $periodeService
    ) {}

    #[Route('/', name: 'enseignant_devoirs_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }

        $devoirs = $this->devoirRepository->findByEnseignant($enseignant, $periodeActive);

        // Statistiques
        $stats = [
            'total' => count($devoirs),
            'publies' => 0,
            'brouillons' => 0,
            'a_corriger' => 0,
        ];

        foreach ($devoirs as $devoir) {
            if ($devoir->getStatut() === 'publie') {
                $stats['publies']++;
                $stats['a_corriger'] += $this->soumissionRepository->countByStatut($devoir, 'soumis');
            } elseif ($devoir->getStatut() === 'brouillon') {
                $stats['brouillons']++;
            }
        }

        return $this->render('enseignant/devoir/index.html.twig', [
            'devoirs' => $devoirs,
            'stats' => $stats,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/new', name: 'enseignant_devoir_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }

        if (!$periodeActive) {
            $this->addFlash('error', 'Aucune période académique active.');
            return $this->redirectToRoute('enseignant_devoirs_index');
        }

        if ($request->isMethod('POST')) {
            $titre = $request->request->get('titre');
            $description = $request->request->get('description');
            $consignes = $request->request->get('consignes');
            $dateEcheance = $request->request->get('date_echeance');
            $noteMax = $request->request->get('note_max', 20);
            $matiereId = $request->request->get('matiere');
            $accepteTardives = $request->request->get('accepte_tardives') === '1';
            $statut = $request->request->get('statut', 'brouillon');

            if ($titre && $description && $dateEcheance && $matiereId) {
                $matiere = $this->entityManager->getRepository(\App\Entity\Matiere::class)->find($matiereId);

                if ($matiere && $matiere->getEnseignant() === $enseignant) {
                    $devoir = new Devoir();
                    $devoir->setTitre($titre);
                    $devoir->setDescription($description);
                    $devoir->setConsignes($consignes);
                    $devoir->setDateEcheance(new \DateTime($dateEcheance));
                    $devoir->setNoteMax((int)$noteMax);
                    $devoir->setMatiere($matiere);
                    $devoir->setEnseignant($enseignant);
                    $devoir->setPeriodeAcademique($periodeActive);
                    $devoir->setAccepteSoumissionsTardives($accepteTardives);
                    $devoir->setStatut($statut);

                    $this->entityManager->persist($devoir);
                    $this->entityManager->flush();

                    $this->addFlash('success', 'Devoir créé avec succès !');
                    return $this->redirectToRoute('enseignant_devoirs_index');
                }
            }

            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
        }

        $matieres = $enseignant->getMatieres();

        return $this->render('enseignant/devoir/new.html.twig', [
            'matieres' => $matieres,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/{id}', name: 'enseignant_devoir_show', methods: ['GET'])]
    public function show(Devoir $devoir): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();

        if ($devoir->getEnseignant() !== $enseignant) {
            throw $this->createAccessDeniedException();
        }

        $soumissions = $this->soumissionRepository->findByDevoir($devoir);
        
        // Statistiques
        $stats = [
            'total' => count($soumissions),
            'soumis' => $this->soumissionRepository->countByStatut($devoir, 'soumis'),
            'corrige' => $this->soumissionRepository->countByStatut($devoir, 'corrige'),
            'en_retard' => count($this->soumissionRepository->findEnRetard($devoir)),
        ];

        return $this->render('enseignant/devoir/show.html.twig', [
            'devoir' => $devoir,
            'soumissions' => $soumissions,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}/edit', name: 'enseignant_devoir_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Devoir $devoir): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();

        if ($devoir->getEnseignant() !== $enseignant) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $titre = $request->request->get('titre');
            $description = $request->request->get('description');
            $consignes = $request->request->get('consignes');
            $dateEcheance = $request->request->get('date_echeance');
            $noteMax = $request->request->get('note_max', 20);
            $accepteTardives = $request->request->get('accepte_tardives') === '1';
            $statut = $request->request->get('statut');

            if ($titre && $description && $dateEcheance && $statut) {
                $devoir->setTitre($titre);
                $devoir->setDescription($description);
                $devoir->setConsignes($consignes);
                $devoir->setDateEcheance(new \DateTime($dateEcheance));
                $devoir->setNoteMax((int)$noteMax);
                $devoir->setAccepteSoumissionsTardives($accepteTardives);
                $devoir->setStatut($statut);
                $devoir->setDateModification(new \DateTime());

                $this->entityManager->flush();

                $this->addFlash('success', 'Devoir modifié avec succès !');
                return $this->redirectToRoute('enseignant_devoir_show', ['id' => $devoir->getId()]);
            }

            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
        }

        return $this->render('enseignant/devoir/edit.html.twig', [
            'devoir' => $devoir,
        ]);
    }

    #[Route('/{id}/delete', name: 'enseignant_devoir_delete', methods: ['POST'])]
    public function delete(Request $request, Devoir $devoir): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();

        if ($devoir->getEnseignant() !== $enseignant) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$devoir->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($devoir);
            $this->entityManager->flush();

            $this->addFlash('success', 'Devoir supprimé avec succès !');
        }

        return $this->redirectToRoute('enseignant_devoirs_index');
    }

    #[Route('/{id}/corriger/{soumissionId}', name: 'enseignant_devoir_corriger', methods: ['GET', 'POST'])]
    public function corriger(Request $request, int $id, int $soumissionId): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        
        $devoir = $this->devoirRepository->find($id);
        $soumission = $this->soumissionRepository->find($soumissionId);

        if (!$devoir || !$soumission || $devoir->getEnseignant() !== $enseignant) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $note = $request->request->get('note');
            $commentaire = $request->request->get('commentaire');

            if ($note !== null) {
                $soumission->setNote($note);
                $soumission->setCommentaireEnseignant($commentaire);
                $soumission->setStatut('corrige');
                $soumission->setDateCorrection(new \DateTime());

                $this->entityManager->flush();

                $this->addFlash('success', 'Soumission corrigée avec succès !');
                return $this->redirectToRoute('enseignant_devoir_show', ['id' => $devoir->getId()]);
            }

            $this->addFlash('error', 'Veuillez saisir une note.');
        }

        return $this->render('enseignant/devoir/corriger.html.twig', [
            'devoir' => $devoir,
            'soumission' => $soumission,
        ]);
    }
}

