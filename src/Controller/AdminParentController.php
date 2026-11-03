<?php

namespace App\Controller;

use App\Entity\ParentEleve;
use App\Entity\User;
use App\Repository\ParentEleveRepository;
use App\Repository\EtudiantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/parents')]
#[IsGranted('ROLE_ADMIN')]
class AdminParentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParentEleveRepository $parentRepository,
        private EtudiantRepository $etudiantRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/', name: 'admin_parents_index', methods: ['GET'])]
    public function index(): Response
    {
        $parents = $this->parentRepository->findAll();

        return $this->render('admin/parent/index.html.twig', [
            'parents' => $parents,
        ]);
    }

    #[Route('/new', name: 'admin_parent_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $email = $request->request->get('email');
            $telephone = $request->request->get('telephone');
            $lienParente = $request->request->get('lien_parente');
            $enfantsIds = $request->request->all('enfants') ?? [];
            $creerCompte = $request->request->get('creer_compte') === '1';

            if ($nom && $prenom && $email && $lienParente) {
                // Vérifier si l'email existe déjà
                $existingParent = $this->parentRepository->findByEmail($email);
                if ($existingParent) {
                    $this->addFlash('error', 'Un parent avec cet email existe déjà.');
                    return $this->redirectToRoute('admin_parent_new');
                }

                $parent = new ParentEleve();
                $parent->setNom($nom);
                $parent->setPrenom($prenom);
                $parent->setEmail($email);
                $parent->setTelephone($telephone);
                $parent->setLienParente($lienParente);
                $parent->setProfession($request->request->get('profession') ?: null);
                $parent->setAdresse($request->request->get('adresse') ?: null);

                // Lier les enfants
                foreach ($enfantsIds as $enfantId) {
                    $enfant = $this->etudiantRepository->find($enfantId);
                    if ($enfant) {
                        $parent->addEnfant($enfant);
                    }
                }

                $this->entityManager->persist($parent);

                // Créer un compte utilisateur si demandé
                if ($creerCompte) {
                    $user = new User();
                    $user->setUsername($email);
                    $user->setEmail($email);
                    $user->setRoles(['ROLE_PARENT']);
                    
                    // Générer un mot de passe temporaire
                    $tempPassword = 'Parent-' . bin2hex(random_bytes(4));
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $tempPassword);
                    $user->setPassword($hashedPassword);
                    $user->setIsActivate(true);

                    $parent->setUser($user);
                    $this->entityManager->persist($user);

                    $this->addFlash('info', sprintf('Compte créé avec le mot de passe temporaire : %s', $tempPassword));
                }

                $this->entityManager->flush();

                $this->addFlash('success', 'Parent créé avec succès !');
                return $this->redirectToRoute('admin_parents_index');
            }

            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
        }

        $etudiants = $this->etudiantRepository->findAll();

        return $this->render('admin/parent/new.html.twig', [
            'etudiants' => $etudiants,
        ]);
    }

    #[Route('/{id}', name: 'admin_parent_show', methods: ['GET'])]
    public function show(ParentEleve $parent): Response
    {
        return $this->render('admin/parent/show.html.twig', [
            'parent' => $parent,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_parent_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ParentEleve $parent): Response
    {
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $email = $request->request->get('email');
            $telephone = $request->request->get('telephone');
            $lienParente = $request->request->get('lien_parente');
            $enfantsIds = $request->request->all('enfants') ?? [];

            if ($nom && $prenom && $email && $lienParente) {
                $parent->setNom($nom);
                $parent->setPrenom($prenom);
                $parent->setEmail($email);
                $parent->setTelephone($telephone);
                $parent->setLienParente($lienParente);
                $parent->setProfession($request->request->get('profession') ?: null);
                $parent->setAdresse($request->request->get('adresse') ?: null);
                $parent->setDateModification(new \DateTime());

                // Mettre à jour les enfants
                $parent->getEnfants()->clear();
                foreach ($enfantsIds as $enfantId) {
                    $enfant = $this->etudiantRepository->find($enfantId);
                    if ($enfant) {
                        $parent->addEnfant($enfant);
                    }
                }

                $this->entityManager->flush();

                $this->addFlash('success', 'Parent modifié avec succès !');
                return $this->redirectToRoute('admin_parent_show', ['id' => $parent->getId()]);
            }

            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
        }

        $etudiants = $this->etudiantRepository->findAll();

        return $this->render('admin/parent/edit.html.twig', [
            'parent' => $parent,
            'etudiants' => $etudiants,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_parent_delete', methods: ['POST'])]
    public function delete(Request $request, ParentEleve $parent): Response
    {
        if ($this->isCsrfTokenValid('delete-parent', $request->request->get('_token'))) {
            // Supprimer aussi le compte utilisateur associé
            if ($parent->getUser()) {
                $this->entityManager->remove($parent->getUser());
            }

            $this->entityManager->remove($parent);
            $this->entityManager->flush();

            $this->addFlash('success', 'Parent supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_parents_index');
    }

    #[Route('/{id}/creer-compte', name: 'admin_parent_creer_compte', methods: ['POST'])]
    public function creerCompte(Request $request, ParentEleve $parent): Response
    {
        if ($this->isCsrfTokenValid('creer-compte'.$parent->getId(), $request->request->get('_token'))) {
            if ($parent->getUser()) {
                $this->addFlash('error', 'Ce parent a déjà un compte utilisateur.');
            } else {
                $user = new User();
                $user->setUsername($parent->getEmail());
                $user->setEmail($parent->getEmail());
                $user->setRoles(['ROLE_PARENT']);
                
                // Générer un mot de passe temporaire
                $tempPassword = 'Parent-' . bin2hex(random_bytes(4));
                $hashedPassword = $this->passwordHasher->hashPassword($user, $tempPassword);
                $user->setPassword($hashedPassword);
                $user->setIsActivate(true);

                $parent->setUser($user);
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->addFlash('success', sprintf('Compte créé avec le mot de passe temporaire : %s', $tempPassword));
            }
        }

        return $this->redirectToRoute('admin_parent_show', ['id' => $parent->getId()]);
    }
}

