<?php

namespace App\Controller;

use App\Entity\Devoir;
use App\Entity\Enseignant;
use App\Entity\Matiere;
use App\Repository\DevoirRepository;
use App\Repository\SoumissionRepository;
use App\Service\FichierUploader;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/enseignant/devoirs')]
#[IsGranted('ROLE_ENSEIGNANT')]
class EnseignantDevoirController extends AbstractController
{
    private const STATUTS = ['brouillon', 'publie', 'cloture'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DevoirRepository $devoirRepository,
        private SoumissionRepository $soumissionRepository,
        private PeriodeAcademiqueService $periodeService,
        private FichierUploader $uploader
    ) {}

    #[Route('/', name: 'enseignant_devoirs_index', methods: ['GET'])]
    public function index(): Response
    {
        $enseignant = $this->getEnseignant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        $devoirs = $this->devoirRepository->findByEnseignant($enseignant, $periodeActive);

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
        $enseignant = $this->getEnseignant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$periodeActive) {
            $this->addFlash('error', 'Aucune période académique active.');
            return $this->redirectToRoute('enseignant_devoirs_index');
        }

        if ($request->isMethod('POST')) {
            $this->verifierCsrf($request);

            $titre = $request->request->get('titre');
            $description = $request->request->get('description');
            $dateEcheance = $request->request->get('date_echeance');
            $matiere = $this->entityManager->getRepository(Matiere::class)->find((int) $request->request->get('matiere'));

            if ($titre && $description && $dateEcheance && $matiere && $matiere->getEnseignant() === $enseignant) {
                $devoir = new Devoir();
                $devoir->setMatiere($matiere);
                $devoir->setEnseignant($enseignant);
                $devoir->setPeriodeAcademique($periodeActive);
                $this->hydrater($devoir, $request);
                $this->gererDocuments($devoir, $request);

                $this->entityManager->persist($devoir);
                $this->entityManager->flush();

                $this->addFlash('success', 'Devoir créé avec succès !');
                return $this->redirectToRoute('enseignant_devoir_show', ['id' => $devoir->getId()]);
            }

            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
        }

        return $this->render('enseignant/devoir/new.html.twig', [
            'matieres' => $enseignant->getMatieres(),
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/{id}', name: 'enseignant_devoir_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Devoir $devoir): Response
    {
        $this->verifierProprietaire($devoir);

        $soumissions = $this->soumissionRepository->findByDevoir($devoir);

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

    #[Route('/{id}/edit', name: 'enseignant_devoir_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Devoir $devoir): Response
    {
        $this->verifierProprietaire($devoir);

        if ($request->isMethod('POST')) {
            $this->verifierCsrf($request);

            if ($request->request->get('titre') && $request->request->get('description') && $request->request->get('date_echeance')) {
                $this->hydrater($devoir, $request);
                $devoir->setDateModification(new \DateTime());

                foreach ($request->request->all('supprimer_documents') as $nomStockage) {
                    if ($devoir->getDocument($nomStockage)) {
                        $this->uploader->supprimer(FichierController::DOSSIER_DEVOIRS, $nomStockage);
                        $devoir->retirerDocument($nomStockage);
                    }
                }
                $this->gererDocuments($devoir, $request);

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
        $this->verifierProprietaire($devoir);

        if ($this->isCsrfTokenValid('delete-devoir', $request->request->get('_token'))) {
            foreach ($devoir->getListeDocuments() as $document) {
                $this->uploader->supprimer(FichierController::DOSSIER_DEVOIRS, $document['fichier']);
            }
            foreach ($devoir->getSoumissions() as $soumission) {
                foreach ($soumission->getListeFichiers() as $fichier) {
                    $this->uploader->supprimer(FichierController::DOSSIER_SOUMISSIONS, $fichier['fichier']);
                }
            }

            $this->entityManager->remove($devoir);
            $this->entityManager->flush();

            $this->addFlash('success', 'Devoir supprimé avec succès !');
        }

        return $this->redirectToRoute('enseignant_devoirs_index');
    }

    #[Route('/{id}/publier', name: 'enseignant_devoir_publier', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function publier(Request $request, Devoir $devoir): Response
    {
        $this->verifierProprietaire($devoir);

        if ($this->isCsrfTokenValid('publier-devoir'.$devoir->getId(), $request->request->get('_token'))) {
            $devoir->setStatut('publie');
            $devoir->setDateModification(new \DateTime());
            $this->entityManager->flush();
            $this->addFlash('success', 'Le devoir est maintenant visible par les étudiants.');
        }

        return $this->redirectToRoute('enseignant_devoir_show', ['id' => $devoir->getId()]);
    }

    #[Route('/{id}/corriger/{soumissionId}', name: 'enseignant_devoir_corriger', methods: ['GET', 'POST'], requirements: ['id' => '\d+', 'soumissionId' => '\d+'])]
    public function corriger(Request $request, Devoir $devoir, int $soumissionId): Response
    {
        $this->verifierProprietaire($devoir);

        $soumission = $this->soumissionRepository->find($soumissionId);
        if (!$soumission || $soumission->getDevoir() !== $devoir) {
            throw $this->createNotFoundException('Soumission introuvable pour ce devoir.');
        }

        if ($request->isMethod('POST')) {
            $note = str_replace(',', '.', (string) $request->request->get('note'));

            if (is_numeric($note) && $note >= 0 && $note <= $devoir->getNoteMax()) {
                $soumission->setNote($note);
                $soumission->setCommentaireEnseignant($request->request->get('commentaire'));
                $soumission->setStatut('corrige');
                $soumission->setDateCorrection(new \DateTime());

                $this->entityManager->flush();

                $this->addFlash('success', 'Soumission corrigée avec succès !');
                return $this->redirectToRoute('enseignant_devoir_show', ['id' => $devoir->getId()]);
            }

            $this->addFlash('error', sprintf('Veuillez saisir une note comprise entre 0 et %d.', $devoir->getNoteMax()));
        }

        return $this->render('enseignant/devoir/corriger.html.twig', [
            'devoir' => $devoir,
            'soumission' => $soumission,
        ]);
    }

    private function hydrater(Devoir $devoir, Request $request): void
    {
        $statut = $request->request->get('statut', 'brouillon');

        $devoir->setTitre($request->request->get('titre'));
        $devoir->setDescription($request->request->get('description'));
        $devoir->setConsignes($request->request->get('consignes'));
        $devoir->setDateEcheance(new \DateTime($request->request->get('date_echeance')));
        $devoir->setNoteMax(max(1, (int) $request->request->get('note_max', 20)));
        $devoir->setAccepteSoumissionsTardives($request->request->get('accepte_tardives') === '1');
        $devoir->setStatut(in_array($statut, self::STATUTS, true) ? $statut : 'brouillon');
    }

    private function gererDocuments(Devoir $devoir, Request $request): void
    {
        foreach (array_filter($request->files->all('documents')) as $fichier) {
            try {
                $devoir->ajouterDocument($this->uploader->upload($fichier, FichierController::DOSSIER_DEVOIRS));
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }
    }

    private function verifierCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('devoir', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token de sécurité invalide.');
        }
    }

    private function verifierProprietaire(Devoir $devoir): void
    {
        if ($devoir->getEnseignant() !== $this->getEnseignant()) {
            throw $this->createAccessDeniedException();
        }
    }

    private function getEnseignant(): Enseignant
    {
        $enseignant = $this->getUser()?->getEnseignant();
        if (!$enseignant) {
            throw $this->createAccessDeniedException('Aucun profil enseignant n\'est associé à ce compte.');
        }

        return $enseignant;
    }
}
