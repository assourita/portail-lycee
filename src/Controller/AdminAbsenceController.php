<?php

namespace App\Controller;

use App\Entity\Absence;
use App\Repository\AbsenceRepository;
use App\Repository\EtudiantRepository;
use App\Repository\FiliereRepository;
use App\Repository\MatiereRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/absences')]
#[IsGranted('ROLE_ADMIN')]
class AdminAbsenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AbsenceRepository $absenceRepository,
        private EtudiantRepository $etudiantRepository,
        private FiliereRepository $filiereRepository,
        private MatiereRepository $matiereRepository,
        private PeriodeAcademiqueService $periodeService
    ) {}

    #[Route('/', name: 'admin_absences_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $periodeActive = $this->periodeService->getPeriodeActive();
        
        // Filtres
        $filiereId = $request->query->get('filiere');
        $statut = $request->query->get('statut'); // 'justifiee', 'non_justifiee', 'en_attente'
        
        $qb = $this->absenceRepository->createQueryBuilder('a')
            ->orderBy('a.date', 'DESC');

        if ($periodeActive) {
            $qb->andWhere('a.periodeAcademique = :periode')
               ->setParameter('periode', $periodeActive);
        }

        if ($filiereId) {
            $qb->join('a.etudiant', 'e')
               ->andWhere('e.filiere = :filiere')
               ->setParameter('filiere', $filiereId);
        }

        if ($statut === 'justifiee') {
            $qb->andWhere('a.justifiee = true');
        } elseif ($statut === 'non_justifiee') {
            $qb->andWhere('a.justifiee = false')
               ->andWhere('a.justification IS NULL');
        } elseif ($statut === 'en_attente') {
            $qb->andWhere('a.justifiee = false')
               ->andWhere('a.justification IS NOT NULL');
        }

        $absences = $qb->getQuery()->getResult();
        
        // Statistiques
        $stats = [
            'total' => count($absences),
            'justifiees' => $this->absenceRepository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->andWhere('a.justifiee = true')
                ->andWhere('a.periodeAcademique = :periode')
                ->setParameter('periode', $periodeActive)
                ->getQuery()
                ->getSingleScalarResult(),
            'non_justifiees' => count($this->absenceRepository->findNonJustifiees($periodeActive)),
            'en_attente' => count($this->absenceRepository->findEnAttenteValidation($periodeActive)),
        ];

        $filieres = $this->filiereRepository->findAll();

        return $this->render('admin/absence/index.html.twig', [
            'absences' => $absences,
            'stats' => $stats,
            'filieres' => $filieres,
            'periodeActive' => $periodeActive,
            'filtreFiliere' => $filiereId,
            'filtreStatut' => $statut,
        ]);
    }

    #[Route('/{id}', name: 'admin_absence_show', methods: ['GET'])]
    public function show(Absence $absence): Response
    {
        $periodeActive = $this->periodeService->getPeriodeActive();

        return $this->render('admin/absence/show.html.twig', [
            'absence' => $absence,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/{id}/valider', name: 'admin_absence_valider', methods: ['POST'])]
    public function valider(Request $request, Absence $absence): Response
    {
        if ($this->isCsrfTokenValid('valider'.$absence->getId(), $request->request->get('_token'))) {
            $absence->setJustifiee(true);
            $absence->setValidePar($this->getUser());
            $absence->setDateValidation(new \DateTime());
            $absence->setDateModification(new \DateTime());

            $this->entityManager->flush();

            $this->addFlash('success', 'Absence validée avec succès !');
        }

        return $this->redirectToRoute('admin_absences_index');
    }

    #[Route('/{id}/rejeter', name: 'admin_absence_rejeter', methods: ['POST'])]
    public function rejeter(Request $request, Absence $absence): Response
    {
        if ($this->isCsrfTokenValid('rejeter'.$absence->getId(), $request->request->get('_token'))) {
            $absence->setJustification(null);
            $absence->setDocumentJustificatif(null);
            $absence->setJustifiee(false);
            $absence->setDateModification(new \DateTime());

            $this->entityManager->flush();

            $this->addFlash('success', 'Justification rejetée.');
        }

        return $this->redirectToRoute('admin_absences_index');
    }

    #[Route('/{id}/delete', name: 'admin_absence_delete', methods: ['POST'])]
    public function delete(Request $request, Absence $absence): Response
    {
        if ($this->isCsrfTokenValid('delete-absence', $request->request->get('_token'))) {
            $this->entityManager->remove($absence);
            $this->entityManager->flush();

            $this->addFlash('success', 'Absence supprimée avec succès !');
        }

        return $this->redirectToRoute('admin_absences_index');
    }

    #[Route('/etudiant/{id}/statistiques', name: 'admin_absence_etudiant_stats', methods: ['GET'])]
    public function statistiquesEtudiant(int $id): Response
    {
        $periodeActive = $this->periodeService->getPeriodeActive();
        $etudiant = $this->etudiantRepository->find($id);

        if (!$etudiant) {
            throw $this->createNotFoundException('Étudiant non trouvé');
        }

        $absences = $this->absenceRepository->findByEtudiant($etudiant, $periodeActive);
        
        $stats = [
            'total' => count($absences),
            'justifiees' => $this->absenceRepository->countByEtudiant($etudiant, $periodeActive, true),
            'non_justifiees' => $this->absenceRepository->countByEtudiant($etudiant, $periodeActive, false),
            'taux_assiduite' => $this->absenceRepository->calculateTauxAssiduite($etudiant, $periodeActive),
        ];

        $parMois = [];
        foreach ($absences as $absence) {
            if (!$absence->getDate()) {
                continue;
            }
            $mois = $absence->getDate()->format('Y-m');
            $parMois[$mois] ??= ['justifiees' => 0, 'non_justifiees' => 0];
            $parMois[$mois][$absence->isJustifiee() ? 'justifiees' : 'non_justifiees']++;
        }
        ksort($parMois);

        return $this->render('admin/absence/statistiques_etudiant.html.twig', [
            'etudiant' => $etudiant,
            'absences' => $absences,
            'stats' => $stats,
            'periodeActive' => $periodeActive,
            'graphique' => [
                'mois' => array_keys($parMois),
                'justifiees' => array_column($parMois, 'justifiees'),
                'non_justifiees' => array_column($parMois, 'non_justifiees'),
            ],
        ]);
    }
}

