<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009152456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE evaluation (id INT AUTO_INCREMENT NOT NULL, matiere_id INT NOT NULL, enseignant_id INT NOT NULL, periode_academique_id INT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, date_evaluation DATETIME NOT NULL, note_max INT NOT NULL, coefficient INT NOT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, INDEX IDX_1323A575F46CD258 (matiere_id), INDEX IDX_1323A575E455FCC0 (enseignant_id), INDEX IDX_1323A575C4BB4B40 (periode_academique_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE periode_academique (id INT AUTO_INCREMENT NOT NULL, annee_scolaire VARCHAR(20) NOT NULL, periode VARCHAR(50) NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575F46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575C4BB4B40 FOREIGN KEY (periode_academique_id) REFERENCES periode_academique (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours ADD periode_academique_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9CC4BB4B40 FOREIGN KEY (periode_academique_id) REFERENCES periode_academique (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FDCA8C9CC4BB4B40 ON cours (periode_academique_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD periode_academique_id INT NOT NULL, ADD evaluation_id INT DEFAULT NULL
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
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14456C5646
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CC4BB4B40
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14C4BB4B40
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
            DROP TABLE evaluation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE periode_academique
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FDCA8C9CC4BB4B40 ON cours
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours DROP periode_academique_id
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_CFBDFA14C4BB4B40 ON note
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_CFBDFA14456C5646 ON note
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP periode_academique_id, DROP evaluation_id
        SQL);
    }
}
