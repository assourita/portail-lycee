<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\Chapitre;
use App\Entity\PieceJointe;
use App\Form\CoursType;
use App\Form\ChapitreType;
use App\Repository\CoursRepository;
use App\Repository\EnseignantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/enseignant/cours')]
class EnseignantCoursController extends AbstractController
{
    #[Route('/', name: 'enseignant_cours_index', methods: ['GET'])]
    public function index(CoursRepository $coursRepository): Response
    {
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        $cours = $coursRepository->findBy(['enseignant' => $enseignant]);

        return $this->render('enseignant/cours/index.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/new', name: 'enseignant_cours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cours = new Cours();
        $cours->setDateAjout(new \DateTime());
        
        $user = $this->getUser();
        $enseignant = $user->getEnseignant();
        $cours->setEnseignant($enseignant);

        $form = $this->createForm(CoursType::class, $cours, [
            'matieres' => $enseignant->getMatieres()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cours);
            $entityManager->flush();

            return $this->redirectToRoute('enseignant_cours_index');
        }

        return $this->render('enseignant/cours/new.html.twig', [
            'cours' => $cours,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'enseignant_cours_show', methods: ['GET'])]
    public function show(Cours $cours): Response
    {
        return $this->render('enseignant/cours/show.html.twig', [
            'cours' => $cours,
        ]);
    }

    #[Route('/{id}/edit', name: 'enseignant_cours_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cours $cours, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CoursType::class, $cours);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('enseignant_cours_index');
        }

        return $this->render('enseignant/cours/edit.html.twig', [
            'cours' => $cours,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/chapitre/{id}', name: 'enseignant_chapitre_show', methods: ['GET'])]
    public function showChapitre(Chapitre $chapitre): Response
    {
        return $this->render('enseignant/cours/chapitre/show.html.twig', [
            'chapitre' => $chapitre,
            'cours' => $chapitre->getCours(),
        ]);
    }

    #[Route('/piece-jointe/{id}/download', name: 'enseignant_piece_jointe_download', methods: ['GET'])]
    public function downloadPieceJointe(PieceJointe $pieceJointe): Response
    {
        $uploadDirectory = $this->getParameter('kernel.project_dir').'/public/uploads/pieces_jointes';
        $filePath = $uploadDirectory.'/'.$pieceJointe->getFichier();
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Le fichier n\'existe pas.');
        }
        
        return $this->file($filePath, $pieceJointe->getNom());
    }

    #[Route('/{id}/chapitre/new', name: 'enseignant_chapitre_new', methods: ['GET', 'POST'])]
    public function newChapitre(Request $request, Cours $cours, EntityManagerInterface $entityManager): Response
    {
        $chapitre = new Chapitre();
        $chapitre->setCours($cours);

        $form = $this->createForm(ChapitreType::class, $chapitre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload des pièces jointes
            $piecesJointesForm = $form->get('piecesJointes');
            
            foreach ($piecesJointesForm as $pieceJointeForm) {
                $fichier = $pieceJointeForm->get('fichier')->getData();
                
                if ($fichier) {
                    // Générer un nom unique pour le fichier
                    $originalFilename = pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
                    // Nettoyer le nom de fichier (supprimer les caractères spéciaux)
                    $safeFilename = preg_replace('/[^A-Za-z0-9_-]/', '', $originalFilename);
                    $safeFilename = strtolower($safeFilename);
                    
                    // Si le nom est vide après nettoyage, utiliser un nom par défaut
                    if (empty($safeFilename)) {
                        $safeFilename = 'fichier';
                    }
                    
                    // S'assurer qu'on a une extension
                    $extension = $fichier->guessExtension();
                    if (!$extension) {
                        $extension = pathinfo($fichier->getClientOriginalName(), PATHINFO_EXTENSION);
                    }
                    
                    // Si toujours pas d'extension, utiliser 'bin' par défaut
                    if (empty($extension)) {
                        $extension = 'bin';
                    }
                    
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;
                    
                    // Déplacer le fichier vers le répertoire d'upload
                    $uploadDirectory = $this->getParameter('kernel.project_dir').'/public/uploads/pieces_jointes';
                    if (!is_dir($uploadDirectory)) {
                        mkdir($uploadDirectory, 0755, true);
                    }
                    
                    // Vérifier que le répertoire est accessible en écriture
                    if (!is_writable($uploadDirectory)) {
                        throw new \Exception('Le répertoire d\'upload n\'est pas accessible en écriture: ' . $uploadDirectory);
                    }
                    
                    try {
                        $fichier->move($uploadDirectory, $newFilename);
                        
                        // Définir le nom du fichier dans l'entité
                        $pieceJointe = $pieceJointeForm->getData();
                        $pieceJointe->setFichier($newFilename);
                        $pieceJointe->setChapitre($chapitre); // Définir la relation avec le chapitre
                        
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload du fichier: ' . $e->getMessage());
                        continue; // Passer au fichier suivant
                    }
                }
            }
            
            $chapitre->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($chapitre);
            $entityManager->flush();

            $this->addFlash('success', 'Le chapitre a été créé avec succès.');
            return $this->redirectToRoute('enseignant_cours_show', ['id' => $cours->getId()]);
        }

        return $this->render('enseignant/cours/chapitre/new.html.twig', [
            'chapitre' => $chapitre,
            'cours' => $cours,
            'form' => $form->createView(),
        ]);
    }
} 