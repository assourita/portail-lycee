<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250116000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create bulletin table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bulletin (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, periode VARCHAR(50) NOT NULL, annee_scolaire VARCHAR(20) NOT NULL, commentaire LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, INDEX IDX_1F87AF51DDEAB1A3 (etudiant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bulletin ADD CONSTRAINT FK_1F87AF51DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bulletin DROP FOREIGN KEY FK_1F87AF51DDEAB1A3');
        $this->addSql('DROP TABLE bulletin');
    }
}
