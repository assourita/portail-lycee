<?php
// src/Controller/AdminController.php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Etudiant;
use App\Entity\Enseignant;
use App\Entity\Matiere;
use App\Entity\Filiere;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Form\EnseignantType;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    /**
     * Crée le compte de connexion d'une personne avec un mot de passe temporaire aléatoire,
     * affiché une seule fois à l'administrateur.
     */
    private function creerCompte(EntityManagerInterface $entityManager, string $role, ?string $email, string $nom, string $prenom): User
    {
        $base = $email ?: strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $prenom.'.'.$nom) ?: $prenom.'.'.$nom);
        $base = preg_replace('/[^a-z0-9.@_-]/i', '', $base) ?: 'utilisateur';
        $username = $base;
        $repository = $entityManager->getRepository(User::class);
        for ($i = 2; $repository->findOneBy(['username' => $username]); $i++) {
            $username = $base.$i;
        }

        $motDePasse = 'Lycee-'.bin2hex(random_bytes(4));
        $user = (new User())
            ->setUsername($username)
            ->setEmail($email)
            ->setRoles([$role])
            ->setIsActivate(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, $motDePasse));
        $entityManager->persist($user);

        $this->addFlash('info', sprintf('Compte créé - identifiant : %s / mot de passe temporaire : %s (à communiquer à l\'intéressé).', $username, $motDePasse));

        return $user;
    }

    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager, PeriodeAcademiqueService $periodeService): Response
    {
        $periodeActive = $periodeService->getPeriodeActive();
        
        // Récupération des statistiques
        $stats = [
            'etudiants' => $entityManager->getRepository(Etudiant::class)->count([]),
            'enseignants' => $entityManager->getRepository(Enseignant::class)->count([]),
            'matieres' => $entityManager->getRepository(Matiere::class)->count([]),
            'filieres' => $entityManager->getRepository(Filiere::class)->count([]),
        ];

        // Récupération des dernières activités
        $recentActivities = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.lastLogin', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->findAll();
        
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/etudiants', name: 'admin_etudiants')]
    public function etudiants(EntityManagerInterface $entityManager): Response
    {
        $etudiants = $entityManager->getRepository(Etudiant::class)->findAll();
        
        return $this->render('admin/etudiants.html.twig', [
            'etudiants' => $etudiants,
        ]);
    }

    #[Route('/enseignants', name: 'admin_enseignants')]
    public function enseignants(EntityManagerInterface $entityManager): Response
    {
        $enseignants = $entityManager->getRepository(Enseignant::class)->findAll();
        
        return $this->render('admin/enseignants.html.twig', [
            'enseignants' => $enseignants,
        ]);
    }

    #[Route('/matieres', name: 'admin_matieres')]
    public function matieres(EntityManagerInterface $entityManager): Response
    {
        $matieres = $entityManager->getRepository(Matiere::class)->findAll();
        
        return $this->render('admin/matieres.html.twig', [
            'matieres' => $matieres,
        ]);
    }

    #[Route('/filieres', name: 'admin_filieres')]
    public function filieres(EntityManagerInterface $entityManager): Response
    {
        $filieres = $entityManager->getRepository(Filiere::class)->findAll();
        
        return $this->render('admin/filieres.html.twig', [
            'filieres' => $filieres,
        ]);
    }

    #[Route('/matiere/new', name: 'admin_matiere_new')]
    public function newMatiere(Request $request, EntityManagerInterface $entityManager): Response
    {
        $matiere = new Matiere();
        $form = $this->createFormBuilder($matiere)
            ->add('nom', null, [
                'label' => 'Nom de la matière',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Mathématiques, Français...']
            ])
            ->add('coeficient', null, [
                'label' => 'Coefficient',
                'attr' => ['class' => 'form-control', 'min' => 1]
            ])
            ->add('filiere', null, [
                'label' => 'Filière',
                'placeholder' => 'Sélectionner une filière',
                'attr' => ['class' => 'form-control']
            ])
            ->add('enseignant', null, [
                'label' => 'Enseignant',
                'placeholder' => 'Sélectionner un enseignant',
                'attr' => ['class' => 'form-control']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($matiere);
            $entityManager->flush();

            $this->addFlash('success', 'La matière a été créée avec succès.');
            return $this->redirectToRoute('admin_matieres');
        }

        return $this->render('admin/matiere/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/filiere/new', name: 'admin_filiere_new')]
    public function newFiliere(Request $request, EntityManagerInterface $entityManager): Response
    {
        $filiere = new Filiere();
        $form = $this->createFormBuilder($filiere)
            ->add('nom', null, [
                'label' => 'Nom de la filière',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Sciences Exactes, Sciences Humaines...']
            ])
            ->add('classe', null, [
                'label' => 'Classe',
                'placeholder' => 'Sélectionner une classe',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($filiere);
            $entityManager->flush();

            $this->addFlash('success', 'La filière a été créée avec succès.');
            return $this->redirectToRoute('admin_filieres');
        }

        return $this->render('admin/filiere/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/etudiant/new', name: 'admin_etudiant_new')]
    public function newEtudiant(Request $request, EntityManagerInterface $entityManager): Response
    {
        $etudiant = new Etudiant();
        $form = $this->createFormBuilder($etudiant)
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('dateNaissance', null, [
                'widget' => 'single_text',
            ])
            ->add('filiere')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $etudiant->setUser($this->creerCompte($entityManager, 'ROLE_ETUDIANT', $etudiant->getEmail(), $etudiant->getNom(), $etudiant->getPrenom()));
            $entityManager->persist($etudiant);
            $entityManager->flush();

            $this->addFlash('success', 'L\'étudiant et son utilisateur ont été créés avec succès.');
            return $this->redirectToRoute('admin_etudiants');
        }

        return $this->render('admin/etudiant/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/enseignant/new', name: 'admin_enseignant_new')]
    public function newEnseignant(Request $request, EntityManagerInterface $entityManager): Response
    {
        $enseignant = new Enseignant();
        $form = $this->createForm(EnseignantType::class, $enseignant);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $enseignant->setUser($this->creerCompte($entityManager, 'ROLE_ENSEIGNANT', $enseignant->getEmail(), $enseignant->getNom(), $enseignant->getPrenom()));
            $entityManager->persist($enseignant);
            $entityManager->flush();

            $this->addFlash('success', 'L\'enseignant et son utilisateur ont été créés avec succès.');
            return $this->redirectToRoute('admin_enseignants');
        }

        return $this->render('admin/enseignant/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/enseignant/{id}', name: 'admin_enseignant_show')]
    public function showEnseignant(Enseignant $enseignant): Response
    {
        return $this->render('admin/enseignant/show.html.twig', [
            'enseignant' => $enseignant,
        ]);
    }

    #[Route('/enseignant/{id}/edit', name: 'admin_enseignant_edit')]
    public function editEnseignant(Request $request, EntityManagerInterface $entityManager, Enseignant $enseignant): Response
    {
        $form = $this->createForm(EnseignantType::class, $enseignant);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Enseignant modifié avec succès.');
            return $this->redirectToRoute('admin_enseignants');
        }

        return $this->render('admin/enseignant/edit.html.twig', [
            'form' => $form->createView(),
            'enseignant' => $enseignant,
        ]);
    }

    #[Route('/enseignant/{id}/delete', name: 'admin_enseignant_delete', methods: ['POST'])]
    public function deleteEnseignant(Request $request, EntityManagerInterface $entityManager, Enseignant $enseignant): Response
    {
        if ($this->isCsrfTokenValid('delete-enseignant-' . $enseignant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($enseignant);
            $entityManager->flush();
            $this->addFlash('success', 'Enseignant supprimé avec succès.');
        }
        return $this->redirectToRoute('admin_enseignants');
    }

    #[Route('/user/{id}', name: 'admin_user_show')]
    public function showUser(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/{id}/edit', name: 'admin_user_edit')]
    public function editUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, User $user): Response
    {
        $form = $this->createFormBuilder($user)
            ->add('username')
            ->add('email')
            ->add('roles', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices'  => [
                    'Admin' => 'ROLE_ADMIN',
                    'Enseignant' => 'ROLE_ENSEIGNANT',
                    'Étudiant' => 'ROLE_ETUDIANT',
                    'Comptable' => 'ROLE_COMPTABLE',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('isActivate')
            ->add('plainPassword', \Symfony\Component\Form\Extension\Core\Type\PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Nouveau mot de passe (laisser vide pour ne pas changer)',
                'attr' => [
                    'placeholder' => 'Entrez un nouveau mot de passe',
                    'class' => 'form-control'
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si un nouveau mot de passe est fourni, le hasher et l'assigner
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
            
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete-user-' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/comptables', name: 'admin_comptables')]
    public function comptables(EntityManagerInterface $entityManager): Response
    {
        $comptables = $entityManager->getRepository(\App\Entity\Comptable::class)->findAll();
        return $this->render('admin/comptables.html.twig', [
            'comptables' => $comptables,
        ]);
    }

    #[Route('/comptable/new', name: 'admin_comptable_new')]
    public function newComptable(Request $request, EntityManagerInterface $entityManager): Response
    {
        $comptable = new \App\Entity\Comptable();
        $form = $this->createFormBuilder($comptable)
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comptable->setUser($this->creerCompte($entityManager, 'ROLE_COMPTABLE', $comptable->getEmail(), $comptable->getNom(), $comptable->getPrenom()));
            $comptable->setPassword(null);
            $entityManager->persist($comptable);
            $entityManager->flush();

            $this->addFlash('success', 'Le comptable et son utilisateur ont été créés avec succès.');
            return $this->redirectToRoute('admin_comptables');
        }

        return $this->render('admin/comptable/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/comptable/{id}', name: 'admin_comptable_show')]
    public function showComptable(\App\Entity\Comptable $comptable): Response
    {
        return $this->render('admin/comptable/show.html.twig', [
            'comptable' => $comptable,
        ]);
    }

    #[Route('/comptable/{id}/edit', name: 'admin_comptable_edit')]
    public function editComptable(Request $request, EntityManagerInterface $entityManager, \App\Entity\Comptable $comptable): Response
    {
        $form = $this->createFormBuilder($comptable)
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Comptable modifié avec succès.');
            return $this->redirectToRoute('admin_comptables');
        }

        return $this->render('admin/comptable/edit.html.twig', [
            'form' => $form->createView(),
            'comptable' => $comptable,
        ]);
    }

    #[Route('/comptable/{id}/delete', name: 'admin_comptable_delete', methods: ['POST'])]
    public function deleteComptable(Request $request, EntityManagerInterface $entityManager, \App\Entity\Comptable $comptable): Response
    {
        if ($this->isCsrfTokenValid('delete-comptable-' . $comptable->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comptable);
            $entityManager->flush();
            $this->addFlash('success', 'Comptable supprimé avec succès.');
        }
        return $this->redirectToRoute('admin_comptables');
    }

    #[Route('/etudiant/{id}', name: 'admin_etudiant_show')]
    public function showEtudiant(Etudiant $etudiant): Response
    {
        return $this->render('admin/etudiant/show.html.twig', [
            'etudiant' => $etudiant,
        ]);
    }

    #[Route('/etudiant/{id}/edit', name: 'admin_etudiant_edit')]
    public function editEtudiant(Request $request, EntityManagerInterface $entityManager, Etudiant $etudiant): Response
    {
        $form = $this->createFormBuilder($etudiant)
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('dateNaissance', null, ['widget' => 'single_text'])
            ->add('filiere')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Étudiant modifié avec succès.');
            return $this->redirectToRoute('admin_etudiants');
        }

        return $this->render('admin/etudiant/edit.html.twig', [
            'form' => $form->createView(),
            'etudiant' => $etudiant,
        ]);
    }

    #[Route('/etudiant/{id}/delete', name: 'admin_etudiant_delete', methods: ['POST'])]
    public function deleteEtudiant(Request $request, EntityManagerInterface $entityManager, Etudiant $etudiant): Response
    {
        if ($this->isCsrfTokenValid('delete-etudiant-' . $etudiant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($etudiant);
            $entityManager->flush();
            $this->addFlash('success', 'Étudiant supprimé avec succès.');
        }
        return $this->redirectToRoute('admin_etudiants');
    }

    #[Route('/filiere/{id}', name: 'admin_filiere_show')]
    public function showFiliere(\App\Entity\Filiere $filiere, EntityManagerInterface $entityManager): Response
    {
        // Récupérer toutes les matières de cette filière
        $matieres = $entityManager->getRepository(\App\Entity\Matiere::class)
            ->findBy(['filiere' => $filiere]);
        
        // Récupérer tous les étudiants de cette filière
        $etudiants = $entityManager->getRepository(\App\Entity\Etudiant::class)
            ->findBy(['filiere' => $filiere]);
        
        // Récupérer les emplois du temps de cette filière
        $emploisDuTemps = $entityManager->getRepository(\App\Entity\EmploiDuTemps::class)
            ->findBy(['filiere' => $filiere]);
        
        return $this->render('admin/filiere/show.html.twig', [
            'filiere' => $filiere,
            'matieres' => $matieres,
            'etudiants' => $etudiants,
            'emplois_du_temps' => $emploisDuTemps,
        ]);
    }

    #[Route('/filiere/{id}/edit', name: 'admin_filiere_edit')]
    public function editFiliere(Request $request, EntityManagerInterface $entityManager, \App\Entity\Filiere $filiere): Response
    {
        $form = $this->createFormBuilder($filiere)
            ->add('nom', null, [
                'label' => 'Nom de la filière',
                'attr' => ['class' => 'form-control']
            ])
            ->add('classe', null, [
                'label' => 'Classe',
                'placeholder' => 'Sélectionner une classe',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Filière modifiée avec succès.');
            return $this->redirectToRoute('admin_filieres');
        }

        return $this->render('admin/filiere/edit.html.twig', [
            'form' => $form->createView(),
            'filiere' => $filiere,
        ]);
    }

    #[Route('/filiere/{id}/delete', name: 'admin_filiere_delete', methods: ['POST'])]
    public function deleteFiliere(Request $request, EntityManagerInterface $entityManager, \App\Entity\Filiere $filiere): Response
    {
        if ($this->isCsrfTokenValid('delete-filiere-' . $filiere->getId(), $request->request->get('_token'))) {
            $entityManager->remove($filiere);
            $entityManager->flush();
            $this->addFlash('success', 'Filière supprimée avec succès.');
        }
        return $this->redirectToRoute('admin_filieres');
    }

    #[Route('/matiere/{id}', name: 'admin_matiere_show')]
    public function showMatiere(\App\Entity\Matiere $matiere, EntityManagerInterface $entityManager, PeriodeAcademiqueService $periodeService): Response
    {
        $periodeActive = $periodeService->getPeriodeActive();
        
        // Récupérer tous les étudiants de la filière de cette matière
        $etudiants = [];
        if ($matiere->getFiliere()) {
            $etudiants = $entityManager->getRepository(\App\Entity\Etudiant::class)
                ->findBy(['filiere' => $matiere->getFiliere()]);
        }
        
        // Récupérer toutes les notes pour cette matière (période active)
        $criteria = ['matiere' => $matiere];
        if ($periodeActive) {
            $criteria['periodeAcademique'] = $periodeActive;
        }
        $notes = $entityManager->getRepository(\App\Entity\Note::class)
            ->findBy($criteria);
        
        // Organiser les notes par étudiant
        $notesParEtudiant = [];
        foreach ($notes as $note) {
            if ($note->getEtudiant()) {
                $etudiantId = $note->getEtudiant()->getId();
                if (!isset($notesParEtudiant[$etudiantId])) {
                    $notesParEtudiant[$etudiantId] = [
                        'etudiant' => $note->getEtudiant(),
                        'notes' => []
                    ];
                }
                $notesParEtudiant[$etudiantId]['notes'][] = $note;
            }
        }
        
        // Calculer les moyennes par étudiant
        foreach ($notesParEtudiant as $etudiantId => &$data) {
            $somme = 0;
            $count = 0;
            foreach ($data['notes'] as $note) {
                $somme += $note->getValeur();
                $count++;
            }
            $data['moyenne'] = $count > 0 ? round($somme / $count, 2) : 0;
            $data['total_notes'] = $count;
        }
        
        // Récupérer les cours de cette matière (période active)
        $coursCriteria = ['matiere' => $matiere];
        if ($periodeActive) {
            $coursCriteria['periodeAcademique'] = $periodeActive;
        }
        $cours = $entityManager->getRepository(\App\Entity\Cours::class)
            ->findBy($coursCriteria);
        
        return $this->render('admin/matiere/show.html.twig', [
            'matiere' => $matiere,
            'etudiants' => $etudiants,
            'notes_par_etudiant' => $notesParEtudiant,
            'cours' => $cours,
            'total_notes' => count($notes),
            'periodeActive' => $periodeActive,
        ]);
    }

    #[Route('/cours/{id}', name: 'admin_cours_show')]
    public function showCours(\App\Entity\Cours $cours): Response
    {
        return $this->render('admin/cours/show.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/matiere/{id}/edit', name: 'admin_matiere_edit')]
    public function editMatiere(Request $request, EntityManagerInterface $entityManager, \App\Entity\Matiere $matiere): Response
    {
        $form = $this->createFormBuilder($matiere)
            ->add('nom', null, [
                'label' => 'Nom de la matière',
                'attr' => ['class' => 'form-control']
            ])
            ->add('coeficient', null, [
                'label' => 'Coefficient',
                'attr' => ['class' => 'form-control', 'min' => 1]
            ])
            ->add('filiere', null, [
                'label' => 'Filière',
                'placeholder' => 'Sélectionner une filière',
                'attr' => ['class' => 'form-control']
            ])
            ->add('enseignant', null, [
                'label' => 'Enseignant',
                'placeholder' => 'Sélectionner un enseignant',
                'attr' => ['class' => 'form-control']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Matière modifiée avec succès.');
            return $this->redirectToRoute('admin_matieres');
        }

        return $this->render('admin/matiere/edit.html.twig', [
            'form' => $form->createView(),
            'matiere' => $matiere,
        ]);
    }

    #[Route('/matiere/{id}/delete', name: 'admin_matiere_delete', methods: ['POST'])]
    public function deleteMatiere(Request $request, EntityManagerInterface $entityManager, \App\Entity\Matiere $matiere): Response
    {
        if ($this->isCsrfTokenValid('delete-matiere-' . $matiere->getId(), $request->request->get('_token'))) {
            $entityManager->remove($matiere);
            $entityManager->flush();
            $this->addFlash('success', 'Matière supprimée avec succès.');
        }
        return $this->redirectToRoute('admin_matieres');
    }
}
