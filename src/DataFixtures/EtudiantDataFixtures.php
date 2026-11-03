<?php

namespace App\DataFixtures;

use App\Entity\Etudiant;
use App\Entity\Cours;
use App\Entity\Chapitre;
use App\Entity\Evaluation;
use App\Entity\Note;
use App\Entity\Absence;
use App\Entity\Matiere;
use App\Entity\Filiere;
use App\Entity\PeriodeAcademique;
use App\Entity\ParcoursScolaire;
use App\Entity\Enseignant;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class EtudiantDataFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Créer une période académique
        $periode = new PeriodeAcademique();
        $periode->setAnneeScolaire('2024-2025');
        $periode->setPeriode('1er Trimestre');
        $periode->setDateDebut(new \DateTime('2024-09-01'));
        $periode->setDateFin(new \DateTime('2024-12-20'));
        $manager->persist($periode);

        // Créer une filière
        $filiere = new Filiere();
        $filiere->setNom('Terminale Sciences Exactes');
        $filiere->setCode('TSE');
        $filiere->setDescription('Sciences Mathématiques et Physiques');
        $manager->persist($filiere);

        // Créer des matières
        $matieres = [];
        $matiereData = [
            ['Mathématiques', 'MATH', 'Cours de mathématiques avancées'],
            ['Physique-Chimie', 'PC', 'Physique et chimie expérimentale'],
            ['Sciences Naturelles', 'SVT', 'Biologie et géologie'],
            ['Français', 'FR', 'Littérature et grammaire française'],
            ['Anglais', 'ENG', 'Langue et littérature anglaise'],
            ['Histoire-Géographie', 'HG', 'Histoire et géographie'],
        ];

        foreach ($matiereData as [$nom, $code, $description]) {
            $matiere = new Matiere();
            $matiere->setNom($nom);
            $matiere->setCode($code);
            $matiere->setDescription($description);
            $matiere->setFiliere($filiere);
            $matieres[$code] = $matiere;
            $manager->persist($matiere);
        }

        // Créer un enseignant
        $enseignantUser = new User();
        $enseignantUser->setEmail('dupont.jean@lycee.fr');
        $enseignantUser->setRoles(['ROLE_ENSEIGNANT']);
        $enseignantUser->setPassword($this->passwordHasher->hashPassword($enseignantUser, 'enseignant123'));
        $manager->persist($enseignantUser);

        $enseignant = new Enseignant();
        $enseignant->setNom('Dupont');
        $enseignant->setPrenom('Jean');
        $enseignant->setEmail('dupont.jean@lycee.fr');
        $enseignant->setUser($enseignantUser);
        $enseignant->setSpecialite('Mathématiques et Physique');
        $manager->persist($enseignant);

        // Associer les matières à l'enseignant
        foreach ($matieres as $matiere) {
            $enseignant->addMatiere($matiere);
        }

        // Créer un étudiant
        $etudiantUser = new User();
        $etudiantUser->setEmail('ogona.assy@lycee.fr');
        $etudiantUser->setRoles(['ROLE_ETUDIANT']);
        $etudiantUser->setPassword($this->passwordHasher->hashPassword($etudiantUser, 'etudiant123'));
        $manager->persist($etudiantUser);

        $etudiant = new Etudiant();
        $etudiant->setNom('Assy');
        $etudiant->setPrenom('Ogona');
        $etudiant->setEmail('ogona.assy@lycee.fr');
        $etudiant->setDateNaissance(new \DateTime('2006-05-15'));
        $etudiant->setUser($etudiantUser);
        $etudiant->setFiliere($filiere);
        $manager->persist($etudiant);

        // Créer le parcours scolaire
        $parcours = new ParcoursScolaire();
        $parcours->setEtudiant($etudiant);
        $parcours->setFiliere($filiere);
        $parcours->setAnnee('2024-2025');
        $parcours->setClasse('Terminale');
        $parcours->setDecision('en_cours');
        $parcours->setDateInscription(new \DateTime('2024-09-01'));
        $manager->persist($parcours);

        // Créer des cours
        $coursData = [
            ['Introduction aux fonctions', 'MATH', 'Étude des fonctions polynomiales et rationnelles'],
            ['Les lois de Newton', 'PC', 'Principes fondamentaux de la mécanique classique'],
            ['La cellule vivante', 'SVT', 'Structure et fonction des cellules'],
            ['Le romantisme français', 'FR', 'Analyse des œuvres romantiques'],
            ['Present perfect tense', 'ENG', 'Usage et formation du present perfect'],
        ];

        foreach ($coursData as [$titre, $matiereCode, $description]) {
            $cours = new Cours();
            $cours->setTitre($titre);
            $cours->setContenu($description . '. Ce cours couvre les concepts fondamentaux et inclut des exercices pratiques.');
            $cours->setDateAjout(new \DateTime('2024-09-' . rand(10, 30)));
            $cours->setMatiere($matieres[$matiereCode]);
            $cours->setEnseignant($enseignant);
            $cours->setPeriodeAcademique($periode);
            $manager->persist($cours);

            // Créer des chapitres pour chaque cours
            for ($i = 1; $i <= 3; $i++) {
                $chapitre = new Chapitre();
                $chapitre->setTitre("Chapitre $i : " . substr($titre, 0, 20) . "...");
                $chapitre->setContenu("Contenu détaillé du chapitre $i avec exemples et exercices.");
                $chapitre->setCours($cours);
                $chapitre->setOrdre($i);
                $manager->persist($chapitre);
            }
        }

        // Créer des évaluations
        $evaluationData = [
            ['Devoir surveillé n°1', 'MATH', 20, new \DateTime('2024-10-15')],
            ['Contrôle de physique', 'PC', 20, new \DateTime('2024-10-20')],
            ['Interrogation SVT', 'SVT', 10, new \DateTime('2024-10-25')],
            ['Rédaction française', 'FR', 15, new \DateTime('2024-11-05')],
            ['Test d\'anglais', 'ENG', 20, new \DateTime('2024-11-10')],
        ];

        foreach ($evaluationData as [$titre, $matiereCode, $noteMax, $date]) {
            $evaluation = new Evaluation();
            $evaluation->setTitre($titre);
            $evaluation->setMatiere($matieres[$matiereCode]);
            $evaluation->setNoteMax($noteMax);
            $evaluation->setDateEvaluation($date);
            $evaluation->setPeriodeAcademique($periode);
            $evaluation->setEnseignant($enseignant);
            $manager->persist($evaluation);

            // Créer une note pour l'étudiant
            $note = new Note();
            $note->setEtudiant($etudiant);
            $note->setMatiere($matieres[$matiereCode]);
            $note->setEvaluation($evaluation);
            $note->setValeur(rand(12, 18)); // Notes entre 12 et 18
            $note->setDateNote($date);
            $note->setPeriodeAcademique($periode);
            $manager->persist($note);
        }

        // Créer des absences
        $absenceData = [
            ['MATH', new \DateTime('2024-10-05'), 'absence', 'Maladie'],
            ['PC', new \DateTime('2024-10-12'), 'retard', 'Transport en retard'],
            ['FR', new \DateTime('2024-10-18'), 'absence', 'Raison familiale'],
        ];

        foreach ($absenceData as [$matiereCode, $date, $type, $motif]) {
            $absence = new Absence();
            $absence->setEtudiant($etudiant);
            $absence->setMatiere($matieres[$matiereCode]);
            $absence->setEnseignant($enseignant);
            $absence->setDateAbsence($date);
            $absence->setType($type);
            $absence->setMotif($motif);
            $absence->setJustifiee(true);
            $absence->setPeriodeAcademique($periode);
            $absence->setValidePar($enseignantUser);
            $manager->persist($absence);
        }

        $manager->flush();
    }
}
