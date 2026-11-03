<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260402192335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE facture (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, montant DOUBLE PRECISION NOT NULL, libelle VARCHAR(255) NOT NULL, statut VARCHAR(20) NOT NULL, date_echeance DATETIME NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_FE866410DDEAB1A3 (etudiant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE paiement (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, montant DOUBLE PRECISION NOT NULL, type VARCHAR(255) NOT NULL, statut VARCHAR(20) NOT NULL, date_paiement DATETIME NOT NULL, INDEX IDX_B1DC7A1EDDEAB1A3 (etudiant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE facture ADD CONSTRAINT FK_FE866410DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1EDDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE parent
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE parent_etudiant
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence CHANGE justifiee justifiee TINYINT(1) NOT NULL, CHANGE motif motif LONGTEXT DEFAULT NULL, CHANGE justification justification LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9F46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence ADD CONSTRAINT FK_765AE0C96AF12ED9 FOREIGN KEY (valide_par_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9C4BB4B40 FOREIGN KEY (periode_academique_id) REFERENCES periode_academique (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence RENAME INDEX idx_765ae0c992e3fd34 TO IDX_765AE0C96AF12ED9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours CHANGE periode_academique_id periode_academique_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9CC4BB4B40 FOREIGN KEY (periode_academique_id) REFERENCES periode_academique (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FDCA8C9CC4BB4B40 ON cours (periode_academique_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir CHANGE description description LONGTEXT NOT NULL, CHANGE consignes consignes LONGTEXT DEFAULT NULL, CHANGE note_max note_max INT NOT NULL, CHANGE accepte_soumissions_tardives accepte_soumissions_tardives TINYINT(1) NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE documents_joints documents_joints LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir ADD CONSTRAINT FK_749EA771F46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir ADD CONSTRAINT FK_749EA771E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir ADD CONSTRAINT FK_749EA771C4BB4B40 FOREIGN KEY (periode_academique_id) REFERENCES periode_academique (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir ADD CONSTRAINT FK_749EA771456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir RENAME INDEX idx_749ea5f4f46cd258 TO IDX_749EA771F46CD258
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir RENAME INDEX idx_749ea5f4e455fcc0 TO IDX_749EA771E455FCC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir RENAME INDEX idx_749ea5f4c4bb4b40 TO IDX_749EA771C4BB4B40
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir RENAME INDEX idx_749ea5f4456c5646 TO IDX_749EA771456C5646
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation CHANGE periode_academique_id periode_academique_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575B9DB5D9D FOREIGN KEY (trimestre_id) REFERENCES trimestre (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1323A575B9DB5D9D ON evaluation (trimestre_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14C4BB4B40 FOREIGN KEY (periode_academique_id) REFERENCES periode_academique (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CFBDFA14C4BB4B40 ON note (periode_academique_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CFBDFA14456C5646 ON note (evaluation_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parcours_scolaire CHANGE decision decision VARCHAR(50) NOT NULL, CHANGE appreciation_generale appreciation_generale LONGTEXT DEFAULT NULL, CHANGE remarques remarques LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parcours_scolaire ADD CONSTRAINT FK_3E094934DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parcours_scolaire ADD CONSTRAINT FK_3E094934180AA129 FOREIGN KEY (filiere_id) REFERENCES filiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parcours_scolaire ADD CONSTRAINT FK_3E0949348F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parcours_scolaire RENAME INDEX idx_parcours_etudiant TO IDX_3E094934DDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parcours_scolaire RENAME INDEX idx_parcours_filiere TO IDX_3E094934180AA129
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parcours_scolaire RENAME INDEX idx_parcours_classe TO IDX_3E0949348F5EA509
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve ADD CONSTRAINT FK_20909154A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve RENAME INDEX email TO UNIQ_20909154E7927C74
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve RENAME INDEX uniq_parent_user TO UNIQ_20909154A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve_etudiant ADD CONSTRAINT FK_7B4EB09C95A16B63 FOREIGN KEY (parent_eleve_id) REFERENCES parent_eleve (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve_etudiant ADD CONSTRAINT FK_7B4EB09CDDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve_etudiant RENAME INDEX idx_pe_parent TO IDX_7B4EB09C95A16B63
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve_etudiant RENAME INDEX idx_pe_etudiant TO IDX_7B4EB09CDDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE soumission CHANGE commentaire commentaire LONGTEXT DEFAULT NULL, CHANGE fichiers fichiers LONGTEXT DEFAULT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE commentaire_enseignant commentaire_enseignant LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE soumission ADD CONSTRAINT FK_9495AA2EC583534E FOREIGN KEY (devoir_id) REFERENCES devoir (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE soumission ADD CONSTRAINT FK_9495AA2EDDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE soumission RENAME INDEX idx_da0ea3e720c1c2dd TO IDX_9495AA2EC583534E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE soumission RENAME INDEX idx_da0ea3e7ddeab1a3 TO IDX_9495AA2EDDEAB1A3
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE parent (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, prenom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, telephone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_uca1400_ai_ci`, telephone_secondaire VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_uca1400_ai_ci`, adresse VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_uca1400_ai_ci`, lien_parente VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, profession VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_uca1400_ai_ci`, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_15390D8CA76ED395 (user_id), UNIQUE INDEX email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE parent_etudiant (parent_id INT NOT NULL, etudiant_id INT NOT NULL, INDEX IDX_A103E3C727ACA70 (parent_id), INDEX IDX_A103E3C7DDEAB1A3 (etudiant_id), PRIMARY KEY(parent_id, etudiant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE facture DROP FOREIGN KEY FK_FE866410DDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1EDDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE facture
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE paiement
        SQL);
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
            ALTER TABLE absence CHANGE justifiee justifiee TINYINT(1) DEFAULT 0, CHANGE motif motif TEXT DEFAULT NULL, CHANGE justification justification TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence RENAME INDEX idx_765ae0c96af12ed9 TO IDX_765AE0C992E3FD34
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CC4BB4B40
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FDCA8C9CC4BB4B40 ON cours
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours CHANGE periode_academique_id periode_academique_id INT NOT NULL
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
            ALTER TABLE devoir CHANGE description description TEXT NOT NULL, CHANGE consignes consignes TEXT DEFAULT NULL, CHANGE note_max note_max INT DEFAULT 20 NOT NULL, CHANGE accepte_soumissions_tardives accepte_soumissions_tardives TINYINT(1) DEFAULT 0, CHANGE statut statut VARCHAR(50) DEFAULT 'brouillon' NOT NULL, CHANGE documents_joints documents_joints TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir RENAME INDEX idx_749ea771e455fcc0 TO IDX_749EA5F4E455FCC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir RENAME INDEX idx_749ea771c4bb4b40 TO IDX_749EA5F4C4BB4B40
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir RENAME INDEX idx_749ea771456c5646 TO IDX_749EA5F4456C5646
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE devoir RENAME INDEX idx_749ea771f46cd258 TO IDX_749EA5F4F46CD258
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575B9DB5D9D
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_1323A575B9DB5D9D ON evaluation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation CHANGE periode_academique_id periode_academique_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14C4BB4B40
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14456C5646
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_CFBDFA14C4BB4B40 ON note
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_CFBDFA14456C5646 ON note
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
            ALTER TABLE parcours_scolaire CHANGE decision decision VARCHAR(50) DEFAULT 'en_cours' NOT NULL, CHANGE appreciation_generale appreciation_generale TEXT DEFAULT NULL, CHANGE remarques remarques TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parcours_scolaire RENAME INDEX idx_3e0949348f5ea509 TO IDX_PARCOURS_CLASSE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parcours_scolaire RENAME INDEX idx_3e094934ddeab1a3 TO IDX_PARCOURS_ETUDIANT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parcours_scolaire RENAME INDEX idx_3e094934180aa129 TO IDX_PARCOURS_FILIERE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve DROP FOREIGN KEY FK_20909154A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve RENAME INDEX uniq_20909154e7927c74 TO email
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve RENAME INDEX uniq_20909154a76ed395 TO UNIQ_PARENT_USER
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve_etudiant DROP FOREIGN KEY FK_7B4EB09C95A16B63
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve_etudiant DROP FOREIGN KEY FK_7B4EB09CDDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve_etudiant RENAME INDEX idx_7b4eb09cddeab1a3 TO IDX_PE_ETUDIANT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE parent_eleve_etudiant RENAME INDEX idx_7b4eb09c95a16b63 TO IDX_PE_PARENT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE soumission DROP FOREIGN KEY FK_9495AA2EC583534E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE soumission DROP FOREIGN KEY FK_9495AA2EDDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE soumission CHANGE commentaire commentaire TEXT DEFAULT NULL, CHANGE fichiers fichiers TEXT DEFAULT NULL, CHANGE statut statut VARCHAR(50) DEFAULT 'soumis' NOT NULL, CHANGE commentaire_enseignant commentaire_enseignant TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE soumission RENAME INDEX idx_9495aa2ec583534e TO IDX_DA0EA3E720C1C2DD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE soumission RENAME INDEX idx_9495aa2eddeab1a3 TO IDX_DA0EA3E7DDEAB1A3
        SQL);
    }
}
