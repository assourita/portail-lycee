<?php

namespace App\Controller;

use App\Entity\EmploiDuTemps;
use App\Entity\Jour;
use App\Entity\Matiere;
use App\Entity\Cours;
use App\Entity\Filiere;
use App\Entity\Enseignant;
use App\Form\EmploiDuTempsType;
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

#[Route('/admin/emploi-du-temps')]
#[IsGranted('ROLE_ADMIN')]
class AdminEmploiDuTempsController extends AbstractController
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

    #[Route('/', name: 'admin_emploi_du_temps_index', methods: ['GET'])]
    public function index(): Response
    {
        $emploisDuTemps = $this->emploiDuTempsRepository->findAll();
        
        // Grouper par filière pour un affichage plus organisé
        $emploisParFiliere = [];
        foreach ($emploisDuTemps as $emploi) {
            $filiereNom = $emploi->getFiliere() ? $emploi->getFiliere()->getNom() : 'Non assigné';
            $emploisParFiliere[$filiereNom][] = $emploi;
        }

        return $this->render('admin/emploi_du_temps/index.html.twig', [
            'emplois_par_filiere' => $emploisParFiliere,
            'total_emplois' => count($emploisDuTemps),
        ]);
    }

    #[Route('/new', name: 'admin_emploi_du_temps_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $filiereId = $request->query->get('filiere');
        $filiere = null;
        
        if ($filiereId) {
            $filiere = $this->filiereRepository->find($filiereId);
        }

        if ($request->isMethod('POST')) {
            $filiereId = $request->request->get('filiere_id');
            $filiere = $this->filiereRepository->find($filiereId);
            
            if ($filiere) {
                $data = $request->request->all();
                // Debug: Log les données reçues
                error_log('Données POST reçues: ' . json_encode($data));
                $this->processEmploiDuTempsData($data, $filiere);
                $this->addFlash('success', 'Emploi du temps créé avec succès.');
            } else {
                $this->addFlash('error', 'Filière non trouvée.');
            }
            
            return $this->redirectToRoute('admin_emploi_du_temps_index');
        }

        $filieres = $this->filiereRepository->findAll();
        $enseignants = $this->enseignantRepository->findAll();
        $matieres = $this->matiereRepository->createQueryBuilder('m')
            ->leftJoin('m.filiere', 'f')
            ->leftJoin('m.enseignant', 'e')
            ->addSelect('f', 'e')
            ->getQuery()
            ->getResult();
        
        // Récupérer les jours dans l'ordre correct (Lundi à Dimanche)
        $jours = $this->jourRepository->createQueryBuilder('j')
            ->orderBy('j.ordre', 'ASC')
            ->getQuery()
            ->getResult();

        // Formater les données pour JavaScript
        $formattedData = $this->formatDataForJavaScript($jours, $matieres, $enseignants);

        return $this->render('admin/emploi_du_temps/new.html.twig', [
            'filieres' => $filieres,
            'enseignants' => $enseignants,
            'matieres' => $matieres,
            'jours' => $jours,
            'selected_filiere' => $filiere,
            'jours_data' => $formattedData['jours_data'],
            'matieres_data' => $formattedData['matieres_data'],
            'enseignants_data' => $formattedData['enseignants_data'],
        ]);
    }

    #[Route('/{id}', name: 'admin_emploi_du_temps_show', methods: ['GET'])]
    public function show(EmploiDuTemps $emploiDuTemps): Response
    {
        return $this->render('admin/emploi_du_temps/show.html.twig', [
            'emploi_du_temps' => $emploiDuTemps,
        ]);
    }

    #[Route('/filiere/{id}/edit', name: 'admin_emploi_du_temps_filiere_edit', methods: ['GET', 'POST'])]
    public function editByFiliere(Request $request, Filiere $filiere): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $this->processEmploiDuTempsData($data, $filiere);
            $this->addFlash('success', 'L\'emploi du temps a été modifié avec succès.');
            return $this->redirectToRoute('admin_emploi_du_temps_index');
        }

        // Récupérer tous les emplois du temps pour cette filière avec leurs relations
        $emploisExistants = $this->emploiDuTempsRepository->createQueryBuilder('e')
            ->leftJoin('e.jour', 'j')
            ->leftJoin('e.matiere', 'm')
            ->leftJoin('e.enseignant', 'ens')
            ->leftJoin('e.cours', 'c')
            ->leftJoin('e.filiere', 'f')
            ->addSelect('j', 'm', 'ens', 'c', 'f')
            ->where('e.filiere = :filiere')
            ->setParameter('filiere', $filiere)
            ->getQuery()
            ->getResult();
        
        $filieres = $this->filiereRepository->findAll();
        $enseignants = $this->enseignantRepository->findAll();
        $matieres = $this->matiereRepository->createQueryBuilder('m')
            ->leftJoin('m.filiere', 'f')
            ->leftJoin('m.enseignant', 'e')
            ->addSelect('f', 'e')
            ->getQuery()
            ->getResult();
        
        // Récupérer les jours dans l'ordre correct (Lundi à Dimanche)
        $jours = $this->jourRepository->createQueryBuilder('j')
            ->orderBy('j.ordre', 'ASC')
            ->getQuery()
            ->getResult();

        // Formater les données pour JavaScript
        $formattedData = $this->formatDataForJavaScript($jours, $matieres, $enseignants);

        return $this->render('admin/emploi_du_temps/edit.html.twig', [
            'emploi_du_temps' => (object)['filiere' => $filiere], // Objet temporaire pour le template
            'emplois_existants' => $emploisExistants,
            'filieres' => $filieres,
            'enseignants' => $enseignants,
            'matieres' => $matieres,
            'jours' => $jours,
            'jours_data' => $formattedData['jours_data'],
            'matieres_data' => $formattedData['matieres_data'],
            'enseignants_data' => $formattedData['enseignants_data'],
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_emploi_du_temps_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EmploiDuTemps $emploiDuTemps): Response
    {
        if ($request->isMethod('POST')) {
            $filiere = $emploiDuTemps->getFiliere();
            if ($filiere) {
                $data = $request->request->all();
                $this->processEmploiDuTempsData($data, $filiere);
                $this->addFlash('success', 'L\'emploi du temps a été modifié avec succès.');
                return $this->redirectToRoute('admin_emploi_du_temps_index');
            } else {
                $this->addFlash('error', 'Filière non trouvée.');
            }
        }

        // Récupérer tous les emplois du temps pour cette filière avec leurs relations
        $emploisExistants = $this->emploiDuTempsRepository->createQueryBuilder('e')
            ->leftJoin('e.jour', 'j')
            ->leftJoin('e.matiere', 'm')
            ->leftJoin('e.enseignant', 'ens')
            ->leftJoin('e.cours', 'c')
            ->leftJoin('e.filiere', 'f')
            ->addSelect('j', 'm', 'ens', 'c', 'f')
            ->where('e.filiere = :filiere')
            ->setParameter('filiere', $emploiDuTemps->getFiliere())
            ->getQuery()
            ->getResult();
        
        $filieres = $this->filiereRepository->findAll();
        $enseignants = $this->enseignantRepository->findAll();
        $matieres = $this->matiereRepository->createQueryBuilder('m')
            ->leftJoin('m.filiere', 'f')
            ->leftJoin('m.enseignant', 'e')
            ->addSelect('f', 'e')
            ->getQuery()
            ->getResult();
        
        // Récupérer les jours dans l'ordre correct (Lundi à Dimanche)
        $jours = $this->jourRepository->createQueryBuilder('j')
            ->orderBy('j.ordre', 'ASC')
            ->getQuery()
            ->getResult();

        // Formater les données pour JavaScript
        $formattedData = $this->formatDataForJavaScript($jours, $matieres, $enseignants);

        return $this->render('admin/emploi_du_temps/edit.html.twig', [
            'emploi_du_temps' => $emploiDuTemps,
            'emplois_existants' => $emploisExistants,
            'filieres' => $filieres,
            'enseignants' => $enseignants,
            'matieres' => $matieres,
            'jours' => $jours,
            'jours_data' => $formattedData['jours_data'],
            'matieres_data' => $formattedData['matieres_data'],
            'enseignants_data' => $formattedData['enseignants_data'],
        ]);
    }

    #[Route('/{id}', name: 'admin_emploi_du_temps_delete', methods: ['POST'])]
    public function delete(Request $request, EmploiDuTemps $emploiDuTemps): Response
    {
        if ($this->isCsrfTokenValid('delete'.$emploiDuTemps->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($emploiDuTemps);
            $this->entityManager->flush();
            $this->addFlash('success', 'Emploi du temps supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_emploi_du_temps_index');
    }

    #[Route('/filiere/{id}', name: 'admin_emploi_du_temps_filiere', methods: ['GET'])]
    public function byFiliere(Filiere $filiere): Response
    {
        $emploisDuTemps = $this->emploiDuTempsRepository->findBy(['filiere' => $filiere]);
        
        // Grouper par jour de la semaine
        $emploisParJour = [];
        $jours = $this->jourRepository->findAll();
        
        foreach ($jours as $jour) {
            $emploisParJour[$jour->getNom()] = [];
        }
        
        foreach ($emploisDuTemps as $emploi) {
            if ($emploi->getJour()) {
                $emploisParJour[$emploi->getJour()->getNom()][] = $emploi;
            }
        }

        return $this->render('admin/emploi_du_temps/filiere.html.twig', [
            'filiere' => $filiere,
            'emplois_par_jour' => $emploisParJour,
        ]);
    }

    #[Route('/enseignant/{id}', name: 'admin_emploi_du_temps_enseignant', methods: ['GET'])]
    public function byEnseignant(Enseignant $enseignant): Response
    {
        $emploisDuTemps = $this->emploiDuTempsRepository->findBy(['enseignant' => $enseignant]);
        
        // Grouper par jour de la semaine
        $emploisParJour = [];
        $jours = $this->jourRepository->findAll();
        
        foreach ($jours as $jour) {
            $emploisParJour[$jour->getNom()] = [];
        }
        
        foreach ($emploisDuTemps as $emploi) {
            if ($emploi->getJour()) {
                $emploisParJour[$emploi->getJour()->getNom()][] = $emploi;
            }
        }

        return $this->render('admin/emploi_du_temps/enseignant.html.twig', [
            'enseignant' => $enseignant,
            'emplois_par_jour' => $emploisParJour,
        ]);
    }

    private function processEmploiDuTempsData(array $data, ?Filiere $filiere): void
    {
        if (!$filiere) {
            return;
        }

        // Les données viennent directement du formulaire avec des clés numériques
        $emploiData = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'emploi_du_temps[') === 0) {
                // Extraire l'index et le champ
                preg_match('/emploi_du_temps\[(\d+)\]\[([^\]]+)\]/', $key, $matches);
                if (count($matches) === 3) {
                    $index = $matches[1];
                    $field = $matches[2];
                    $emploiData[$index][$field] = $value;
                }
            }
        }
        
        // Récupérer les emplois du temps existants pour cette filière
        $existingEmplois = $this->emploiDuTempsRepository->findBy(['filiere' => $filiere]);
        
        // Créer un tableau des emplois existants indexés par leurs caractéristiques
        $existingEmploisMap = [];
        foreach ($existingEmplois as $emploi) {
            $key = $this->getEmploiKey($emploi);
            $existingEmploisMap[$key] = $emploi;
        }
        
        // Traiter chaque créneau du formulaire
        $processedKeys = [];
        foreach ($emploiData as $creneauIndex => $creneau) {
            if (empty($creneau['jour']) || empty($creneau['heure_debut']) || empty($creneau['heure_fin']) || 
                empty($creneau['enseignant']) || empty($creneau['matiere'])) {
                continue;
            }

            $jour = $this->jourRepository->find($creneau['jour']);
            if (!$jour) continue;
            
            $enseignant = $this->enseignantRepository->find($creneau['enseignant']);
            $matiere = $this->matiereRepository->find($creneau['matiere']);
            
            if (!$enseignant || !$matiere) continue;
            
            // Créer une clé pour ce créneau
            $creneauKey = $this->getCreneauKey($jour, $creneau['heure_debut'], $creneau['heure_fin'], $enseignant, $matiere);
            $processedKeys[] = $creneauKey;
            
            // Vérifier si cet emploi existe déjà
            if (isset($existingEmploisMap[$creneauKey])) {
                // L'emploi existe déjà, on le met à jour si nécessaire
                $emploi = $existingEmploisMap[$creneauKey];
                $this->updateEmploiIfNeeded($emploi, $creneau);
            } else {
                // Créer un nouvel emploi du temps
                $emploi = new EmploiDuTemps();
                $emploi->setJour($jour);
                $emploi->setFiliere($filiere);
                $emploi->setHeureDebut(new \DateTime($creneau['heure_debut']));
                $emploi->setHeureFin(new \DateTime($creneau['heure_fin']));
                $emploi->setEnseignant($enseignant);
                $emploi->setMatiere($matiere);
                
                if (!empty($creneau['cours'])) {
                    $cours = $this->coursRepository->find($creneau['cours']);
                    if ($cours) {
                        $emploi->setCours($cours);
                    }
                }
                
                $this->entityManager->persist($emploi);
            }
        }
        
        // Supprimer les emplois qui ne sont plus dans le formulaire
        foreach ($existingEmploisMap as $key => $emploi) {
            if (!in_array($key, $processedKeys)) {
                $this->entityManager->remove($emploi);
            }
        }

        $this->entityManager->flush();
    }
    
    private function getEmploiKey(EmploiDuTemps $emploi): string
    {
        return sprintf(
            '%d_%s_%s_%d_%d',
            $emploi->getJour() ? $emploi->getJour()->getId() : 0,
            $emploi->getHeureDebut() ? $emploi->getHeureDebut()->format('H:i') : '',
            $emploi->getHeureFin() ? $emploi->getHeureFin()->format('H:i') : '',
            $emploi->getEnseignant() ? $emploi->getEnseignant()->getId() : 0,
            $emploi->getMatiere() ? $emploi->getMatiere()->getId() : 0
        );
    }
    
    private function getCreneauKey($jour, string $heureDebut, string $heureFin, $enseignant, $matiere): string
    {
        return sprintf(
            '%d_%s_%s_%d_%d',
            $jour->getId(),
            $heureDebut,
            $heureFin,
            $enseignant->getId(),
            $matiere->getId()
        );
    }
    
    private function updateEmploiIfNeeded(EmploiDuTemps $emploi, array $creneau, $cours = null): void
    {
        // Pour l'instant, on ne met à jour que le cours si nécessaire
        if (!empty($creneau['cours'])) {
            $cours = $this->coursRepository->find($creneau['cours']);
            if ($cours && $emploi->getCours() !== $cours) {
                $emploi->setCours($cours);
            }
        } elseif ($emploi->getCours() !== null) {
            $emploi->setCours(null);
        }
    }

    private function formatDataForJavaScript(array $jours, array $matieres, array $enseignants): array
    {
        // Formater les jours
        $joursData = [];
        foreach ($jours as $jour) {
            $joursData[] = [
                'id' => $jour->getId(),
                'nom' => $jour->getNom(),
                'ordre' => $jour->getOrdre()
            ];
        }

        // Formater les matières
        $matieresData = [];
        foreach ($matieres as $matiere) {
            $matieresData[] = [
                'id' => $matiere->getId(),
                'nom' => $matiere->getNom(),
                'filiere_id' => $matiere->getFiliere() ? $matiere->getFiliere()->getId() : null,
                'enseignant_id' => $matiere->getEnseignant() ? $matiere->getEnseignant()->getId() : null
            ];
        }

        // Formater les enseignants
        $enseignantsData = [];
        foreach ($enseignants as $enseignant) {
            $enseignantsData[] = [
                'id' => $enseignant->getId(),
                'nom' => $enseignant->getNom(),
                'prenom' => $enseignant->getPrenom()
            ];
        }

        return [
            'jours_data' => $joursData,
            'matieres_data' => $matieresData,
            'enseignants_data' => $enseignantsData,
        ];
    }

    #[Route('/api/matieres/{filiereId}', name: 'admin_emploi_du_temps_api_matieres', methods: ['GET'])]
    public function getMatieresByFiliere(int $filiereId): Response
    {
        $filiere = $this->filiereRepository->find($filiereId);
        if (!$filiere) {
            return new Response('Filière non trouvée', 404);
        }

        $matieres = $this->matiereRepository->findBy(['filiere' => $filiere]);
        
        $data = [];
        foreach ($matieres as $matiere) {
            $data[] = [
                'id' => $matiere->getId(),
                'nom' => $matiere->getNom(),
                'coefficient' => $matiere->getCoeficient()
            ];
        }

        return new Response(json_encode($data), 200, ['Content-Type' => 'application/json']);
    }

    #[Route('/api/enseignants/{matiereId}', name: 'admin_emploi_du_temps_api_enseignants', methods: ['GET'])]
    public function getEnseignantsByMatiere(int $matiereId): Response
    {
        $matiere = $this->matiereRepository->find($matiereId);
        if (!$matiere) {
            return new Response('Matière non trouvée', 404);
        }

        $enseignant = $matiere->getEnseignant();
        if (!$enseignant) {
            return new Response(json_encode([]), 200, ['Content-Type' => 'application/json']);
        }

        $data = [[
            'id' => $enseignant->getId(),
            'nom' => $enseignant->getNom(),
            'prenom' => $enseignant->getPrenom()
        ]];

        return new Response(json_encode($data), 200, ['Content-Type' => 'application/json']);
    }

        #[Route('/test-data', name: 'admin_emploi_du_temps_test_data', methods: ['GET'])]
        public function testData(): Response
        {
            // Vérifier les emplois du temps existants
            $emplois = $this->emploiDuTempsRepository->findAll();
            $data = [];
            
            foreach ($emplois as $emploi) {
                $data[] = [
                    'id' => $emploi->getId(),
                    'jour' => $emploi->getJour() ? $emploi->getJour()->getNom() : 'N/A',
                    'heure_debut' => $emploi->getHeureDebut() ? $emploi->getHeureDebut()->format('H:i') : 'N/A',
                    'heure_fin' => $emploi->getHeureFin() ? $emploi->getHeureFin()->format('H:i') : 'N/A',
                    'matiere' => $emploi->getMatiere() ? $emploi->getMatiere()->getNom() : 'N/A',
                    'enseignant' => $emploi->getEnseignant() ? $emploi->getEnseignant()->getNom() : 'N/A',
                    'filiere' => $emploi->getFiliere() ? $emploi->getFiliere()->getNom() : 'N/A',
                ];
            }
            
            return new Response(json_encode([
                'count' => count($emplois),
                'data' => $data
            ], JSON_PRETTY_PRINT), 200, ['Content-Type' => 'application/json']);
        }

        #[Route('/test-create', name: 'admin_emploi_du_temps_test_create', methods: ['GET'])]
        public function testCreate(): Response
        {
            // Créer un emploi du temps de test
            $filiere = $this->filiereRepository->findOneBy([]);
            $jour = $this->jourRepository->findOneBy([]);
            $matiere = $this->matiereRepository->findOneBy([]);
            $enseignant = $this->enseignantRepository->findOneBy([]);
            
            if (!$filiere || !$jour || !$matiere || !$enseignant) {
                return new Response('Données manquantes pour le test', 400);
            }
            
            $emploi = new EmploiDuTemps();
            $emploi->setFiliere($filiere);
            $emploi->setJour($jour);
            $emploi->setMatiere($matiere);
            $emploi->setEnseignant($enseignant);
            $emploi->setHeureDebut(new \DateTime('08:00'));
            $emploi->setHeureFin(new \DateTime('10:00'));
            
            $this->entityManager->persist($emploi);
            $this->entityManager->flush();
            
        return new Response('Emploi du temps de test créé avec succès', 200);
    }

        #[Route('/test/jours', name: 'admin_emploi_du_temps_test_jours', methods: ['GET'])]
        public function testJours(): Response
        {
            $jours = $this->jourRepository->findAll();
            $data = [];
            
            foreach ($jours as $jour) {
                $data[] = [
                    'id' => $jour->getId(),
                    'nom' => $jour->getNom(),
                    'ordre' => $jour->getOrdre()
                ];
            }
            
            return new Response(json_encode([
                'count' => count($jours),
                'data' => $data
            ], JSON_PRETTY_PRINT), 200, ['Content-Type' => 'application/json']);
        }

        #[Route('/test/format-data', name: 'admin_emploi_du_temps_test_format_data', methods: ['GET'])]
        public function testFormatData(): Response
        {
            $jours = $this->jourRepository->createQueryBuilder('j')
                ->orderBy('j.ordre', 'ASC')
                ->getQuery()
                ->getResult();
            
            $matieres = $this->matiereRepository->createQueryBuilder('m')
                ->leftJoin('m.filiere', 'f')
                ->leftJoin('m.enseignant', 'e')
                ->addSelect('f', 'e')
                ->getQuery()
                ->getResult();
            
            $enseignants = $this->enseignantRepository->findAll();
            
            $formattedData = $this->formatDataForJavaScript($jours, $matieres, $enseignants);
            
            return new Response(json_encode([
                'jours_data' => $formattedData['jours_data'],
                'matieres_data' => $formattedData['matieres_data'],
                'enseignants_data' => $formattedData['enseignants_data'],
            ], JSON_PRETTY_PRINT), 200, ['Content-Type' => 'application/json']);
        }
}
