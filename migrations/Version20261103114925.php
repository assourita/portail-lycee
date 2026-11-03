<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261103114925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              paiement
            ADD
              facture_id INT DEFAULT NULL,
            ADD
              mode_paiement VARCHAR(30) DEFAULT NULL,
            ADD
              reference VARCHAR(100) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              paiement
            ADD
              CONSTRAINT FK_B1DC7A1E7F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B1DC7A1E7F2DEE08 ON paiement (facture_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E7F2DEE08
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_B1DC7A1E7F2DEE08 ON paiement
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement DROP facture_id, DROP mode_paiement, DROP reference
        SQL);
    }
}
