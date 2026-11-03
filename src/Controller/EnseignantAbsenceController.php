<?php

namespace App\Controller;

use App\Entity\Absence;
use App\Repository\AbsenceRepository;
use App\Repository\EtudiantRepository;
use App\Repository\MatiereRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/enseignant/absences')]
#[IsGranted('ROLE_ENSEIGNANT')]
class EnseignantAbsenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AbsenceRepository $absenceRepository,
        private EtudiantRepository $etudiantRepository,
        private MatiereRepository $matiereRepository,
        private PeriodeAcademiqueService $periodeService
    ) {}

    #[Route('/', name: 'enseignant_absences_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$enseignant) {
            throw $this->createNotFoundException('Enseignant non trouvé');
        }

        // Récupérer les absences enregistrées par cet enseignant
        $absences = $this->absenceRepository->createQueryBuilder('a')
            ->andWhere('a.enseignant = :enseignant')
            ->andWhere('a.periodeAcademique = :periode')
            ->setParameter('enseignant', $enseignant)
            ->setParameter('periode', $periodeActive)
            ->orderBy('a.date', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('enseignant/absence/index.html.twig', [
            'absences' => $absences,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/new', name: 'enseignant_absence_new', methods: ['GET', 'POST'])]
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
            return $this->redirectToRoute('enseignant_absences_index');
        }

        if ($request->isMethod('POST')) {
            $etudiantId = $request->request->get('etudiant');
            $date = $request->request->get('date');
            $type = $request->request->get('type');
            $periode = $request->request->get('periode');
            $matiereId = $request->request->get('matiere');
            $motif = $request->request->get('motif');
            $heureDebut = $request->request->get('heure_debut');
            $heureFin = $request->request->get('heure_fin');

            if ($etudiantId && $date && $type && $periode) {
                $etudiant = $this->etudiantRepository->find($etudiantId);
                $matiere = $matiereId ? $this->matiereRepository->find($matiereId) : null;

                if ($etudiant) {
                    $absence = new Absence();
                    $absence->setEtudiant($etudiant);
                    $absence->setDate(new \DateTime($date));
                    $absence->setType($type);
                    $absence->setPeriode($periode);
                    $absence->setMotif($motif);
                    $absence->setEnseignant($enseignant);
                    $absence->setMatiere($matiere);
                    $absence->setPeriodeAcademique($periodeActive);

                    if ($heureDebut) {
                        $absence->setHeureDebut(new \DateTime($heureDebut));
                    }
                    if ($heureFin) {
                        $absence->setHeureFin(new \DateTime($heureFin));
                    }

                    $this->entityManager->persist($absence);
                    $this->entityManager->flush();

                    $this->addFlash('success', 'Absence enregistrée avec succès !');
                    return $this->redirectToRoute('enseignant_absences_index');
                }
            }

            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
        }

        // Récupérer les étudiants des matières de l'enseignant
        $matieres = $enseignant->getMatieres();
        $etudiants = [];
        foreach ($matieres as $matiere) {
            if ($matiere->getFiliere()) {
                $etudiantsMatiere = $this->etudiantRepository->findBy(['filiere' => $matiere->getFiliere()]);
                foreach ($etudiantsMatiere as $etudiant) {
                    $etudiants[$etudiant->getId()] = $etudiant;
                }
            }
        }

        return $this->render('enseignant/absence/new.html.twig', [
            'etudiants' => array_values($etudiants),
            'matieres' => $matieres,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/{id}', name: 'enseignant_absence_show', methods: ['GET'])]
    public function show(Absence $absence): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();

        // Vérifier que l'absence a été créée par cet enseignant
        if ($absence->getEnseignant() !== $enseignant) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('enseignant/absence/show.html.twig', [
            'absence' => $absence,
        ]);
    }

    #[Route('/{id}/delete', name: 'enseignant_absence_delete', methods: ['POST'])]
    public function delete(Request $request, Absence $absence): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();

        // Vérifier que l'absence a été créée par cet enseignant
        if ($absence->getEnseignant() !== $enseignant) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$absence->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($absence);
            $this->entityManager->flush();

            $this->addFlash('success', 'Absence supprimée avec succès !');
        }

        return $this->redirectToRoute('enseignant_absences_index');
    }
}

