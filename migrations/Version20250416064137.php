<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250416064137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE piece_jointe (id INT AUTO_INCREMENT NOT NULL, cours_id INT NOT NULL, nom VARCHAR(255) NOT NULL, fichier VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_AB5111D47ECF78B0 (cours_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE piece_jointe ADD CONSTRAINT FK_AB5111D47ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours ADD contenu VARCHAR(255) NOT NULL, ADD date_publication DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE piece_jointe DROP FOREIGN KEY FK_AB5111D47ECF78B0
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE piece_jointe
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours DROP contenu, DROP date_publication
        SQL);
    }
}
