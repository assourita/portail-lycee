<?php

namespace App\Controller;

use App\Entity\Chapitre;
use App\Entity\Cours;
use App\Entity\Enseignant;
use App\Entity\PieceJointe;
use App\Form\ChapitreType;
use App\Form\CoursType;
use App\Repository\CoursRepository;
use App\Service\PeriodeAcademiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/enseignant/cours')]
#[IsGranted('ROLE_ENSEIGNANT')]
class EnseignantCoursController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PeriodeAcademiqueService $periodeService,
        private SluggerInterface $slugger,
    ) {}

    #[Route('/', name: 'enseignant_cours_index', methods: ['GET'])]
    public function index(CoursRepository $coursRepository): Response
    {
        return $this->render('enseignant/cours/index.html.twig', [
            'cours' => $coursRepository->findBy(['enseignant' => $this->getEnseignant()], ['dateAjout' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'enseignant_cours_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $enseignant = $this->getEnseignant();

        $cours = new Cours();
        $cours->setEnseignant($enseignant);
        $cours->setPeriodeAcademique($this->periodeService->getPeriodeActive());

        $form = $this->createForm(CoursType::class, $cours, ['matieres' => $enseignant->getMatieres()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($cours);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le cours a été créé. Ajoutez-lui maintenant des chapitres.');

            return $this->redirectToRoute('enseignant_cours_show', ['id' => $cours->getId()]);
        }

        return $this->render('enseignant/cours/new.html.twig', [
            'cours' => $cours,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'enseignant_cours_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Cours $cours): Response
    {
        $this->verifierCours($cours);

        return $this->render('enseignant/cours/show.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/{id}/edit', name: 'enseignant_cours_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Cours $cours): Response
    {
        $this->verifierCours($cours);

        $form = $this->createForm(CoursType::class, $cours, ['matieres' => $this->getEnseignant()->getMatieres()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Le cours a été modifié.');

            return $this->redirectToRoute('enseignant_cours_show', ['id' => $cours->getId()]);
        }

        return $this->render('enseignant/cours/edit.html.twig', [
            'cours' => $cours,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'enseignant_cours_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Cours $cours): Response
    {
        $this->verifierCours($cours);

        if ($this->isCsrfTokenValid('delete-cours'.$cours->getId(), $request->request->get('_token'))) {
            foreach ($cours->getChapitres() as $chapitre) {
                foreach ($chapitre->getPiecesJointes() as $pieceJointe) {
                    $this->supprimerFichier($pieceJointe);
                }
            }
            $this->entityManager->remove($cours);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le cours a été supprimé.');
        }

        return $this->redirectToRoute('enseignant_cours_index');
    }

    #[Route('/chapitre/{id}', name: 'enseignant_chapitre_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showChapitre(Chapitre $chapitre): Response
    {
        $this->verifierCours($chapitre->getCours());

        return $this->render('enseignant/cours/chapitre/show.html.twig', [
            'chapitre' => $chapitre,
            'cours' => $chapitre->getCours(),
        ]);
    }

    #[Route('/piece-jointe/{id}/download', name: 'enseignant_piece_jointe_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadPieceJointe(PieceJointe $pieceJointe): BinaryFileResponse
    {
        $this->verifierCours($pieceJointe->getChapitre()?->getCours());

        $filePath = $this->getParameter('pieces_jointes_directory').'/'.$pieceJointe->getFichier();
        if (!$pieceJointe->getFichier() || !is_file($filePath)) {
            throw $this->createNotFoundException('Le fichier n\'existe pas.');
        }

        $nom = $pieceJointe->getNom() ?: 'document';
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if ($extension && !str_ends_with(strtolower($nom), '.'.strtolower($extension))) {
            $nom .= '.'.$extension;
        }

        return $this->file($filePath, $nom, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    #[Route('/{id}/chapitre/new', name: 'enseignant_chapitre_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function newChapitre(Request $request, Cours $cours): Response
    {
        $this->verifierCours($cours);

        $chapitre = new Chapitre();
        $chapitre->setCours($cours);
        $chapitre->setOrdre($cours->getChapitres()->count() + 1);

        $form = $this->createForm(ChapitreType::class, $chapitre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('piecesJointes') as $pieceJointeForm) {
                $pieceJointe = $pieceJointeForm->getData();
                $fichier = $pieceJointeForm->get('fichier')->getData();

                if (!$fichier || !$this->enregistrerFichier($pieceJointe, $fichier)) {
                    $chapitre->getPiecesJointes()->removeElement($pieceJointe);
                    continue;
                }
                $pieceJointe->setChapitre($chapitre);
            }

            $chapitre->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($chapitre);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le chapitre a été créé avec succès.');

            return $this->redirectToRoute('enseignant_cours_show', ['id' => $cours->getId()]);
        }

        return $this->render('enseignant/cours/chapitre/new.html.twig', [
            'chapitre' => $chapitre,
            'cours' => $cours,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/chapitre/{id}/edit', name: 'enseignant_chapitre_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editChapitre(Request $request, Chapitre $chapitre): Response
    {
        $this->verifierCours($chapitre->getCours());

        $form = $this->createFormBuilder($chapitre)
            ->add('titre', TextType::class, ['label' => 'Titre du chapitre', 'attr' => ['class' => 'form-control']])
            ->add('ordre', IntegerType::class, ['label' => 'Ordre', 'attr' => ['class' => 'form-control', 'min' => 1]])
            ->add('contenu', TextareaType::class, ['label' => 'Contenu', 'attr' => ['class' => 'form-control', 'rows' => 12]])
            ->add('nouveauxFichiers', FileType::class, [
                'label' => 'Ajouter des pièces jointes',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $aSupprimer = $request->request->all('supprimer_pieces');
            foreach ($chapitre->getPiecesJointes()->toArray() as $pieceJointe) {
                if (in_array((string) $pieceJointe->getId(), $aSupprimer, true)) {
                    $this->supprimerFichier($pieceJointe);
                    $chapitre->getPiecesJointes()->removeElement($pieceJointe);
                    $this->entityManager->remove($pieceJointe);
                }
            }

            foreach ($form->get('nouveauxFichiers')->getData() ?? [] as $fichier) {
                $pieceJointe = new PieceJointe();
                $pieceJointe->setNom(pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME));
                if ($this->enregistrerFichier($pieceJointe, $fichier)) {
                    $pieceJointe->setChapitre($chapitre);
                    $chapitre->getPiecesJointes()->add($pieceJointe);
                    $this->entityManager->persist($pieceJointe);
                }
            }

            $chapitre->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->addFlash('success', 'Le chapitre a été modifié.');

            return $this->redirectToRoute('enseignant_chapitre_show', ['id' => $chapitre->getId()]);
        }

        return $this->render('enseignant/cours/chapitre/edit.html.twig', [
            'chapitre' => $chapitre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/chapitre/{id}/delete', name: 'enseignant_chapitre_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteChapitre(Request $request, Chapitre $chapitre): Response
    {
        $cours = $chapitre->getCours();
        $this->verifierCours($cours);

        if ($this->isCsrfTokenValid('delete-chapitre'.$chapitre->getId(), $request->request->get('_token'))) {
            foreach ($chapitre->getPiecesJointes() as $pieceJointe) {
                $this->supprimerFichier($pieceJointe);
            }
            $this->entityManager->remove($chapitre);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le chapitre a été supprimé.');
        }

        return $this->redirectToRoute('enseignant_cours_show', ['id' => $cours->getId()]);
    }

    private function enregistrerFichier(PieceJointe $pieceJointe, UploadedFile $fichier): bool
    {
        $extension = $fichier->guessExtension() ?: pathinfo($fichier->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'bin';
        $base = $this->slugger->slug(pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME))->lower()->truncate(60);
        $nom = sprintf('%s-%s.%s', $base ?: 'fichier', uniqid(), $extension);

        try {
            $fichier->move($this->getParameter('pieces_jointes_directory'), $nom);
        } catch (\Exception $e) {
            $this->addFlash('error', sprintf('Erreur lors de l\'envoi de « %s » : %s', $fichier->getClientOriginalName(), $e->getMessage()));

            return false;
        }

        $pieceJointe->setFichier($nom);

        return true;
    }

    private function supprimerFichier(PieceJointe $pieceJointe): void
    {
        $chemin = $this->getParameter('pieces_jointes_directory').'/'.$pieceJointe->getFichier();
        if ($pieceJointe->getFichier() && is_file($chemin)) {
            @unlink($chemin);
        }
    }

    private function verifierCours(?Cours $cours): void
    {
        if (!$cours || $cours->getEnseignant() !== $this->getEnseignant()) {
            throw $this->createAccessDeniedException('Ce cours ne vous appartient pas.');
        }
    }

    private function getEnseignant(): Enseignant
    {
        $enseignant = $this->getUser()?->getEnseignant();
        if (!$enseignant) {
            throw $this->createAccessDeniedException('Aucun profil enseignant n\'est associé à ce compte.');
        }

        return $enseignant;
    }
}
