<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250416071039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE etudiant CHANGE user_id user_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE piece_jointe DROP FOREIGN KEY FK_AB5111D47ECF78B0
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_AB5111D47ECF78B0 ON piece_jointe
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE piece_jointe CHANGE cours_id chapitre_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE piece_jointe ADD CONSTRAINT FK_AB5111D41FBEEF7B FOREIGN KEY (chapitre_id) REFERENCES chapitre (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AB5111D41FBEEF7B ON piece_jointe (chapitre_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE etudiant CHANGE user_id user_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE piece_jointe DROP FOREIGN KEY FK_AB5111D41FBEEF7B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_AB5111D41FBEEF7B ON piece_jointe
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE piece_jointe CHANGE chapitre_id cours_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE piece_jointe ADD CONSTRAINT FK_AB5111D47ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AB5111D47ECF78B0 ON piece_jointe (cours_id)
        SQL);
    }
}
