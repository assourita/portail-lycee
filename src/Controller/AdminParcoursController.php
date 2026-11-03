<?php

namespace App\Controller;

use App\Entity\ParcoursScolaire;
use App\Repository\ParcoursRepository;
use App\Repository\EtudiantRepository;
use App\Repository\NoteRepository;
use App\Repository\AbsenceRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/parcours')]
#[IsGranted('ROLE_ADMIN')]
class AdminParcoursController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParcoursRepository $parcoursRepository,
        private EtudiantRepository $etudiantRepository,
        private NoteRepository $noteRepository,
        private AbsenceRepository $absenceRepository,
        private PeriodeAcademiqueService $periodeService
    ) {}

    #[Route('/etudiant/{id}', name: 'admin_parcours_etudiant', methods: ['GET'])]
    public function parcoursEtudiant(int $id): Response
    {
        $etudiant = $this->etudiantRepository->find($id);

        if (!$etudiant) {
            throw $this->createNotFoundException('Étudiant non trouvé');
        }

        $parcours = $this->parcoursRepository->findByEtudiant($etudiant);
        $periodeActive = $this->periodeService->getPeriodeActive();

        // Calculer les statistiques de l'année en cours
        $statsAnneeEnCours = $this->calculerStatistiquesAnnee($etudiant, $periodeActive);

        return $this->render('admin/parcours/etudiant.html.twig', [
            'etudiant' => $etudiant,
            'parcours' => $parcours,
            'statsAnneeEnCours' => $statsAnneeEnCours,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/generer/{etudiantId}', name: 'admin_parcours_generer', methods: ['POST'])]
    public function generer(Request $request, int $etudiantId): Response
    {
        $etudiant = $this->etudiantRepository->find($etudiantId);

        if (!$etudiant) {
            throw $this->createNotFoundException('Étudiant non trouvé');
        }

        $anneeScolaire = $request->request->get('annee_scolaire');
        $periodeActive = $this->periodeService->getPeriodeActive();

        if (!$anneeScolaire) {
            $anneeScolaire = $periodeActive ? $periodeActive->getAnneeScolaire() : date('Y').'-'.(date('Y')+1);
        }

        // Vérifier si un parcours existe déjà pour cette année
        $parcoursExistant = $this->parcoursRepository->findByEtudiantAndAnnee($etudiant, $anneeScolaire);

        if ($parcoursExistant) {
            $this->addFlash('error', 'Un parcours existe déjà pour cette année scolaire.');
            return $this->redirectToRoute('admin_parcours_etudiant', ['id' => $etudiantId]);
        }

        // Calculer les statistiques
        $stats = $this->calculerStatistiquesAnnee($etudiant, $periodeActive);

        // Créer le parcours
        $parcours = new ParcoursScolaire();
        $parcours->setEtudiant($etudiant);
        $parcours->setAnneeScolaire($anneeScolaire);
        $parcours->setNiveau($etudiant->getFiliere()->getClasse()->getNom() ?? 'Non défini');
        $parcours->setFiliere($etudiant->getFiliere());
        $parcours->setClasse($etudiant->getFiliere()->getClasse());
        $parcours->setMoyenneAnnuelle($stats['moyenne_generale']);
        $parcours->setMoyenneTrimestre1($stats['moyenne_t1']);
        $parcours->setMoyenneTrimestre2($stats['moyenne_t2']);
        $parcours->setMoyenneTrimestre3($stats['moyenne_t3']);
        $parcours->setRangClasse($stats['rang']);
        $parcours->setTotalEtudiants($stats['total_etudiants']);
        $parcours->setTotalAbsences($stats['total_absences']);
        $parcours->setAbsencesJustifiees($stats['absences_justifiees']);
        $parcours->setTauxAssiduite($stats['taux_assiduite']);

        $this->entityManager->persist($parcours);
        $this->entityManager->flush();

        $this->addFlash('success', 'Parcours scolaire généré avec succès !');
        return $this->redirectToRoute('admin_parcours_etudiant', ['id' => $etudiantId]);
    }

    private function calculerStatistiquesAnnee($etudiant, $periodeActive): array
    {
        // Récupérer toutes les notes de l'étudiant
        $notes = $this->noteRepository->findBy([
            'etudiant' => $etudiant,
            'periodeAcademique' => $periodeActive
        ]);

        $moyenneGenerale = 0;
        if (count($notes) > 0) {
            $somme = array_sum(array_map(fn($note) => $note->getValeur(), $notes));
            $moyenneGenerale = round($somme / count($notes), 2);
        }

        // Calculer les absences
        $totalAbsences = $this->absenceRepository->countByEtudiant($etudiant, $periodeActive);
        $absencesJustifiees = $this->absenceRepository->countByEtudiant($etudiant, $periodeActive, true);
        $tauxAssiduite = $this->absenceRepository->calculateTauxAssiduite($etudiant, $periodeActive);

        // Calculer le rang (simplifié)
        $filiere = $etudiant->getFiliere();
        $totalEtudiants = $filiere ? count($this->etudiantRepository->findBy(['filiere' => $filiere])) : 0;

        return [
            'moyenne_generale' => $moyenneGenerale,
            'moyenne_t1' => null, // À calculer par trimestre
            'moyenne_t2' => null,
            'moyenne_t3' => null,
            'rang' => 1, // À calculer
            'total_etudiants' => $totalEtudiants,
            'total_absences' => $totalAbsences,
            'absences_justifiees' => $absencesJustifiees,
            'taux_assiduite' => $tauxAssiduite,
        ];
    }
}

