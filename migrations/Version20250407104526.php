<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250407104526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE admin (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE chapitre (id INT AUTO_INCREMENT NOT NULL, cours_id INT NOT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, ordre INT NOT NULL, INDEX IDX_8C62B0257ECF78B0 (cours_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE comptable (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE cours (id INT AUTO_INCREMENT NOT NULL, enseignant_id INT NOT NULL, matiere_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_ajout DATETIME NOT NULL, INDEX IDX_FDCA8C9CE455FCC0 (enseignant_id), INDEX IDX_FDCA8C9CF46CD258 (matiere_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE emploi_du_temps (id INT AUTO_INCREMENT NOT NULL, jour_id INT NOT NULL, matiere_id INT NOT NULL, cours_id INT DEFAULT NULL, filiere_id INT DEFAULT NULL, enseignant_id INT DEFAULT NULL, heure_debut TIME NOT NULL, heure_fin TIME NOT NULL, date DATE NOT NULL, INDEX IDX_F86B32C1220C6AD0 (jour_id), INDEX IDX_F86B32C1F46CD258 (matiere_id), INDEX IDX_F86B32C17ECF78B0 (cours_id), INDEX IDX_F86B32C1180AA129 (filiere_id), INDEX IDX_F86B32C1E455FCC0 (enseignant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE enseignant (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, specialite VARCHAR(255) NOT NULL, date_embauche DATE NOT NULL, UNIQUE INDEX UNIQ_81A72FA1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE etudiant (id INT AUTO_INCREMENT NOT NULL, filiere_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, date_naissance DATE NOT NULL, email VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, date_inscription DATE NOT NULL, INDEX IDX_717E22E3180AA129 (filiere_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE filiere (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE jour (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE matiere (id INT AUTO_INCREMENT NOT NULL, filiere_id INT DEFAULT NULL, enseignant_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, coeficient INT NOT NULL, INDEX IDX_9014574A180AA129 (filiere_id), INDEX IDX_9014574AE455FCC0 (enseignant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, matiere_id INT NOT NULL, valeur DOUBLE PRECISION NOT NULL, type VARCHAR(255) NOT NULL, date DATE NOT NULL, INDEX IDX_CFBDFA14DDEAB1A3 (etudiant_id), INDEX IDX_CFBDFA14F46CD258 (matiere_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT '(DC2Type:json)', password VARCHAR(255) NOT NULL, is_activate TINYINT(1) NOT NULL, email VARCHAR(255) DEFAULT NULL, last_login DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE chapitre ADD CONSTRAINT FK_8C62B0257ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9CE455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9CF46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps ADD CONSTRAINT FK_F86B32C1220C6AD0 FOREIGN KEY (jour_id) REFERENCES jour (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps ADD CONSTRAINT FK_F86B32C1F46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps ADD CONSTRAINT FK_F86B32C17ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps ADD CONSTRAINT FK_F86B32C1180AA129 FOREIGN KEY (filiere_id) REFERENCES filiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps ADD CONSTRAINT FK_F86B32C1E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE enseignant ADD CONSTRAINT FK_81A72FA1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE etudiant ADD CONSTRAINT FK_717E22E3180AA129 FOREIGN KEY (filiere_id) REFERENCES filiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE matiere ADD CONSTRAINT FK_9014574A180AA129 FOREIGN KEY (filiere_id) REFERENCES filiere (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE matiere ADD CONSTRAINT FK_9014574AE455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14F46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE chapitre DROP FOREIGN KEY FK_8C62B0257ECF78B0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CE455FCC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CF46CD258
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps DROP FOREIGN KEY FK_F86B32C1220C6AD0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps DROP FOREIGN KEY FK_F86B32C1F46CD258
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps DROP FOREIGN KEY FK_F86B32C17ECF78B0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps DROP FOREIGN KEY FK_F86B32C1180AA129
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE emploi_du_temps DROP FOREIGN KEY FK_F86B32C1E455FCC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE enseignant DROP FOREIGN KEY FK_81A72FA1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE etudiant DROP FOREIGN KEY FK_717E22E3180AA129
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE matiere DROP FOREIGN KEY FK_9014574A180AA129
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE matiere DROP FOREIGN KEY FK_9014574AE455FCC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14DDEAB1A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14F46CD258
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE admin
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE chapitre
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE comptable
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE cours
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE emploi_du_temps
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE enseignant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE etudiant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE filiere
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE jour
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE matiere
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE note
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
    }
}
