<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Etudiant;
use App\Entity\Enseignant;
use App\Entity\Comptable;
use App\Entity\Admin;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
class ProfilController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/', name: 'app_profil')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les informations spécifiques au rôle
        $profilInfo = null;
        $role = '';
        
        if ($user->getEtudiant()) {
            $profilInfo = $user->getEtudiant();
            $role = 'etudiant';
        } elseif ($user->getEnseignant()) {
            $profilInfo = $user->getEnseignant();
            $role = 'enseignant';
        } elseif ($user->getComptable()) {
            $profilInfo = $user->getComptable();
            $role = 'comptable';
        } elseif ($user->getAdmin()) {
            $profilInfo = $user->getAdmin();
            $role = 'admin';
        }

        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'profilInfo' => $profilInfo,
            'role' => $role
        ]);
    }

    #[Route('/modifier', name: 'app_profil_modifier')]
    public function modifier(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer le changement de mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('app_profil');
        }

        return $this->render('profil/modifier.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/modifier-profil-specifique', name: 'app_profil_modifier_specifique')]
    public function modifierProfilSpecifique(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'entité spécifique au rôle
        $profilInfo = null;
        $role = '';
        
        if ($user->getEtudiant()) {
            $profilInfo = $user->getEtudiant();
            $role = 'etudiant';
        } elseif ($user->getEnseignant()) {
            $profilInfo = $user->getEnseignant();
            $role = 'enseignant';
        } elseif ($user->getComptable()) {
            $profilInfo = $user->getComptable();
            $role = 'comptable';
        } elseif ($user->getAdmin()) {
            $profilInfo = $user->getAdmin();
            $role = 'admin';
        }

        if (!$profilInfo) {
            $this->addFlash('error', 'Profil non trouvé.');
            return $this->redirectToRoute('app_profil');
        }

        // Créer un formulaire dynamique selon le rôle
        $formBuilder = $this->createFormBuilder($profilInfo);

        if ($role === 'etudiant') {
            $formBuilder->add('nom', null, ['label' => 'Nom'])
                      ->add('prenom', null, ['label' => 'Prénom'])
                      ->add('telephone', null, ['label' => 'Téléphone', 'required' => false])
                      ->add('adresse', null, ['label' => 'Adresse', 'required' => false]);
        } elseif ($role === 'enseignant') {
            $formBuilder->add('nom', null, ['label' => 'Nom'])
                      ->add('prenom', null, ['label' => 'Prénom'])
                      ->add('telephone', null, ['label' => 'Téléphone', 'required' => false])
                      ->add('adresse', null, ['label' => 'Adresse', 'required' => false])
                      ->add('specialite', null, ['label' => 'Spécialité', 'required' => false]);
        } elseif ($role === 'comptable') {
            $formBuilder->add('nom', null, ['label' => 'Nom'])
                      ->add('prenom', null, ['label' => 'Prénom'])
                      ->add('telephone', null, ['label' => 'Téléphone', 'required' => false])
                      ->add('poste', null, ['label' => 'Poste', 'required' => false]);
        } elseif ($role === 'admin') {
            $formBuilder->add('nom', null, ['label' => 'Nom'])
                      ->add('prenom', null, ['label' => 'Prénom'])
                      ->add('telephone', null, ['label' => 'Téléphone', 'required' => false])
                      ->add('poste', null, ['label' => 'Poste', 'required' => false]);
        }

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Vos informations personnelles ont été mises à jour avec succès.');

            return $this->redirectToRoute('app_profil');
        }

        return $this->render('profil/modifier_specifique.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'profilInfo' => $profilInfo,
            'role' => $role
        ]);
    }

    #[Route('/supprimer-compte', name: 'app_profil_supprimer_compte', methods: ['POST'])]
    public function supprimerCompte(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
            // Ne pas permettre la suppression du compte admin principal
            if ($user->getAdmin() && in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
                $this->addFlash('error', 'Impossible de supprimer le compte administrateur principal.');
                return $this->redirectToRoute('app_profil');
            }

            // Déconnecter l'utilisateur
            $this->container->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();

            // Supprimer l'utilisateur
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('error', 'Token de sécurité invalide.');
        return $this->redirectToRoute('app_profil');
    }

    #[Route('/statistiques', name: 'app_profil_statistiques')]
    public function statistiques(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $stats = [];
        $role = '';

        if ($user->getEtudiant()) {
            $role = 'etudiant';
            $etudiant = $user->getEtudiant();
            
            // Statistiques étudiant
            $stats = [
                'notes_count' => $this->entityManager->getRepository('App:Note')->count(['etudiant' => $etudiant]),
                'devoirs_count' => count($this->entityManager->getRepository('App:Devoir')->findBy(['filiere' => $etudiant->getFiliere()])),
                'evaluations_count' => count($this->entityManager->getRepository('App:Evaluation')->findBy(['filiere' => $etudiant->getFiliere()])),
                'cours_count' => count($this->entityManager->getRepository('App:Cours')->findBy(['filiere' => $etudiant->getFiliere()])),
            ];
        } elseif ($user->getEnseignant()) {
            $role = 'enseignant';
            $enseignant = $user->getEnseignant();
            
            // Statistiques enseignant
            $stats = [
                'matieres_count' => count($this->entityManager->getRepository('App:Matiere')->findBy(['enseignant' => $enseignant])),
                'cours_count' => count($this->entityManager->getRepository('App:Cours')->findBy(['enseignant' => $enseignant])),
                'evaluations_count' => count($this->entityManager->getRepository('App:Evaluation')->findBy(['enseignant' => $enseignant])),
                'notes_count' => $this->entityManager->createQueryBuilder()
                    ->select('COUNT(n.id)')
                    ->from('App:Note', 'n')
                    ->join('n.evaluation', 'e')
                    ->where('e.enseignant = :enseignant')
                    ->setParameter('enseignant', $enseignant)
                    ->getQuery()
                    ->getSingleScalarResult(),
            ];
        }

        return $this->render('profil/statistiques.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'role' => $role
        ]);
    }
}
