<?php

namespace App\Controller;

use App\Entity\Chapitre;
use App\Form\ChapitreType;
use App\Repository\ChapitreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChapitreController extends AbstractController
{
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $chapitre = new Chapitre();
        $form = $this->createForm(ChapitreType::class, $chapitre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer les pièces jointes
            foreach ($chapitre->getPiecesJointes() as $pieceJointe) {
                $file = $form->get('piecesJointes')->get($pieceJointe->getId())->get('fichier')->getData();
                if ($file) {
                    $fileName = md5(uniqid()).'.'.$file->guessExtension();
                    $file->move(
                        $this->getParameter('pieces_jointes_directory'),
                        $fileName
                    );
                    $pieceJointe->setFichier($fileName);
                }
            }

            $entityManager->persist($chapitre);
            $entityManager->flush();

            return $this->redirectToRoute('app_chapitre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('chapitre/new.html.twig', [
            'chapitre' => $chapitre,
            'form' => $form,
        ]);
    }

    public function edit(Request $request, Chapitre $chapitre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ChapitreType::class, $chapitre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer les pièces jointes
            foreach ($chapitre->getPiecesJointes() as $pieceJointe) {
                $file = $form->get('piecesJointes')->get($pieceJointe->getId())->get('fichier')->getData();
                if ($file) {
                    $fileName = md5(uniqid()).'.'.$file->guessExtension();
                    $file->move(
                        $this->getParameter('pieces_jointes_directory'),
                        $fileName
                    );
                    $pieceJointe->setFichier($fileName);
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_chapitre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('chapitre/edit.html.twig', [
            'chapitre' => $chapitre,
            'form' => $form,
        ]);
    }
} 