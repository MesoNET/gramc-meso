<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240112083658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX adresseip ON adresseip');
        $this->addSql('CREATE UNIQUE INDEX adresseip ON adresseip (adresse, id_labo)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649E3FC35B FOREIGN KEY (id_individu) REFERENCES individu (id_individu)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64976222944 FOREIGN KEY (id_projet) REFERENCES projet (id_projet)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6495863E191 FOREIGN KEY (id_clessh) REFERENCES clessh (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649A951FAD6 FOREIGN KEY (id_serveur) REFERENCES serveur (nom)');
        $this->addSql('ALTER TABLE version ADD CONSTRAINT FK_BF1CD3C32EE4D7B FOREIGN KEY (maj_ind) REFERENCES individu (id_individu) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE version ADD CONSTRAINT FK_BF1CD3C33143FDD7 FOREIGN KEY (prj_id_thematique) REFERENCES thematique (id_thematique)');
        $this->addSql('ALTER TABLE version ADD CONSTRAINT FK_BF1CD3C376222944 FOREIGN KEY (id_projet) REFERENCES projet (id_projet)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX adresseip ON adresseip');
        $this->addSql('CREATE UNIQUE INDEX adresseip ON adresseip (adresse(44), id_labo)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649E3FC35B');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64976222944');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6495863E191');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649A951FAD6');
        $this->addSql('ALTER TABLE version DROP FOREIGN KEY FK_BF1CD3C32EE4D7B');
        $this->addSql('ALTER TABLE version DROP FOREIGN KEY FK_BF1CD3C33143FDD7');
        $this->addSql('ALTER TABLE version DROP FOREIGN KEY FK_BF1CD3C376222944');
    }
}
