<?php

namespace App\Controller;

use App\Entity\Absence;
use App\Entity\Devoir;
use App\Entity\Etudiant;
use App\Entity\Soumission;
use App\Entity\User;
use App\Service\FichierUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Téléchargement des documents privés, après vérification des droits de l'utilisateur.
 */
#[Route('/fichiers')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class FichierController extends AbstractController
{
    public const DOSSIER_JUSTIFICATIFS = 'justificatifs';
    public const DOSSIER_SOUMISSIONS = 'soumissions';
    public const DOSSIER_DEVOIRS = 'devoirs';

    public function __construct(private FichierUploader $uploader) {}

    #[Route('/absence/{id}/justificatif', name: 'fichier_absence_justificatif', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function justificatif(Absence $absence): BinaryFileResponse
    {
        $user = $this->getAppUser();
        $autorise = $this->isGranted('ROLE_ADMIN')
            || $this->estConcerneParEtudiant($user, $absence->getEtudiant())
            || ($user->getEnseignant() && $absence->getEnseignant() === $user->getEnseignant());

        if (!$autorise) {
            throw $this->createAccessDeniedException();
        }

        return $this->envoyer(self::DOSSIER_JUSTIFICATIFS, $absence->getDocumentJustificatif(), 'justificatif-absence-'.$absence->getId());
    }

    #[Route('/soumission/{id}/{fichier}', name: 'fichier_soumission', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function soumission(Soumission $soumission, string $fichier): BinaryFileResponse
    {
        $user = $this->getAppUser();
        $autorise = $this->isGranted('ROLE_ADMIN')
            || $this->estConcerneParEtudiant($user, $soumission->getEtudiant())
            || ($user->getEnseignant() && $soumission->getDevoir()?->getEnseignant() === $user->getEnseignant());

        $infos = $soumission->getFichier($fichier);
        if (!$autorise || !$infos) {
            throw $this->createAccessDeniedException();
        }

        return $this->envoyer(self::DOSSIER_SOUMISSIONS, $infos['fichier'], $infos['nom']);
    }

    #[Route('/devoir/{id}/{fichier}', name: 'fichier_devoir', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function devoir(Devoir $devoir, string $fichier): BinaryFileResponse
    {
        $user = $this->getAppUser();
        $filiere = $devoir->getMatiere()?->getFiliere();
        $autorise = $this->isGranted('ROLE_ADMIN')
            || ($user->getEnseignant() && $devoir->getEnseignant() === $user->getEnseignant())
            || ($user->getEtudiant() && $filiere && $user->getEtudiant()->getFiliere() === $filiere && $devoir->getStatut() !== 'brouillon');

        $infos = $devoir->getDocument($fichier);
        if (!$autorise || !$infos) {
            throw $this->createAccessDeniedException();
        }

        return $this->envoyer(self::DOSSIER_DEVOIRS, $infos['fichier'], $infos['nom']);
    }

    private function envoyer(string $dossier, ?string $nomStockage, string $nomTelechargement): BinaryFileResponse
    {
        $chemin = $nomStockage ? $this->uploader->getChemin($dossier, $nomStockage) : null;
        if (!$chemin) {
            throw $this->createNotFoundException('Le fichier demandé est introuvable.');
        }

        $extension = pathinfo($chemin, PATHINFO_EXTENSION);
        if ($extension && !str_ends_with(strtolower($nomTelechargement), '.'.strtolower($extension))) {
            $nomTelechargement .= '.'.$extension;
        }

        return $this->file($chemin, $nomTelechargement, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    private function estConcerneParEtudiant(User $user, ?Etudiant $etudiant): bool
    {
        if (!$etudiant) {
            return false;
        }

        return $user->getEtudiant() === $etudiant
            || ($user->getParentEleve() && $user->getParentEleve()->getEnfants()->contains($etudiant));
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
