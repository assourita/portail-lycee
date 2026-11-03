<?php

namespace App\Controller;

use App\Entity\Absence;
use App\Entity\Bulletin;
use App\Entity\Cours;
use App\Entity\Devoir;
use App\Entity\Evaluation;
use App\Entity\Matiere;
use App\Entity\Note;
use App\Entity\Soumission;
use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class ProfilController extends AbstractController
{
    /**
     * Champs modifiables par l'utilisateur lui-même, selon son profil.
     */
    private const CHAMPS_PAR_ROLE = [
        'etudiant' => ['nom' => 'Nom', 'prenom' => 'Prénom', 'telephone' => 'Téléphone', 'adresse' => 'Adresse'],
        'enseignant' => ['nom' => 'Nom', 'prenom' => 'Prénom', 'telephone' => 'Téléphone', 'adresse' => 'Adresse', 'specialite' => 'Spécialité'],
        'comptable' => ['nom' => 'Nom', 'prenom' => 'Prénom', 'telephone' => 'Téléphone', 'poste' => 'Poste'],
        'admin' => ['nom' => 'Nom', 'prenom' => 'Prénom', 'poste' => 'Poste'],
        'parent' => ['nom' => 'Nom', 'prenom' => 'Prénom', 'telephone' => 'Téléphone', 'telephoneSecondaire' => 'Téléphone secondaire', 'adresse' => 'Adresse', 'profession' => 'Profession'],
    ];

    private const CHAMPS_OBLIGATOIRES = ['nom', 'prenom'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/', name: 'app_profil', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getAppUser();
        [$role, $profilInfo] = $this->getProfil($user);

        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'profilInfo' => $profilInfo,
            'role' => $role,
        ]);
    }

    #[Route('/modifier', name: 'app_profil_modifier', methods: ['GET', 'POST'])]
    public function modifier(Request $request): Response
    {
        $user = $this->getAppUser();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
                $user->setPasswordChangedAt(new \DateTime());
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('app_profil');
        }

        return $this->render('profil/modifier.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/modifier-profil-specifique', name: 'app_profil_modifier_specifique', methods: ['GET', 'POST'])]
    public function modifierProfilSpecifique(Request $request): Response
    {
        $user = $this->getAppUser();
        [$role, $profilInfo] = $this->getProfil($user);

        if (!$profilInfo) {
            $this->addFlash('error', 'Aucun profil personnel n\'est associé à ce compte.');

            return $this->redirectToRoute('app_profil');
        }

        $formBuilder = $this->createFormBuilder($profilInfo);
        foreach (self::CHAMPS_PAR_ROLE[$role] as $champ => $label) {
            $formBuilder->add($champ, TextType::class, [
                'label' => $label,
                'required' => in_array($champ, self::CHAMPS_OBLIGATOIRES, true),
                'attr' => ['class' => 'form-control'],
            ]);
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
            'role' => $role,
        ]);
    }

    #[Route('/supprimer-compte', name: 'app_profil_supprimer_compte', methods: ['POST'])]
    public function supprimerCompte(Request $request): Response
    {
        $user = $this->getAppUser();

        if (!$this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');

            return $this->redirectToRoute('app_profil');
        }

        if ($this->isGranted('ROLE_ADMIN') || $user->getEtudiant() || $user->getEnseignant()) {
            $this->addFlash('error', 'Ce type de compte ne peut être supprimé que par l\'administration.');

            return $this->redirectToRoute('app_profil');
        }

        $this->container->get('security.token_storage')->setToken(null);
        $request->getSession()->invalidate();

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_login');
    }

    #[Route('/statistiques', name: 'app_profil_statistiques', methods: ['GET'])]
    public function statistiques(): Response
    {
        $user = $this->getAppUser();
        [$role] = $this->getProfil($user);

        $stats = match ($role) {
            'etudiant' => $this->statistiquesEtudiant($user),
            'enseignant' => $this->statistiquesEnseignant($user),
            default => [],
        };

        return $this->render('profil/statistiques.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'role' => $role,
        ]);
    }

    private function statistiquesEtudiant(User $user): array
    {
        $etudiant = $user->getEtudiant();
        $filiere = $etudiant->getFiliere();

        $moyenne = $this->entityManager->createQueryBuilder()
            ->select('AVG(n.valeur)')
            ->from(Note::class, 'n')
            ->where('n.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'Notes reçues' => $this->entityManager->getRepository(Note::class)->count(['etudiant' => $etudiant]),
            'Moyenne générale' => $moyenne !== null ? round((float) $moyenne, 2).'/20' : '-',
            'Cours disponibles' => $filiere ? $this->countParFiliere(Cours::class, $filiere) : 0,
            'Devoirs publiés' => $filiere ? $this->countParFiliere(Devoir::class, $filiere) : 0,
            'Devoirs rendus' => $this->entityManager->getRepository(Soumission::class)->count(['etudiant' => $etudiant]),
            'Évaluations' => $filiere ? $this->countParFiliere(Evaluation::class, $filiere) : 0,
            'Absences' => $this->entityManager->getRepository(Absence::class)->count(['etudiant' => $etudiant]),
            'Bulletins' => $this->entityManager->getRepository(Bulletin::class)->count(['etudiant' => $etudiant]),
        ];
    }

    private function statistiquesEnseignant(User $user): array
    {
        $enseignant = $user->getEnseignant();

        return [
            'Matières enseignées' => $this->entityManager->getRepository(Matiere::class)->count(['enseignant' => $enseignant]),
            'Cours publiés' => $this->entityManager->getRepository(Cours::class)->count(['enseignant' => $enseignant]),
            'Devoirs créés' => $this->entityManager->getRepository(Devoir::class)->count(['enseignant' => $enseignant]),
            'Évaluations créées' => $this->entityManager->getRepository(Evaluation::class)->count(['enseignant' => $enseignant]),
            'Notes saisies' => $this->entityManager->getRepository(Note::class)->count(['enseignant' => $enseignant]),
            'Absences enregistrées' => $this->entityManager->getRepository(Absence::class)->count(['enseignant' => $enseignant]),
        ];
    }

    private function countParFiliere(string $classe, $filiere): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(x.id)')
            ->from($classe, 'x')
            ->join('x.matiere', 'm')
            ->where('m.filiere = :filiere')
            ->setParameter('filiere', $filiere)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{0: string, 1: object|null}
     */
    private function getProfil(User $user): array
    {
        return match (true) {
            $user->getEtudiant() !== null => ['etudiant', $user->getEtudiant()],
            $user->getEnseignant() !== null => ['enseignant', $user->getEnseignant()],
            $user->getComptable() !== null => ['comptable', $user->getComptable()],
            $user->getAdmin() !== null => ['admin', $user->getAdmin()],
            $user->getParentEleve() !== null => ['parent', $user->getParentEleve()],
            default => ['', null],
        };
    }

    private function getAppUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
