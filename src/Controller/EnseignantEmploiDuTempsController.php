<?php

namespace App\Controller;

use App\Entity\EmploiDuTemps;
use App\Entity\Jour;
use App\Entity\Matiere;
use App\Entity\Cours;
use App\Entity\Filiere;
use App\Entity\Enseignant;
use App\Repository\EmploiDuTempsRepository;
use App\Repository\JourRepository;
use App\Repository\MatiereRepository;
use App\Repository\CoursRepository;
use App\Repository\FiliereRepository;
use App\Repository\EnseignantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/enseignant/emploi-du-temps')]
#[IsGranted('ROLE_ENSEIGNANT')]
class EnseignantEmploiDuTempsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmploiDuTempsRepository $emploiDuTempsRepository,
        private JourRepository $jourRepository,
        private MatiereRepository $matiereRepository,
        private CoursRepository $coursRepository,
        private FiliereRepository $filiereRepository,
        private EnseignantRepository $enseignantRepository
    ) {}

    #[Route('/', name: 'app_enseignant_emploi_du_temps', methods: ['GET'])]
    public function index(): Response
    {
        /** @var Enseignant $enseignant */
        $enseignant = $this->getUser()->getEnseignant();
        
        if (!$enseignant) {
            $this->addFlash('error', 'Profil enseignant non trouvé.');
            return $this->redirectToRoute('enseignant_dashboard');
        }

        // Récupérer les emplois du temps de l'enseignant
        $emploisDuTemps = $this->emploiDuTempsRepository->findBy(
            ['enseignant' => $enseignant],
            ['jour' => 'ASC', 'heureDebut' => 'ASC']
        );

        // Grouper par jour de la semaine pour un affichage organisé
        $emploisParJour = [];
        $jours = $this->jourRepository->findBy([], ['ordre' => 'ASC']);
        
        foreach ($jours as $jour) {
            $emploisParJour[$jour->getNom()] = [];
        }
        
        foreach ($emploisDuTemps as $emploi) {
            if ($emploi->getJour()) {
                $emploisParJour[$emploi->getJour()->getNom()][] = $emploi;
            }
        }

        // Statistiques
        $stats = [
            'total_heures' => 0,
            'total_cours' => count($emploisDuTemps),
            'total_filieres' => 0,
            'matieres_differentes' => []
        ];

        $filieres = [];
        foreach ($emploisDuTemps as $emploi) {
            if ($emploi->getHeureDebut() && $emploi->getHeureFin()) {
                $interval = $emploi->getHeureDebut()->diff($emploi->getHeureFin());
                $stats['total_heures'] += $interval->h + ($interval->i / 60);
            }
            
            if ($emploi->getFiliere()) {
                $filieres[$emploi->getFiliere()->getId()] = $emploi->getFiliere();
            }
            
            if ($emploi->getMatiere()) {
                $stats['matieres_differentes'][$emploi->getMatiere()->getId()] = $emploi->getMatiere()->getNom();
            }
        }
        
        $stats['total_filieres'] = count($filieres);
        $stats['matieres_differentes'] = count($stats['matieres_differentes']);

        return $this->render('enseignant/emploi_du_temps/index.html.twig', [
            'enseignant' => $enseignant,
            'emplois_par_jour' => $emploisParJour,
            'jours' => $jours,
            'stats' => $stats,
            'emplois_du_temps' => $emploisDuTemps
        ]);
    }

    #[Route('/semaine', name: 'enseignant_emploi_du_temps_semaine', methods: ['GET'])]
    public function semaine(): Response
    {
        /** @var Enseignant $enseignant */
        $enseignant = $this->getUser()->getEnseignant();
        
        if (!$enseignant) {
            $this->addFlash('error', 'Profil enseignant non trouvé.');
            return $this->redirectToRoute('enseignant_dashboard');
        }

        // Récupérer la semaine actuelle
        $semaineActuelle = new \DateTime();
        $lundi = (clone $semaineActuelle)->modify('monday this week');
        $dimanche = (clone $semaineActuelle)->modify('sunday this week');

        // Récupérer les emplois du temps de l'enseignant pour la semaine
        $emploisDuTemps = $this->emploiDuTempsRepository->createQueryBuilder('e')
            ->where('e.enseignant = :enseignant')
            ->andWhere('e.jour IS NOT NULL')
            ->setParameter('enseignant', $enseignant)
            ->orderBy('e.jour', 'ASC')
            ->addOrderBy('e.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();

        // Créer une grille hebdomadaire
        $grille = [];
        $jours = $this->jourRepository->findBy([], ['ordre' => 'ASC']);
        $heures = $this->generateHeures();

        foreach ($jours as $jour) {
            $grille[$jour->getNom()] = [];
            foreach ($heures as $heure) {
                $grille[$jour->getNom()][$heure] = null;
            }
        }

        // Remplir la grille avec les cours
        foreach ($emploisDuTemps as $emploi) {
            if ($emploi->getJour() && $emploi->getHeureDebut()) {
                $jourNom = $emploi->getJour()->getNom();
                $heureDebut = $emploi->getHeureDebut()->format('H:00');
                
                if (isset($grille[$jourNom][$heureDebut])) {
                    $grille[$jourNom][$heureDebut] = $emploi;
                }
            }
        }

        return $this->render('enseignant/emploi_du_temps/semaine.html.twig', [
            'enseignant' => $enseignant,
            'grille' => $grille,
            'jours' => $jours,
            'heures' => $heures,
            'lundi' => $lundi,
            'dimanche' => $dimanche
        ]);
    }

    #[Route('/filiere/{filiereId}', name: 'enseignant_emploi_du_temps_filiere', methods: ['GET'])]
    public function byFiliere(int $filiereId): Response
    {
        /** @var Enseignant $enseignant */
        $enseignant = $this->getUser()->getEnseignant();
        
        if (!$enseignant) {
            $this->addFlash('error', 'Profil enseignant non trouvé.');
            return $this->redirectToRoute('enseignant_dashboard');
        }

        $filiere = $this->filiereRepository->find($filiereId);
        if (!$filiere) {
            $this->addFlash('error', 'Filière non trouvée.');
            return $this->redirectToRoute('app_enseignant_emploi_du_temps');
        }

        // Récupérer les emplois du temps de l'enseignant pour cette filière
        $emploisDuTemps = $this->emploiDuTempsRepository->findBy([
            'enseignant' => $enseignant,
            'filiere' => $filiere
        ], ['jour' => 'ASC', 'heureDebut' => 'ASC']);

        // Grouper par jour
        $emploisParJour = [];
        $jours = $this->jourRepository->findBy([], ['ordre' => 'ASC']);
        
        foreach ($jours as $jour) {
            $emploisParJour[$jour->getNom()] = [];
        }
        
        foreach ($emploisDuTemps as $emploi) {
            if ($emploi->getJour()) {
                $emploisParJour[$emploi->getJour()->getNom()][] = $emploi;
            }
        }

        return $this->render('enseignant/emploi_du_temps/filiere.html.twig', [
            'enseignant' => $enseignant,
            'filiere' => $filiere,
            'emplois_par_jour' => $emploisParJour,
            'jours' => $jours,
            'emplois_du_temps' => $emploisDuTemps
        ]);
    }

    #[Route('/export', name: 'enseignant_emploi_du_temps_export', methods: ['GET'])]
    public function export(): Response
    {
        /** @var Enseignant $enseignant */
        $enseignant = $this->getUser()->getEnseignant();
        
        if (!$enseignant) {
            $this->addFlash('error', 'Profil enseignant non trouvé.');
            return $this->redirectToRoute('enseignant_dashboard');
        }

        // Récupérer les emplois du temps de l'enseignant
        $emploisDuTemps = $this->emploiDuTempsRepository->findBy(
            ['enseignant' => $enseignant],
            ['jour' => 'ASC', 'heureDebut' => 'ASC']
        );

        // Créer le contenu CSV
        $csvContent = "Jour;Heure Début;Heure Fin;Matière;Filière;Salle\n";
        
        foreach ($emploisDuTemps as $emploi) {
            $ligne = sprintf(
                "%s;%s;%s;%s;%s;%s\n",
                $emploi->getJour() ? $emploi->getJour()->getNom() : '',
                $emploi->getHeureDebut() ? $emploi->getHeureDebut()->format('H:i') : '',
                $emploi->getHeureFin() ? $emploi->getHeureFin()->format('H:i') : '',
                $emploi->getMatiere() ? $emploi->getMatiere()->getNom() : '',
                $emploi->getFiliere() ? $emploi->getFiliere()->getNom() : '',
                $emploi->getCours() ? $emploi->getCours()->getSalle() : ''
            );
            $csvContent .= $ligne;
        }

        // Créer la réponse
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="emploi_du_temps_' . $enseignant->getNom() . '.csv"');

        return $response;
    }

    private function generateHeures(): array
    {
        $heures = [];
        for ($i = 8; $i <= 18; $i++) {
            $heures[] = sprintf('%02d:00', $i);
        }
        return $heures;
    }
}
