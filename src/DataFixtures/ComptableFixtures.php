<?php

namespace App\DataFixtures;

use App\Entity\Paiement;
use App\Entity\Facture;
use App\Entity\Etudiant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ComptableFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer quelques étudiants
        $etudiants = $manager->getRepository(Etudiant::class)->findAll();
        
        if (empty($etudiants)) {
            return;
        }

        // Créer des factures
        $typesFacture = ['Frais de scolarité', 'Frais d\'inscription', 'Frais de documentation', 'Frais de transport'];
        
        foreach ($etudiants as $index => $etudiant) {
            // Créer 2-3 factures par étudiant
            for ($i = 0; $i < rand(2, 3); $i++) {
                $facture = new Facture();
                $facture->setLibelle($typesFacture[array_rand($typesFacture)]);
                $facture->setMontant(rand(200, 1500));
                $facture->setStatut(rand(0, 1) ? 'en_attente' : 'paye');
                $facture->setDateCreation(new \DateTime('-' . rand(1, 30) . ' days'));
                $facture->setDateEcheance(new \DateTime('+' . rand(10, 60) . ' days'));
                $facture->setEtudiant($etudiant);
                
                $manager->persist($facture);
            }
        }

        // Créer des paiements
        $typesPaiement = ['Frais de scolarité', 'Frais d\'inscription', 'Frais de documentation'];
        
        foreach ($etudiants as $index => $etudiant) {
            // Créer 1-2 paiements par étudiant
            for ($i = 0; $i < rand(1, 2); $i++) {
                $paiement = new Paiement();
                $paiement->setType($typesPaiement[array_rand($typesPaiement)]);
                $paiement->setMontant(rand(150, 800));
                $paiement->setStatut('paye');
                $paiement->setDatePaiement(new \DateTime('-' . rand(1, 15) . ' days'));
                $paiement->setEtudiant($etudiant);
                
                $manager->persist($paiement);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            EtudiantFixtures::class,
        ];
    }
}
