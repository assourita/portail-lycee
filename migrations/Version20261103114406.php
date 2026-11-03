<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261103114406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE absence (
              id INT AUTO_INCREMENT NOT NULL,
              etudiant_id INT NOT NULL,
              matiere_id INT DEFAULT NULL,
              enseignant_id INT DEFAULT NULL,
              valide_par_id INT DEFAULT NULL,
              periode_academique_id INT DEFAULT NULL,
              date DATE NOT NULL,
              type VARCHAR(20) NOT NULL,
              periode VARCHAR(20) NOT NULL,
              heure_debut TIME DEFAULT NULL,
              heure_fin TIME DEFAULT NULL,
              justifiee TINYINT(1) NOT NULL,
              motif LONGTEXT DEFAULT NULL,
              justification LONGTEXT DEFAULT NULL,
              document_justificatif VARCHAR(255) DEFAULT NULL,
              date_creation DATETIME NOT NULL,
              date_modification DATETIME DEFAULT NULL,
              date_validation DATETIME DEFAULT NULL,
              INDEX IDX_765AE0C9DDEAB1A3 (etudiant_id),
              INDEX IDX_765AE0C9F46CD258 (matiere_id),
              INDEX IDX_765AE0C9E455FCC0 (enseignant_id),
              INDEX IDX_765AE0C96AF12ED9 (valide_par_id),
              INDEX IDX_765AE0C9C4BB4B40 (periode_academique_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE admin (
              id INT AUTO_INCREMENT NOT NULL,
              user_id INT DEFAULT NULL,
              nom VARCHAR(255) NOT NULL,
              prenom VARCHAR(255) NOT NULL,
              email VARCHAR(255) DEFAULT NULL,
              password VARCHAR(255) DEFAULT NULL,
              poste VARCHAR(255) DEFAULT NULL,
              UNIQUE INDEX UNIQ_880E0D76A76ED395 (user_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE bulletin (
              id INT AUTO_INCREMENT NOT NULL,
              etudiant_id INT NOT NULL,
              periode VARCHAR(50) NOT NULL,
              annee_scolaire VARCHAR(20) NOT NULL,
              commentaire LONGTEXT DEFAULT NULL,
              date_creation DATETIME NOT NULL,
              date_modification DATETIME DEFAULT NULL,
              INDEX IDX_2B7D8942DDEAB1A3 (etudiant_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE chapitre (
              id INT AUTO_INCREMENT NOT NULL,
              cours_id INT NOT NULL,
              titre VARCHAR(255) NOT NULL,
              contenu LONGTEXT NOT NULL,
              ordre INT NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_8C62B0257ECF78B0 (cours_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE classe (
              id INT AUTO_INCREMENT NOT NULL,
              nom VARCHAR(50) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE comptable (
              id INT AUTO_INCREMENT NOT NULL,
              user_id INT DEFAULT NULL,
              nom VARCHAR(255) NOT NULL,
              prenom VARCHAR(255) NOT NULL,
              email VARCHAR(255) DEFAULT NULL,
              password VARCHAR(255) DEFAULT NULL,
              telephone VARCHAR(255) DEFAULT NULL,
              poste VARCHAR(255) DEFAULT NULL,
              UNIQUE INDEX UNIQ_5B644367A76ED395 (user_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE cours (
              id INT AUTO_INCREMENT NOT NULL,
              enseignant_id INT NOT NULL,
              matiere_id INT DEFAULT NULL,
              periode_academique_id INT DEFAULT NULL,
              titre VARCHAR(255) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              date_ajout DATETIME NOT NULL,
              contenu VARCHAR(255) NOT NULL,
              date_publication DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_FDCA8C9CE455FCC0 (enseignant_id),
              INDEX IDX_FDCA8C9CF46CD258 (matiere_id),
              INDEX IDX_FDCA8C9CC4BB4B40 (periode_academique_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE devoir (
              id INT AUTO_INCREMENT NOT NULL,
              matiere_id INT NOT NULL,
              enseignant_id INT NOT NULL,
              periode_academique_id INT DEFAULT NULL,
              evaluation_id INT DEFAULT NULL,
              titre VARCHAR(255) NOT NULL,
              description LONGTEXT NOT NULL,
              consignes LONGTEXT DEFAULT NULL,
              date_creation DATETIME NOT NULL,
              date_echeance DATETIME NOT NULL,
              date_modification DATETIME DEFAULT NULL,
              note_max INT NOT NULL,
              accepte_soumissions_tardives TINYINT(1) NOT NULL,
              statut VARCHAR(50) NOT NULL,
              documents_joints LONGTEXT DEFAULT NULL,
              INDEX IDX_749EA771F46CD258 (matiere_id),
              INDEX IDX_749EA771E455FCC0 (enseignant_id),
              INDEX IDX_749EA771C4BB4B40 (periode_academique_id),
              INDEX IDX_749EA771456C5646 (evaluation_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE emploi_du_temps (
              id INT AUTO_INCREMENT NOT NULL,
              jour_id INT NOT NULL,
              matiere_id INT NOT NULL,
              cours_id INT DEFAULT NULL,
              filiere_id INT NOT NULL,
              enseignant_id INT NOT NULL,
              heure_debut TIME NOT NULL,
              heure_fin TIME NOT NULL,
              date DATE DEFAULT NULL,
              INDEX IDX_F86B32C1220C6AD0 (jour_id),
              INDEX IDX_F86B32C1F46CD258 (matiere_id),
              INDEX IDX_F86B32C17ECF78B0 (cours_id),
              INDEX IDX_F86B32C1180AA129 (filiere_id),
              INDEX IDX_F86B32C1E455FCC0 (enseignant_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE enseignant (
              id INT AUTO_INCREMENT NOT NULL,
              user_id INT DEFAULT NULL,
              nom VARCHAR(255) NOT NULL,
              prenom VARCHAR(255) NOT NULL,
              email VARCHAR(255) DEFAULT NULL,
              password VARCHAR(255) DEFAULT NULL,
              specialite VARCHAR(255) NOT NULL,
              telephone VARCHAR(255) DEFAULT NULL,
              adresse VARCHAR(255) DEFAULT NULL,
              date_embauche DATE NOT NULL,
              UNIQUE INDEX UNIQ_81A72FA1A76ED395 (user_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE etudiant (
              id INT AUTO_INCREMENT NOT NULL,
              filiere_id INT DEFAULT NULL,
              user_id INT DEFAULT NULL,
              nom VARCHAR(255) NOT NULL,
              prenom VARCHAR(255) NOT NULL,
              date_naissance DATE NOT NULL,
              email VARCHAR(255) DEFAULT NULL,
              password VARCHAR(255) DEFAULT NULL,
              date_inscription DATE NOT NULL,
              telephone VARCHAR(255) DEFAULT NULL,
              adresse VARCHAR(255) DEFAULT NULL,
              INDEX IDX_717E22E3180AA129 (filiere_id),
              UNIQUE INDEX UNIQ_717E22E3A76ED395 (user_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE evaluation (
              id INT AUTO_INCREMENT NOT NULL,
              matiere_id INT NOT NULL,
              enseignant_id INT NOT NULL,
              periode_academique_id INT DEFAULT NULL,
              trimestre_id INT NOT NULL,
              nom VARCHAR(255) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              date_evaluation DATETIME NOT NULL,
              note_max INT NOT NULL,
              coefficient INT NOT NULL,
              date_creation DATETIME NOT NULL,
              date_modification DATETIME DEFAULT NULL,
              INDEX IDX_1323A575F46CD258 (matiere_id),
              INDEX IDX_1323A575E455FCC0 (enseignant_id),
              INDEX IDX_1323A575C4BB4B40 (periode_academique_id),
              INDEX IDX_1323A575B9DB5D9D (trimestre_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE facture (
              id INT AUTO_INCREMENT NOT NULL,
              etudiant_id INT NOT NULL,
              montant DOUBLE PRECISION NOT NULL,
              libelle VARCHAR(255) NOT NULL,
              statut VARCHAR(20) NOT NULL,
              date_echeance DATETIME NOT NULL,
              date_creation DATETIME NOT NULL,
              INDEX IDX_FE866410DDEAB1A3 (etudiant_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE filiere (
              id INT AUTO_INCREMENT NOT NULL,
              classe_id INT DEFAULT NULL,
              nom VARCHAR(255) NOT NULL,
              INDEX IDX_2ED05D9E8F5EA509 (classe_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE jour (
              id INT AUTO_INCREMENT NOT NULL,
              nom VARCHAR(255) NOT NULL,
              ordre INT NOT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE matiere (
              id INT AUTO_INCREMENT NOT NULL,
              filiere_id INT DEFAULT NULL,
              enseignant_id INT DEFAULT NULL,
              nom VARCHAR(255) NOT NULL,
              coeficient INT NOT NULL,
              INDEX IDX_9014574A180AA129 (filiere_id),
              INDEX IDX_9014574AE455FCC0 (enseignant_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE note (
              id INT AUTO_INCREMENT NOT NULL,
              etudiant_id INT NOT NULL,
              matiere_id INT NOT NULL,
              enseignant_id INT NOT NULL,
              periode_academique_id INT DEFAULT NULL,
              evaluation_id INT DEFAULT NULL,
              valeur NUMERIC(4, 2) NOT NULL,
              commentaire LONGTEXT DEFAULT NULL,
              date_note DATE NOT NULL,
              type VARCHAR(50) NOT NULL,
              INDEX IDX_CFBDFA14DDEAB1A3 (etudiant_id),
              INDEX IDX_CFBDFA14F46CD258 (matiere_id),
              INDEX IDX_CFBDFA14E455FCC0 (enseignant_id),
              INDEX IDX_CFBDFA14C4BB4B40 (periode_academique_id),
              INDEX IDX_CFBDFA14456C5646 (evaluation_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE paiement (
              id INT AUTO_INCREMENT NOT NULL,
              etudiant_id INT NOT NULL,
              montant DOUBLE PRECISION NOT NULL,
              type VARCHAR(255) NOT NULL,
              statut VARCHAR(20) NOT NULL,
              date_paiement DATETIME NOT NULL,
              INDEX IDX_B1DC7A1EDDEAB1A3 (etudiant_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE parcours_scolaire (
              id INT AUTO_INCREMENT NOT NULL,
              etudiant_id INT NOT NULL,
              filiere_id INT DEFAULT NULL,
              classe_id INT DEFAULT NULL,
              annee_scolaire VARCHAR(20) NOT NULL,
              niveau VARCHAR(50) NOT NULL,
              moyenne_annuelle NUMERIC(4, 2) DEFAULT NULL,
              moyenne_trimestre1 NUMERIC(4, 2) DEFAULT NULL,
              moyenne_trimestre2 NUMERIC(4, 2) DEFAULT NULL,
              moyenne_trimestre3 NUMERIC(4, 2) DEFAULT NULL,
              rang_classe INT DEFAULT NULL,
              total_etudiants INT DEFAULT NULL,
              total_absences INT DEFAULT NULL,
              absences_justifiees INT DEFAULT NULL,
              taux_assiduite NUMERIC(5, 2) DEFAULT NULL,
              decision VARCHAR(50) NOT NULL,
              appreciation_generale LONGTEXT DEFAULT NULL,
              remarques LONGTEXT DEFAULT NULL,
              date_creation DATETIME NOT NULL,
              date_modification DATETIME DEFAULT NULL,
              INDEX IDX_3E094934DDEAB1A3 (etudiant_id),
              INDEX IDX_3E094934180AA129 (filiere_id),
              INDEX IDX_3E0949348F5EA509 (classe_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE parent_eleve (
              id INT AUTO_INCREMENT NOT NULL,
              user_id INT DEFAULT NULL,
              nom VARCHAR(100) NOT NULL,
              prenom VARCHAR(100) NOT NULL,
              email VARCHAR(180) NOT NULL,
              telephone VARCHAR(20) DEFAULT NULL,
              telephone_secondaire VARCHAR(20) DEFAULT NULL,
              adresse VARCHAR(255) DEFAULT NULL,
              lien_parente VARCHAR(50) NOT NULL,
              profession VARCHAR(100) DEFAULT NULL,
              date_creation DATETIME NOT NULL,
              date_modification DATETIME DEFAULT NULL,
              UNIQUE INDEX UNIQ_20909154E7927C74 (email),
              UNIQUE INDEX UNIQ_20909154A76ED395 (user_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE parent_eleve_etudiant (
              parent_eleve_id INT NOT NULL,
              etudiant_id INT NOT NULL,
              INDEX IDX_7B4EB09C95A16B63 (parent_eleve_id),
              INDEX IDX_7B4EB09CDDEAB1A3 (etudiant_id),
              PRIMARY KEY(parent_eleve_id, etudiant_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE periode_academique (
              id INT AUTO_INCREMENT NOT NULL,
              annee_scolaire VARCHAR(20) NOT NULL,
              periode VARCHAR(50) NOT NULL,
              date_debut DATETIME NOT NULL,
              date_fin DATETIME NOT NULL,
              is_active TINYINT(1) NOT NULL,
              date_creation DATETIME NOT NULL,
              date_modification DATETIME DEFAULT NULL,
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE piece_jointe (
              id INT AUTO_INCREMENT NOT NULL,
              chapitre_id INT NOT NULL,
              nom VARCHAR(255) NOT NULL,
              fichier VARCHAR(255) NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              INDEX IDX_AB5111D41FBEEF7B (chapitre_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE soumission (
              id INT AUTO_INCREMENT NOT NULL,
              devoir_id INT NOT NULL,
              etudiant_id INT NOT NULL,
              date_soumission DATETIME NOT NULL,
              commentaire LONGTEXT DEFAULT NULL,
              fichiers LONGTEXT DEFAULT NULL,
              statut VARCHAR(50) NOT NULL,
              note NUMERIC(4, 2) DEFAULT NULL,
              commentaire_enseignant LONGTEXT DEFAULT NULL,
              date_correction DATETIME DEFAULT NULL,
              date_modification DATETIME DEFAULT NULL,
              INDEX IDX_9495AA2EC583534E (devoir_id),
              INDEX IDX_9495AA2EDDEAB1A3 (etudiant_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE trimestre (
              id INT AUTO_INCREMENT NOT NULL,
              classe_id INT NOT NULL,
              nom VARCHAR(50) NOT NULL,
              date_debut DATE NOT NULL,
              date_fin DATE NOT NULL,
              annee_scolaire VARCHAR(20) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              INDEX IDX_5406BC488F5EA509 (classe_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (
              id INT AUTO_INCREMENT NOT NULL,
              username VARCHAR(180) NOT NULL,
              roles JSON NOT NULL COMMENT '(DC2Type:json)',
              password VARCHAR(255) NOT NULL,
              is_activate TINYINT(1) NOT NULL,
              email VARCHAR(255) DEFAULT NULL,
              last_login DATETIME DEFAULT NULL,
              theme VARCHAR(20) DEFAULT NULL,
              langue VARCHAR(10) DEFAULT NULL,
              notifications_email TINYINT(1) DEFAULT NULL,
              notifications_sms TINYINT(1) DEFAULT NULL,
              google_authenticator_secret VARCHAR(255) DEFAULT NULL,
              password_changed_at DATETIME DEFAULT NULL,
              reset_token VARCHAR(255) DEFAULT NULL,
              reset_token_expires_at DATETIME DEFAULT NULL,
              UNIQUE INDEX UNIQ_8D93D649F85E0677 (username),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              absence
            ADD
              CONSTRAINT FK_765AE0C9DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              absence
            ADD
              CONSTRAINT FK_765AE0C9F46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              absence
            ADD
              CONSTRAINT FK_765AE0C9E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              absence
            ADD
              CONSTRAINT FK_765AE0C96AF12ED9 FOREIGN KEY (valide_par_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              absence
            ADD
              CONSTRAINT FK_765AE0C9C4BB4B40 FOREIGN KEY (periode_academique_id) REFERENCES periode_academique (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              admin
            ADD
              CONSTRAINT FK_880E0D76A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              bulletin
            ADD
              CONSTRAINT FK_2B7D8942DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              chapitre
            ADD
              CONSTRAINT FK_8C62B0257ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              comptable
            ADD
              CONSTRAINT FK_5B644367A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              cours
            ADD
              CONSTRAINT FK_FDCA8C9CE455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              cours
            ADD
              CONSTRAINT FK_FDCA8C9CF46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              cours
            ADD
              CONSTRAINT FK_FDCA8C9CC4BB4B40 FOREIGN KEY (periode_academique_id) REFERENCES periode_academique (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              devoir
            ADD
              CONSTRAINT FK_749EA771F46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              devoir
            ADD
              CONSTRAINT FK_749EA771E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              devoir
            ADD
              CONSTRAINT FK_749EA771C4BB4B40 FOREIGN KEY (periode_academique_id) REFERENCES periode_academique (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              devoir
            ADD
              CONSTRAINT FK_749EA771456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              emploi_du_temps
            ADD
              CONSTRAINT FK_F86B32C1220C6AD0 FOREIGN KEY (jour_id) REFERENCES jour (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              emploi_du_temps
            ADD
              CONSTRAINT FK_F86B32C1F46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              emploi_du_temps
            ADD
              CONSTRAINT FK_F86B32C17ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              emploi_du_temps
            ADD
              CONSTRAINT FK_F86B32C1180AA129 FOREIGN KEY (filiere_id) REFERENCES filiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              emploi_du_temps
            ADD
              CONSTRAINT FK_F86B32C1E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              enseignant
            ADD
              CONSTRAINT FK_81A72FA1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              etudiant
            ADD
              CONSTRAINT FK_717E22E3180AA129 FOREIGN KEY (filiere_id) REFERENCES filiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              etudiant
            ADD
              CONSTRAINT FK_717E22E3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              evaluation
            ADD
              CONSTRAINT FK_1323A575F46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              evaluation
            ADD
              CONSTRAINT FK_1323A575E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              evaluation
            ADD
              CONSTRAINT FK_1323A575C4BB4B40 FOREIGN KEY (periode_academique_id) REFERENCES periode_academique (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              evaluation
            ADD
              CONSTRAINT FK_1323A575B9DB5D9D FOREIGN KEY (trimestre_id) REFERENCES trimestre (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              facture
            ADD
              CONSTRAINT FK_FE866410DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              filiere
            ADD
              CONSTRAINT FK_2ED05D9E8F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              matiere
            ADD
              CONSTRAINT FK_9014574A180AA129 FOREIGN KEY (filiere_id) REFERENCES filiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              matiere
            ADD
              CONSTRAINT FK_9014574AE455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              note
            ADD
              CONSTRAINT FK_CFBDFA14DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              note
            ADD
              CONSTRAINT FK_CFBDFA14F46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              note
            ADD
              CONSTRAINT FK_CFBDFA14E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              note
            ADD
              CONSTRAINT FK_CFBDFA14C4BB4B40 FOREIGN KEY (periode_academique_id) REFERENCES periode_academique (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              note
            ADD
              CONSTRAINT FK_CFBDFA14456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              paiement
            ADD
              CONSTRAINT FK_B1DC7A1EDDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              parcours_scolaire
            ADD
              CONSTRAINT FK_3E094934DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              parcours_scolaire
            ADD
              CONSTRAINT FK_3E094934180AA129 FOREIGN KEY (filiere_id) REFERENCES filiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              parcours_scolaire
            ADD
              CONSTRAINT FK_3E0949348F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              parent_eleve
            ADD
              CONSTRAINT FK_20909154A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              parent_eleve_etudiant
            ADD
              CONSTRAINT FK_7B4EB09C95A16B63 FOREIGN KEY (parent_eleve_id) REFERENCES parent_eleve (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              parent_eleve_etudiant
            ADD
              CONSTRAINT FK_7B4EB09CDDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              piece_jointe
            ADD
              CONSTRAINT FK_AB5111D41FBEEF7B FOREIGN KEY (chapitre_id) REFERENCES chapitre (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              soumission
            ADD
              CONSTRAINT FK_9495AA2EC583534E FOREIGN KEY (devoir_id) REFERENCES devoir (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              soumission
            ADD
              CONSTRAINT FK_9495AA2EDDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              trimestre
            ADD
              CONSTRAINT FK_5406BC488F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9DDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9F46CD258
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9E455FCC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C96AF12ED9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9C4BB4B40
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE admin DROP FOREIGN KEY FK_880E0D76A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bulletin DROP FOREIGN KEY FK_2B7D8942DDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE chapitre DROP FOREIGN KEY FK_8C62B0257ECF78B0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comptable DROP FOREIGN KEY FK_5B644367A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CE455FCC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CF46CD258
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CC4BB4B40
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir DROP FOREIGN KEY FK_749EA771F46CD258
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir DROP FOREIGN KEY FK_749EA771E455FCC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir DROP FOREIGN KEY FK_749EA771C4BB4B40
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir DROP FOREIGN KEY FK_749EA771456C5646
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps DROP FOREIGN KEY FK_F86B32C1220C6AD0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps DROP FOREIGN KEY FK_F86B32C1F46CD258
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps DROP FOREIGN KEY FK_F86B32C17ECF78B0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps DROP FOREIGN KEY FK_F86B32C1180AA129
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps DROP FOREIGN KEY FK_F86B32C1E455FCC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE enseignant DROP FOREIGN KEY FK_81A72FA1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE etudiant DROP FOREIGN KEY FK_717E22E3180AA129
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE etudiant DROP FOREIGN KEY FK_717E22E3A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575F46CD258
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575E455FCC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575C4BB4B40
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575B9DB5D9D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE facture DROP FOREIGN KEY FK_FE866410DDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE filiere DROP FOREIGN KEY FK_2ED05D9E8F5EA509
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE matiere DROP FOREIGN KEY FK_9014574A180AA129
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE matiere DROP FOREIGN KEY FK_9014574AE455FCC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14DDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14F46CD258
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14E455FCC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14C4BB4B40
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14456C5646
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1EDDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parcours_scolaire DROP FOREIGN KEY FK_3E094934DDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parcours_scolaire DROP FOREIGN KEY FK_3E094934180AA129
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parcours_scolaire DROP FOREIGN KEY FK_3E0949348F5EA509
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve DROP FOREIGN KEY FK_20909154A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve_etudiant DROP FOREIGN KEY FK_7B4EB09C95A16B63
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve_etudiant DROP FOREIGN KEY FK_7B4EB09CDDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE piece_jointe DROP FOREIGN KEY FK_AB5111D41FBEEF7B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE soumission DROP FOREIGN KEY FK_9495AA2EC583534E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE soumission DROP FOREIGN KEY FK_9495AA2EDDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trimestre DROP FOREIGN KEY FK_5406BC488F5EA509
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE absence
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE admin
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE bulletin
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE chapitre
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE classe
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE comptable
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE cours
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE devoir
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE emploi_du_temps
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE enseignant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE etudiant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE evaluation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE facture
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE filiere
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE jour
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE matiere
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE note
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE paiement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE parcours_scolaire
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE parent_eleve
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE parent_eleve_etudiant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE periode_academique
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE piece_jointe
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE soumission
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE trimestre
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
    }
}
