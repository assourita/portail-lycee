<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240407140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la contrainte NOT NULL sur user_id dans la table etudiant';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si la colonne user_id existe
        $table = $schema->getTable('etudiant');
        if ($table->hasColumn('user_id')) {
            // D'abord, supprimer les étudiants sans user_id
            $this->addSql('DELETE FROM etudiant WHERE user_id IS NULL');
            
            // Ensuite, modifier la colonne pour la rendre NOT NULL
            $this->addSql('ALTER TABLE etudiant MODIFY user_id INT NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Revenir à la colonne nullable
        $this->addSql('ALTER TABLE etudiant MODIFY user_id INT DEFAULT NULL');
    }
} 