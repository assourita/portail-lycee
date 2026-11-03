<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009120938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE classe (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, date_fin_trimestre1 DATE DEFAULT NULL, date_fin_trimestre2 DATE DEFAULT NULL, date_fin_trimestre3 DATE DEFAULT NULL, date_fin_semestre1 DATE DEFAULT NULL, date_fin_semestre2 DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bulletin RENAME INDEX idx_1f87af51ddeab1a3 TO IDX_2B7D8942DDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE filiere ADD classe_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE filiere ADD CONSTRAINT FK_2ED05D9E8F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2ED05D9E8F5EA509 ON filiere (classe_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE filiere DROP FOREIGN KEY FK_2ED05D9E8F5EA509
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE classe
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bulletin RENAME INDEX idx_2b7d8942ddeab1a3 TO IDX_1F87AF51DDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_2ED05D9E8F5EA509 ON filiere
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE filiere DROP classe_id
        SQL);
    }
}
