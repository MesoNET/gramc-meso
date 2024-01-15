<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240115081953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE templates');
        $this->addSql('ALTER TABLE adresseip CHANGE adresse adresse VARCHAR(45) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX adresseip ON adresseip (adresse, id_labo)');
        $this->addSql('ALTER TABLE dac DROP FOREIGN KEY FK_18C2D0EA61817AB3');
        $this->addSql('DROP INDEX IDX_18C2D0EA61817AB3 ON dac');
        $this->addSql('ALTER TABLE dac CHANGE id_version id_version VARCHAR(13) NOT NULL');
        $this->addSql('ALTER TABLE dar DROP FOREIGN KEY FK_7272F018E3D1DEE5');
        $this->addSql('DROP INDEX IDX_7272F018E3D1DEE5 ON dar');
        $this->addSql('ALTER TABLE dar CHANGE id_rallonge id_rallonge VARCHAR(15) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE templates (nom VARCHAR(40) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, sujet TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, contenu TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(nom)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP INDEX adresseip ON adresseip');
        $this->addSql('ALTER TABLE adresseip CHANGE adresse adresse TEXT NOT NULL');
        $this->addSql('ALTER TABLE dac CHANGE id_version id_version VARCHAR(13) DEFAULT NULL');
        $this->addSql('ALTER TABLE dac ADD CONSTRAINT FK_18C2D0EA61817AB3 FOREIGN KEY (id_version) REFERENCES version (id_version)');
        $this->addSql('CREATE INDEX IDX_18C2D0EA61817AB3 ON dac (id_version)');
        $this->addSql('ALTER TABLE dar CHANGE id_rallonge id_rallonge VARCHAR(15) DEFAULT NULL');
        $this->addSql('ALTER TABLE dar ADD CONSTRAINT FK_7272F018E3D1DEE5 FOREIGN KEY (id_rallonge) REFERENCES rallonge (id_rallonge)');
        $this->addSql('CREATE INDEX IDX_7272F018E3D1DEE5 ON dar (id_rallonge)');
    }
}
