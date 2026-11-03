<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009152632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
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
            ALTER TABLE evaluation CHANGE periode_academique_id periode_academique_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD periode_academique_id INT DEFAULT NULL, ADD evaluation_id INT DEFAULT NULL
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
            ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CC4BB4B40
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FDCA8C9CC4BB4B40 ON cours
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours CHANGE periode_academique_id periode_academique_id INT NOT NULL
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
            ALTER TABLE note DROP periode_academique_id, DROP evaluation_id
        SQL);
    }
}
