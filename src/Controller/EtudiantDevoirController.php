<?php

namespace App\Controller;

use App\Entity\Devoir;
use App\Entity\Soumission;
use App\Repository\DevoirRepository;
use App\Repository\SoumissionRepository;
use App\Service\FichierUploader;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/etudiant/devoirs')]
#[IsGranted('ROLE_ETUDIANT')]
class EtudiantDevoirController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DevoirRepository $devoirRepository,
        private SoumissionRepository $soumissionRepository,
        private PeriodeAcademiqueService $periodeService,
        private FichierUploader $uploader
    ) {}

    #[Route('/', name: 'etudiant_devoirs_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$etudiant) {
            $this->addFlash('error', 'Vous devez être un étudiant pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        $devoirs = $this->devoirRepository->findByEtudiant($etudiant, $periodeActive);

        // Enrichir avec les informations de soumission
        $devoirsAvecSoumission = [];
        foreach ($devoirs as $devoir) {
            $soumission = $this->soumissionRepository->findByEtudiantAndDevoir($etudiant, $devoir);
            $devoirsAvecSoumission[] = [
                'devoir' => $devoir,
                'soumission' => $soumission,
                'enRetard' => $devoir->isEnRetard() && !$soumission,
            ];
        }

        // Statistiques
        $stats = [
            'total' => count($devoirs),
            'a_faire' => 0,
            'soumis' => 0,
            'corriges' => 0,
            'en_retard' => 0,
        ];

        foreach ($devoirsAvecSoumission as $item) {
            if ($item['soumission']) {
                if ($item['soumission']->getStatut() === 'corrige') {
                    $stats['corriges']++;
                } else {
                    $stats['soumis']++;
                }
            } else {
                $stats['a_faire']++;
                if ($item['enRetard']) {
                    $stats['en_retard']++;
                }
            }
        }

        return $this->render('etudiant/devoir/index.html.twig', [
            'devoirsAvecSoumission' => $devoirsAvecSoumission,
            'stats' => $stats,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/{id}', name: 'etudiant_devoir_show', methods: ['GET'])]
    public function show(Devoir $devoir): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();

        if (!$etudiant) {
            throw $this->createAccessDeniedException();
        }

        if ($devoir->getMatiere()->getFiliere() !== $etudiant->getFiliere() || $devoir->getStatut() === 'brouillon') {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce devoir.');
        }

        $soumission = $this->soumissionRepository->findByEtudiantAndDevoir($etudiant, $devoir);

        return $this->render('etudiant/devoir/show.html.twig', [
            'devoir' => $devoir,
            'soumission' => $soumission,
        ]);
    }

    #[Route('/{id}/soumettre', name: 'etudiant_devoir_soumettre', methods: ['GET', 'POST'])]
    public function soumettre(Request $request, Devoir $devoir): Response
    {
        $user = $this->getUser();
        $etudiant = $user->getEtudiant();

        if (!$etudiant) {
            throw $this->createAccessDeniedException();
        }

        if ($devoir->getMatiere()->getFiliere() !== $etudiant->getFiliere() || $devoir->getStatut() === 'brouillon') {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce devoir.');
        }

        if ($devoir->getStatut() === 'cloture') {
            $this->addFlash('error', 'Ce devoir est clôturé, il n\'accepte plus de rendu.');
            return $this->redirectToRoute('etudiant_devoir_show', ['id' => $devoir->getId()]);
        }

        // Vérifier si une soumission existe déjà
        $soumission = $this->soumissionRepository->findByEtudiantAndDevoir($etudiant, $devoir);

        if ($soumission && $soumission->getStatut() !== 'brouillon') {
            $this->addFlash('error', 'Vous avez déjà soumis ce devoir.');
            return $this->redirectToRoute('etudiant_devoir_show', ['id' => $devoir->getId()]);
        }

        // Vérifier l'échéance
        if ($devoir->isEnRetard() && !$devoir->isAccepteSoumissionsTardives()) {
            $this->addFlash('error', 'La date limite de soumission est dépassée.');
            return $this->redirectToRoute('etudiant_devoir_show', ['id' => $devoir->getId()]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('soumettre'.$devoir->getId(), $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Token de sécurité invalide.');
            }

            $commentaire = $request->request->get('commentaire');
            $action = $request->request->get('action'); // 'brouillon' ou 'soumettre'

            if (!$soumission) {
                $soumission = new Soumission();
                $soumission->setDevoir($devoir);
                $soumission->setEtudiant($etudiant);
                $this->entityManager->persist($soumission);
            }

            foreach ($request->request->all('supprimer_fichiers') as $nomStockage) {
                if ($soumission->getFichier($nomStockage)) {
                    $this->uploader->supprimer(FichierController::DOSSIER_SOUMISSIONS, $nomStockage);
                    $soumission->retirerFichier($nomStockage);
                }
            }

            foreach (array_filter($request->files->all('fichiers')) as $fichier) {
                try {
                    $soumission->ajouterFichier($this->uploader->upload($fichier, FichierController::DOSSIER_SOUMISSIONS));
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('error', $e->getMessage());
                    $action = 'brouillon';
                }
            }

            if ($action === 'soumettre' && !trim((string) $commentaire) && !$soumission->getListeFichiers()) {
                $this->addFlash('error', 'Votre rendu est vide : rédigez une réponse ou joignez au moins un fichier.');
                $action = 'brouillon';
            }

            $soumission->setCommentaire($commentaire);
            $soumission->setDateSoumission(new \DateTime());
            
            if ($action === 'soumettre') {
                $soumission->setStatut('soumis');
                $this->addFlash('success', 'Devoir soumis avec succès !');
            } else {
                $soumission->setStatut('brouillon');
                $this->addFlash('success', 'Brouillon enregistré.');
            }

            $soumission->setDateModification(new \DateTime());

            $this->entityManager->flush();

            return $this->redirectToRoute(
                $action === 'soumettre' ? 'etudiant_devoir_show' : 'etudiant_devoir_soumettre',
                ['id' => $devoir->getId()]
            );
        }

        return $this->render('etudiant/devoir/soumettre.html.twig', [
            'devoir' => $devoir,
            'soumission' => $soumission,
        ]);
    }
}

