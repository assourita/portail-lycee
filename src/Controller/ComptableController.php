<?php
// src/Controller/ComptableController.php
namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\Facture;
use App\Repository\PaiementRepository;
use App\Repository\FactureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/comptable')]
#[IsGranted('ROLE_COMPTABLE')]
class ComptableController extends AbstractController
{
    #[Route('/dashboard', name: 'comptable_dashboard')]
    public function dashboard(PaiementRepository $paiementRepository, FactureRepository $factureRepository): Response
    {
        // Statistiques
        $montantTotalMois = $paiementRepository->getMontantTotalDuMois();
        $facturesEnAttente = $factureRepository->countByStatut('en_attente');
        $paiementsEnRetard = $factureRepository->findFacturesEnRetard();
        
        // Calcul du taux de paiement
        $totalFactures = $factureRepository->countByStatut('en_attente') + 
                        $factureRepository->countByStatut('paye');
        $tauxPaiement = $totalFactures > 0 ? 
                        round(($factureRepository->countByStatut('paye') / $totalFactures) * 100, 1) : 0;

        // Derniers paiements
        $derniersPaiements = $paiementRepository->findDerniersPaiements(5);

        return $this->render('comptable/dashboard.html.twig', [
            'montantTotalMois' => $montantTotalMois,
            'facturesEnAttente' => $facturesEnAttente,
            'tauxPaiement' => $tauxPaiement,
            'retardsPaiement' => count($paiementsEnRetard),
            'derniersPaiements' => $derniersPaiements,
        ]);
    }

    #[Route('/paiements', name: 'comptable_paiements')]
    public function paiements(PaiementRepository $paiementRepository): Response
    {
        $paiements = $paiementRepository->findAll();
        
        return $this->render('comptable/paiements.html.twig', [
            'paiements' => $paiements,
        ]);
    }

    #[Route('/factures', name: 'comptable_factures')]
    public function factures(FactureRepository $factureRepository): Response
    {
        $factures = $factureRepository->findAll();
        
        return $this->render('comptable/factures.html.twig', [
            'factures' => $factures,
        ]);
    }

    #[Route('/rapports', name: 'comptable_rapports')]
    public function rapports(): Response
    {
        return $this->render('comptable/rapports.html.twig');
    }

    #[Route('/etudiants', name: 'comptable_etudiants')]
    public function etudiants(): Response
    {
        return $this->render('comptable/etudiants.html.twig');
    }
}
