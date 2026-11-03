<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250407112759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD enseignant_id INT NOT NULL, ADD commentaire LONGTEXT DEFAULT NULL, CHANGE valeur valeur NUMERIC(4, 2) NOT NULL, CHANGE type type VARCHAR(50) NOT NULL, CHANGE date date_note DATE NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CFBDFA14E455FCC0 ON note (enseignant_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14E455FCC0
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_CFBDFA14E455FCC0 ON note
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP enseignant_id, DROP commentaire, CHANGE valeur valeur DOUBLE PRECISION NOT NULL, CHANGE type type VARCHAR(255) NOT NULL, CHANGE date_note date DATE NOT NULL
        SQL);
    }
}
