<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009121645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE trimestre (id INT AUTO_INCREMENT NOT NULL, classe_id INT NOT NULL, nom VARCHAR(50) NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, annee_scolaire VARCHAR(20) NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_5406BC488F5EA509 (classe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trimestre ADD CONSTRAINT FK_5406BC488F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE classe DROP date_fin_trimestre1, DROP date_fin_trimestre2, DROP date_fin_trimestre3, DROP date_fin_semestre1, DROP date_fin_semestre2
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE trimestre DROP FOREIGN KEY FK_5406BC488F5EA509
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE trimestre
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE classe ADD date_fin_trimestre1 DATE DEFAULT NULL, ADD date_fin_trimestre2 DATE DEFAULT NULL, ADD date_fin_trimestre3 DATE DEFAULT NULL, ADD date_fin_semestre1 DATE DEFAULT NULL, ADD date_fin_semestre2 DATE DEFAULT NULL
        SQL);
    }
}
