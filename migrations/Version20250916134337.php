<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250916134337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps CHANGE filiere_id filiere_id INT NOT NULL, CHANGE enseignant_id enseignant_id INT NOT NULL, CHANGE date date DATE DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps CHANGE filiere_id filiere_id INT DEFAULT NULL, CHANGE enseignant_id enseignant_id INT DEFAULT NULL, CHANGE date date DATE NOT NULL
        SQL);
    }
}
