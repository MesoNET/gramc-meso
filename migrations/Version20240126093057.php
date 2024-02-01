<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240126093057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, individu_id INT NOT NULL, sujet LONGTEXT NOT NULL, date_creation DATE NOT NULL, INDEX IDX_BF5476CA480B6028 (individu_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA480B6028 FOREIGN KEY (individu_id) REFERENCES individu (id_individu)');
        $this->addSql('DROP TABLE templates');
        $this->addSql('ALTER TABLE adresseip CHANGE adresse adresse VARCHAR(45) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX adresseip ON adresseip (adresse, id_labo)');
        $this->addSql('ALTER TABLE dar DROP FOREIGN KEY FK_7272F018E3D1DEE5');
        $this->addSql('DROP INDEX IDX_7272F018E3D1DEE5 ON dar');
        $this->addSql('ALTER TABLE dar CHANGE id_rallonge id_rallonge VARCHAR(15) NOT NULL');
        $this->addSql('DROP INDEX acro ON laboratoire');
        $this->addSql('ALTER TABLE laboratoire ADD numero_national_structure VARCHAR(50) NOT NULL, ADD actif TINYINT(1) NOT NULL, ADD numero_de_structure_successeur VARCHAR(50) DEFAULT NULL, CHANGE acro_labo acro_labo VARCHAR(100) DEFAULT NULL, CHANGE nom_labo nom_labo VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649E3FC35B');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649E3FC35B FOREIGN KEY (id_individu) REFERENCES individu (id_individu) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE templates (nom VARCHAR(40) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, sujet TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, contenu TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(nom)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA480B6028');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP INDEX adresseip ON adresseip');
        $this->addSql('ALTER TABLE adresseip CHANGE adresse adresse TEXT NOT NULL');
        $this->addSql('ALTER TABLE dar CHANGE id_rallonge id_rallonge VARCHAR(15) DEFAULT NULL');
        $this->addSql('ALTER TABLE dar ADD CONSTRAINT FK_7272F018E3D1DEE5 FOREIGN KEY (id_rallonge) REFERENCES rallonge (id_rallonge) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_7272F018E3D1DEE5 ON dar (id_rallonge)');
        $this->addSql('ALTER TABLE laboratoire DROP numero_national_structure, DROP actif, DROP numero_de_structure_successeur, CHANGE acro_labo acro_labo VARCHAR(100) NOT NULL, CHANGE nom_labo nom_labo VARCHAR(100) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX acro ON laboratoire (acro_labo)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649E3FC35B');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649E3FC35B FOREIGN KEY (id_individu) REFERENCES individu (id_individu) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
