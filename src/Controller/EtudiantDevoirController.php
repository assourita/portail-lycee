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

#[Route('/etudiant/devoirs')]
#[IsGranted('ROLE_ETUDIANT')]
class EtudiantDevoirController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DevoirRepository $devoirRepository,
        private SoumissionRepository $soumissionRepository,
        private PeriodeAcademiqueService $periodeService
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

        // Vérifier que l'étudiant a accès à ce devoir (via sa filière)
        if ($devoir->getMatiere()->getFiliere() !== $etudiant->getFiliere()) {
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

        // Vérifier que l'étudiant a accès à ce devoir
        if ($devoir->getMatiere()->getFiliere() !== $etudiant->getFiliere()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce devoir.');
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
            $commentaire = $request->request->get('commentaire');
            $action = $request->request->get('action'); // 'brouillon' ou 'soumettre'

            if (!$soumission) {
                $soumission = new Soumission();
                $soumission->setDevoir($devoir);
                $soumission->setEtudiant($etudiant);
                $this->entityManager->persist($soumission);
            }

            $soumission->setCommentaire($commentaire);
            
            if ($action === 'soumettre') {
                $soumission->setStatut('soumis');
                $soumission->setDateSoumission(new \DateTime());
                $this->addFlash('success', 'Devoir soumis avec succès !');
            } else {
                $soumission->setStatut('brouillon');
                $this->addFlash('success', 'Brouillon enregistré avec succès !');
            }

            $soumission->setDateModification(new \DateTime());

            // TODO: Gérer l'upload des fichiers

            $this->entityManager->flush();

            return $this->redirectToRoute('etudiant_devoir_show', ['id' => $devoir->getId()]);
        }

        return $this->render('etudiant/devoir/soumettre.html.twig', [
            'devoir' => $devoir,
            'soumission' => $soumission,
        ]);
    }
}

