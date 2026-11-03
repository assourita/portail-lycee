<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\Note;
use App\Entity\ParcoursScolaire;
use App\Entity\PeriodeAcademique;
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
        $classe = $etudiant->getFiliere()?->getClasse();
        $parcours->setNiveau($classe?->getNom() ?? $etudiant->getFiliere()?->getNom() ?? 'Non défini');
        $parcours->setFiliere($etudiant->getFiliere());
        $parcours->setClasse($classe);
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

    private function calculerStatistiquesAnnee(Etudiant $etudiant, ?PeriodeAcademique $periodeActive): array
    {
        $notes = $this->findNotes($etudiant, $periodeActive);

        $parTrimestre = [1 => [], 2 => [], 3 => []];
        foreach ($notes as $note) {
            $numero = $this->numeroTrimestre($note->getEvaluation()?->getTrimestre()?->getNom());
            if ($numero) {
                $parTrimestre[$numero][] = (float) $note->getValeur();
            }
        }
        $moyenne = fn (array $valeurs) => $valeurs ? round(array_sum($valeurs) / count($valeurs), 2) : null;

        $moyenneGenerale = $moyenne(array_map(fn (Note $n) => (float) $n->getValeur(), $notes)) ?? 0;

        $filiere = $etudiant->getFiliere();
        $camarades = $filiere ? $this->etudiantRepository->findBy(['filiere' => $filiere]) : [$etudiant];
        $meilleures = 0;
        foreach ($camarades as $camarade) {
            if ($camarade !== $etudiant) {
                $moyenneCamarade = $moyenne(array_map(fn (Note $n) => (float) $n->getValeur(), $this->findNotes($camarade, $periodeActive))) ?? 0;
                if ($moyenneCamarade > $moyenneGenerale) {
                    $meilleures++;
                }
            }
        }

        return [
            'moyenne_generale' => $moyenneGenerale,
            'total_notes' => count($notes),
            'moyenne_t1' => $moyenne($parTrimestre[1]),
            'moyenne_t2' => $moyenne($parTrimestre[2]),
            'moyenne_t3' => $moyenne($parTrimestre[3]),
            'rang' => $meilleures + 1,
            'total_etudiants' => count($camarades),
            'total_absences' => $this->absenceRepository->countByEtudiant($etudiant, $periodeActive),
            'absences_justifiees' => $this->absenceRepository->countByEtudiant($etudiant, $periodeActive, true),
            'taux_assiduite' => $this->absenceRepository->calculateTauxAssiduite($etudiant, $periodeActive),
        ];
    }

    /**
     * @return Note[]
     */
    private function findNotes(Etudiant $etudiant, ?PeriodeAcademique $periode): array
    {
        $criteres = ['etudiant' => $etudiant];
        if ($periode) {
            $criteres['periodeAcademique'] = $periode;
        }

        return $this->noteRepository->findBy($criteres);
    }

    private function numeroTrimestre(?string $nom): ?int
    {
        if (!$nom || !preg_match('/^\s*(\d)\D*trimestre/i', $nom, $correspondance)) {
            return null;
        }

        return in_array((int) $correspondance[1], [1, 2, 3], true) ? (int) $correspondance[1] : null;
    }
}

