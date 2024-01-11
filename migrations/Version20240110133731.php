<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240110133731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE adresseip (id INT AUTO_INCREMENT NOT NULL, id_labo INT DEFAULT NULL, adresse TINYTEXT NOT NULL, INDEX IDX_B7A04D9718475F5E (id_labo), UNIQUE INDEX adresseip (adresse, id_labo), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE clessh (id INT AUTO_INCREMENT NOT NULL, id_individu INT DEFAULT NULL, nom VARCHAR(20) NOT NULL, pub VARCHAR(5000) NOT NULL, emp VARCHAR(100) NOT NULL, rvk TINYINT(1) NOT NULL, INDEX IDX_7E54547CE3FC35B (id_individu), UNIQUE INDEX nom_individu (id_individu, nom), UNIQUE INDEX pubuniq (emp), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE collaborateurVersion (id INT AUTO_INCREMENT NOT NULL, id_coll_statut SMALLINT DEFAULT NULL, id_version VARCHAR(13) DEFAULT NULL, id_coll_labo INT DEFAULT NULL, id_coll_etab INT DEFAULT NULL, id_collaborateur INT DEFAULT NULL, responsable TINYINT(1) NOT NULL, deleted TINYINT(1) NOT NULL COMMENT \'supprimé prochainement\', INDEX id_coll_labo (id_coll_labo), INDEX id_coll_statut (id_coll_statut), INDEX id_coll_etab (id_coll_etab), INDEX collaborateur_collaborateurprojet_fk (id_collaborateur), INDEX id_version (id_version), UNIQUE INDEX id_version_2 (id_version, id_collaborateur), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dac (id_dac INT AUTO_INCREMENT NOT NULL, id_ressource INT DEFAULT NULL, id_version VARCHAR(13) NOT NULL, demande INT NOT NULL COMMENT \'demande, l\'\'unité est celle de la ressource associée\', attribution INT NOT NULL COMMENT \'attribution, l\'\'unité est celle de la ressource associée\', todof TINYINT(1) NOT NULL, consommation INT NOT NULL COMMENT \'consommation, l\'\'unité est celle de la ressource associée\', INDEX IDX_18C2D0EA13AAF963 (id_ressource), PRIMARY KEY(id_dac)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dar (id_dar INT AUTO_INCREMENT NOT NULL, id_ressource INT DEFAULT NULL, id_rallonge VARCHAR(15) NOT NULL, demande INT NOT NULL COMMENT \'demande, l\'\'unité est celle de la ressource associée\', attribution INT NOT NULL COMMENT \'attribution, l\'\'unité est celle de la ressource associée\', todof TINYINT(1) NOT NULL, INDEX IDX_7272F01813AAF963 (id_ressource), PRIMARY KEY(id_dar)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE etablissement (id_etab INT AUTO_INCREMENT NOT NULL, libelle_etab VARCHAR(50) NOT NULL, PRIMARY KEY(id_etab)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE expertise (id INT AUTO_INCREMENT NOT NULL, id_version VARCHAR(13) DEFAULT NULL, id_rallonge VARCHAR(15) DEFAULT NULL, id_expert INT DEFAULT NULL, validation INT NOT NULL, commentaire_interne TEXT DEFAULT NULL, commentaire_externe TEXT DEFAULT NULL, definitif TINYINT(1) NOT NULL, INDEX IDX_229ADF8BE3D1DEE5 (id_rallonge), INDEX version_expertise_fk (id_version), INDEX expert_expertise_fk (id_expert), INDEX id_version (id_version), INDEX id_expert (id_expert), UNIQUE INDEX id_version_2 (id_version, id_expert), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE formation (id INT AUTO_INCREMENT NOT NULL, numero_form INT DEFAULT NULL, acro_form VARCHAR(15) DEFAULT NULL, nom_form VARCHAR(100) DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE formationVersion (id INT AUTO_INCREMENT NOT NULL, id_version VARCHAR(13) DEFAULT NULL, id_formation INT DEFAULT NULL, nombre INT NOT NULL, INDEX id_formation (id_formation), INDEX id_version (id_version), UNIQUE INDEX id_version2 (id_version, id_formation), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE individu (id_individu INT AUTO_INCREMENT NOT NULL, id_statut SMALLINT DEFAULT NULL, id_labo INT DEFAULT NULL, id_etab INT DEFAULT NULL, creation_stamp DATETIME NOT NULL, nom VARCHAR(50) DEFAULT NULL, prenom VARCHAR(50) DEFAULT NULL, mail VARCHAR(200) NOT NULL, admin TINYINT(1) NOT NULL, sysadmin TINYINT(1) NOT NULL, obs TINYINT(1) NOT NULL, expert TINYINT(1) NOT NULL, valideur TINYINT(1) NOT NULL, president TINYINT(1) NOT NULL, desactive TINYINT(1) NOT NULL, INDEX id_labo (id_labo), INDEX id_statut (id_statut), INDEX id_etab (id_etab), UNIQUE INDEX mail (mail), PRIMARY KEY(id_individu)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invitation (id_invitation INT AUTO_INCREMENT NOT NULL, id_inviting INT DEFAULT NULL, id_invited INT DEFAULT NULL, clef VARCHAR(50) NOT NULL, creation_stamp DATETIME NOT NULL, INDEX IDX_F11D61A2E37E3182 (id_inviting), INDEX IDX_F11D61A25E3046DB (id_invited), UNIQUE INDEX clef (clef), UNIQUE INDEX invit (id_inviting, id_invited), PRIMARY KEY(id_invitation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE journal (id INT AUTO_INCREMENT NOT NULL, individu INT DEFAULT NULL, gramc_sess_id VARCHAR(40) DEFAULT NULL, type VARCHAR(15) NOT NULL, message VARCHAR(300) NOT NULL, stamp DATETIME NOT NULL, ip VARCHAR(40) NOT NULL, niveau INT NOT NULL, INDEX IDX_C1A7E74D5EE42FCE (individu), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE laboratoire (id_labo INT AUTO_INCREMENT NOT NULL, numero_labo INT NOT NULL, acro_labo VARCHAR(100) NOT NULL, nom_labo VARCHAR(100) NOT NULL, UNIQUE INDEX acro (acro_labo), PRIMARY KEY(id_labo)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE param (id_param INT AUTO_INCREMENT NOT NULL, cle VARCHAR(32) NOT NULL, val VARCHAR(128) NOT NULL, UNIQUE INDEX UNIQ_A4FA7C8941401D17 (cle), PRIMARY KEY(id_param)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE projet (id_projet VARCHAR(10) NOT NULL, id_veract VARCHAR(13) DEFAULT NULL, id_verder VARCHAR(13) DEFAULT NULL, etat_projet INT NOT NULL, type_projet INT NOT NULL, limit_date DATETIME DEFAULT NULL, tetat_projet INT DEFAULT NULL, UNIQUE INDEX UNIQ_50159CA98AC746EE (id_veract), UNIQUE INDEX UNIQ_50159CA9333586B6 (id_verder), INDEX etat_projet (etat_projet), PRIMARY KEY(id_projet)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE publicationProjet (id_projet VARCHAR(10) NOT NULL, id_publi INT NOT NULL, INDEX IDX_385F338F76222944 (id_projet), INDEX IDX_385F338F3BE1E455 (id_publi), PRIMARY KEY(id_projet, id_publi)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE publication (id_publi INT AUTO_INCREMENT NOT NULL, refbib TEXT NOT NULL, doi VARCHAR(100) DEFAULT NULL, open_url VARCHAR(300) DEFAULT NULL, annee INT NOT NULL, PRIMARY KEY(id_publi)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rallonge (id_rallonge VARCHAR(15) NOT NULL, id_version VARCHAR(13) DEFAULT NULL, id_expert INT DEFAULT NULL, etat_rallonge INT NOT NULL, prj_justif_rallonge TEXT DEFAULT NULL, commentaire_interne TEXT DEFAULT NULL, commentaire_externe TEXT DEFAULT NULL, validation TINYINT(1) NOT NULL, INDEX IDX_B30A3270692C26AF (id_expert), INDEX id_version (id_version), INDEX num_rallonge (id_rallonge), INDEX etat_rallonge (etat_rallonge), PRIMARY KEY(id_rallonge)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rapportActivite (id INT AUTO_INCREMENT NOT NULL, id_projet VARCHAR(10) DEFAULT NULL, annee INT NOT NULL, nom_fichier VARCHAR(100) DEFAULT NULL, taille INT NOT NULL, INDEX id_projet (id_projet), UNIQUE INDEX id_projet_2 (id_projet, annee), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ressource (id_ressource INT AUTO_INCREMENT NOT NULL, id_serveur VARCHAR(20) DEFAULT NULL, nom VARCHAR(8) DEFAULT NULL COMMENT \'optionnel, voir la fonction ServiceRessources::getNomComplet\', descr VARCHAR(2000) DEFAULT NULL, doc_url VARCHAR(200) DEFAULT NULL, unite VARCHAR(20) DEFAULT NULL COMMENT \'unité utilisée pour les allocations\', max_dem INT DEFAULT NULL COMMENT \'Valeur max qu\'\'on a le droit de demander\', co2 INT DEFAULT NULL COMMENT \'gramme de co2 émis par unite et par heure\', INDEX IDX_939F4544A951FAD6 (id_serveur), INDEX nom (nom), PRIMARY KEY(id_ressource)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE serveur (nom VARCHAR(20) NOT NULL, descr VARCHAR(200) DEFAULT \'\', cgu_url VARCHAR(200) DEFAULT NULL, admname VARCHAR(20) DEFAULT NULL COMMENT \'username symfony pour l\'\'api\', UNIQUE INDEX admname (admname), PRIMARY KEY(nom)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sso (eppn VARCHAR(200) NOT NULL, id_individu INT DEFAULT NULL, INDEX id_individu (id_individu), PRIMARY KEY(eppn)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE statut (id_statut SMALLINT NOT NULL, libelle_statut VARCHAR(50) NOT NULL, permanent TINYINT(1) NOT NULL, INDEX id_statut (id_statut), PRIMARY KEY(id_statut)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE thematique (id_thematique INT AUTO_INCREMENT NOT NULL, libelle_thematique VARCHAR(200) NOT NULL, PRIMARY KEY(id_thematique)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE thematiqueExpert (id_thematique INT NOT NULL, id_expert INT NOT NULL, INDEX IDX_D89754909F04557F (id_thematique), INDEX IDX_D8975490692C26AF (id_expert), PRIMARY KEY(id_thematique, id_expert)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, id_serveur VARCHAR(20) DEFAULT NULL, id_individu INT DEFAULT NULL, id_projet VARCHAR(10) DEFAULT NULL, id_clessh INT DEFAULT NULL, loginname VARCHAR(20) DEFAULT NULL, login TINYINT(1) NOT NULL COMMENT \'login sur le serveur lié\', password VARCHAR(200) DEFAULT NULL, cpassword VARCHAR(200) DEFAULT NULL, expire TINYINT(1) DEFAULT NULL, pass_expiration DATETIME DEFAULT NULL, cgu TINYINT(1) NOT NULL, deply TINYINT(1) NOT NULL, INDEX IDX_8D93D649A951FAD6 (id_serveur), INDEX IDX_8D93D649E3FC35B (id_individu), INDEX IDX_8D93D64976222944 (id_projet), INDEX IDX_8D93D6495863E191 (id_clessh), UNIQUE INDEX loginname (id_serveur, loginname), UNIQUE INDEX i_p_s (id_individu, id_projet, id_serveur), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE version (id_version VARCHAR(13) NOT NULL, maj_ind INT DEFAULT NULL, prj_id_thematique INT DEFAULT NULL, id_projet VARCHAR(10) DEFAULT NULL, etat_version INT DEFAULT NULL, type_version INT DEFAULT NULL COMMENT \'type du projet associé (le type du projet peut changer)\', prj_l_labo VARCHAR(300) DEFAULT NULL, prj_titre VARCHAR(500) DEFAULT NULL, prj_financement VARCHAR(100) DEFAULT NULL, prj_genci_machines VARCHAR(60) DEFAULT NULL, prj_genci_centre VARCHAR(60) DEFAULT NULL, prj_genci_heures VARCHAR(30) DEFAULT NULL, prj_expose LONGTEXT DEFAULT NULL, prj_justif_renouv LONGTEXT DEFAULT NULL, prj_fiche_val TINYINT(1) DEFAULT NULL, prj_genci_dari VARCHAR(15) DEFAULT NULL, code_nom VARCHAR(150) DEFAULT NULL, code_licence TEXT DEFAULT NULL, libelle_thematique VARCHAR(200) DEFAULT NULL, maj_stamp DATETIME DEFAULT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, limit_date DATETIME DEFAULT NULL, prj_fiche_len INT DEFAULT NULL, cgu TINYINT(1) DEFAULT NULL, nb_version VARCHAR(5) NOT NULL COMMENT \'Numéro de version (01,02,03,...)\', INDEX IDX_BF1CD3C32EE4D7B (maj_ind), INDEX etat_version (etat_version), INDEX id_projet (id_projet), INDEX prj_id_thematique (prj_id_thematique), PRIMARY KEY(id_version)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE adresseip ADD CONSTRAINT FK_B7A04D9718475F5E FOREIGN KEY (id_labo) REFERENCES laboratoire (id_labo)');
        $this->addSql('ALTER TABLE clessh ADD CONSTRAINT FK_7E54547CE3FC35B FOREIGN KEY (id_individu) REFERENCES individu (id_individu)');
        $this->addSql('ALTER TABLE collaborateurVersion ADD CONSTRAINT FK_B49A55AD2667DCD9 FOREIGN KEY (id_coll_statut) REFERENCES statut (id_statut)');
        $this->addSql('ALTER TABLE collaborateurVersion ADD CONSTRAINT FK_B49A55AD61817AB3 FOREIGN KEY (id_version) REFERENCES version (id_version) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collaborateurVersion ADD CONSTRAINT FK_B49A55AD31598A15 FOREIGN KEY (id_coll_labo) REFERENCES laboratoire (id_labo)');
        $this->addSql('ALTER TABLE collaborateurVersion ADD CONSTRAINT FK_B49A55AD3208B7A FOREIGN KEY (id_coll_etab) REFERENCES etablissement (id_etab)');
        $this->addSql('ALTER TABLE collaborateurVersion ADD CONSTRAINT FK_B49A55ADC9246DB6 FOREIGN KEY (id_collaborateur) REFERENCES individu (id_individu)');
        $this->addSql('ALTER TABLE dac ADD CONSTRAINT FK_18C2D0EA13AAF963 FOREIGN KEY (id_ressource) REFERENCES ressource (id_ressource)');
        $this->addSql('ALTER TABLE dar ADD CONSTRAINT FK_7272F01813AAF963 FOREIGN KEY (id_ressource) REFERENCES ressource (id_ressource)');
        $this->addSql('ALTER TABLE expertise ADD CONSTRAINT FK_229ADF8B61817AB3 FOREIGN KEY (id_version) REFERENCES version (id_version)');
        $this->addSql('ALTER TABLE expertise ADD CONSTRAINT FK_229ADF8BE3D1DEE5 FOREIGN KEY (id_rallonge) REFERENCES rallonge (id_rallonge)');
        $this->addSql('ALTER TABLE expertise ADD CONSTRAINT FK_229ADF8B692C26AF FOREIGN KEY (id_expert) REFERENCES individu (id_individu)');
        $this->addSql('ALTER TABLE formationVersion ADD CONSTRAINT FK_A98B03F161817AB3 FOREIGN KEY (id_version) REFERENCES version (id_version)');
        $this->addSql('ALTER TABLE formationVersion ADD CONSTRAINT FK_A98B03F1C0759D98 FOREIGN KEY (id_formation) REFERENCES formation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE individu ADD CONSTRAINT FK_5EE42FCEC3534552 FOREIGN KEY (id_statut) REFERENCES statut (id_statut)');
        $this->addSql('ALTER TABLE individu ADD CONSTRAINT FK_5EE42FCE18475F5E FOREIGN KEY (id_labo) REFERENCES laboratoire (id_labo)');
        $this->addSql('ALTER TABLE individu ADD CONSTRAINT FK_5EE42FCE2A3E5E31 FOREIGN KEY (id_etab) REFERENCES etablissement (id_etab)');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A2E37E3182 FOREIGN KEY (id_inviting) REFERENCES individu (id_individu)');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A25E3046DB FOREIGN KEY (id_invited) REFERENCES individu (id_individu)');
        $this->addSql('ALTER TABLE journal ADD CONSTRAINT FK_C1A7E74D5EE42FCE FOREIGN KEY (individu) REFERENCES individu (id_individu) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA98AC746EE FOREIGN KEY (id_veract) REFERENCES version (id_version) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA9333586B6 FOREIGN KEY (id_verder) REFERENCES version (id_version) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE publicationProjet ADD CONSTRAINT FK_385F338F76222944 FOREIGN KEY (id_projet) REFERENCES projet (id_projet)');
        $this->addSql('ALTER TABLE publicationProjet ADD CONSTRAINT FK_385F338F3BE1E455 FOREIGN KEY (id_publi) REFERENCES publication (id_publi)');
        $this->addSql('ALTER TABLE rallonge ADD CONSTRAINT FK_B30A327061817AB3 FOREIGN KEY (id_version) REFERENCES version (id_version)');
        $this->addSql('ALTER TABLE rallonge ADD CONSTRAINT FK_B30A3270692C26AF FOREIGN KEY (id_expert) REFERENCES individu (id_individu)');
        $this->addSql('ALTER TABLE rapportActivite ADD CONSTRAINT FK_4E9BB65D76222944 FOREIGN KEY (id_projet) REFERENCES projet (id_projet)');
        $this->addSql('ALTER TABLE ressource ADD CONSTRAINT FK_939F4544A951FAD6 FOREIGN KEY (id_serveur) REFERENCES serveur (nom)');
        $this->addSql('ALTER TABLE sso ADD CONSTRAINT FK_70E959E7E3FC35B FOREIGN KEY (id_individu) REFERENCES individu (id_individu)');
        $this->addSql('ALTER TABLE thematiqueExpert ADD CONSTRAINT FK_D89754909F04557F FOREIGN KEY (id_thematique) REFERENCES thematique (id_thematique)');
        $this->addSql('ALTER TABLE thematiqueExpert ADD CONSTRAINT FK_D8975490692C26AF FOREIGN KEY (id_expert) REFERENCES individu (id_individu)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649A951FAD6 FOREIGN KEY (id_serveur) REFERENCES serveur (nom)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649E3FC35B FOREIGN KEY (id_individu) REFERENCES individu (id_individu)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64976222944 FOREIGN KEY (id_projet) REFERENCES projet (id_projet)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6495863E191 FOREIGN KEY (id_clessh) REFERENCES clessh (id)');
        $this->addSql('ALTER TABLE version ADD CONSTRAINT FK_BF1CD3C32EE4D7B FOREIGN KEY (maj_ind) REFERENCES individu (id_individu) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE version ADD CONSTRAINT FK_BF1CD3C33143FDD7 FOREIGN KEY (prj_id_thematique) REFERENCES thematique (id_thematique)');
        $this->addSql('ALTER TABLE version ADD CONSTRAINT FK_BF1CD3C376222944 FOREIGN KEY (id_projet) REFERENCES projet (id_projet)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE adresseip DROP FOREIGN KEY FK_B7A04D9718475F5E');
        $this->addSql('ALTER TABLE clessh DROP FOREIGN KEY FK_7E54547CE3FC35B');
        $this->addSql('ALTER TABLE collaborateurVersion DROP FOREIGN KEY FK_B49A55AD2667DCD9');
        $this->addSql('ALTER TABLE collaborateurVersion DROP FOREIGN KEY FK_B49A55AD61817AB3');
        $this->addSql('ALTER TABLE collaborateurVersion DROP FOREIGN KEY FK_B49A55AD31598A15');
        $this->addSql('ALTER TABLE collaborateurVersion DROP FOREIGN KEY FK_B49A55AD3208B7A');
        $this->addSql('ALTER TABLE collaborateurVersion DROP FOREIGN KEY FK_B49A55ADC9246DB6');
        $this->addSql('ALTER TABLE dac DROP FOREIGN KEY FK_18C2D0EA13AAF963');
        $this->addSql('ALTER TABLE dar DROP FOREIGN KEY FK_7272F01813AAF963');
        $this->addSql('ALTER TABLE expertise DROP FOREIGN KEY FK_229ADF8B61817AB3');
        $this->addSql('ALTER TABLE expertise DROP FOREIGN KEY FK_229ADF8BE3D1DEE5');
        $this->addSql('ALTER TABLE expertise DROP FOREIGN KEY FK_229ADF8B692C26AF');
        $this->addSql('ALTER TABLE formationVersion DROP FOREIGN KEY FK_A98B03F161817AB3');
        $this->addSql('ALTER TABLE formationVersion DROP FOREIGN KEY FK_A98B03F1C0759D98');
        $this->addSql('ALTER TABLE individu DROP FOREIGN KEY FK_5EE42FCEC3534552');
        $this->addSql('ALTER TABLE individu DROP FOREIGN KEY FK_5EE42FCE18475F5E');
        $this->addSql('ALTER TABLE individu DROP FOREIGN KEY FK_5EE42FCE2A3E5E31');
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A2E37E3182');
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A25E3046DB');
        $this->addSql('ALTER TABLE journal DROP FOREIGN KEY FK_C1A7E74D5EE42FCE');
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY FK_50159CA98AC746EE');
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY FK_50159CA9333586B6');
        $this->addSql('ALTER TABLE publicationProjet DROP FOREIGN KEY FK_385F338F76222944');
        $this->addSql('ALTER TABLE publicationProjet DROP FOREIGN KEY FK_385F338F3BE1E455');
        $this->addSql('ALTER TABLE rallonge DROP FOREIGN KEY FK_B30A327061817AB3');
        $this->addSql('ALTER TABLE rallonge DROP FOREIGN KEY FK_B30A3270692C26AF');
        $this->addSql('ALTER TABLE rapportActivite DROP FOREIGN KEY FK_4E9BB65D76222944');
        $this->addSql('ALTER TABLE ressource DROP FOREIGN KEY FK_939F4544A951FAD6');
        $this->addSql('ALTER TABLE sso DROP FOREIGN KEY FK_70E959E7E3FC35B');
        $this->addSql('ALTER TABLE thematiqueExpert DROP FOREIGN KEY FK_D89754909F04557F');
        $this->addSql('ALTER TABLE thematiqueExpert DROP FOREIGN KEY FK_D8975490692C26AF');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649A951FAD6');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649E3FC35B');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64976222944');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6495863E191');
        $this->addSql('ALTER TABLE version DROP FOREIGN KEY FK_BF1CD3C32EE4D7B');
        $this->addSql('ALTER TABLE version DROP FOREIGN KEY FK_BF1CD3C33143FDD7');
        $this->addSql('ALTER TABLE version DROP FOREIGN KEY FK_BF1CD3C376222944');
        $this->addSql('DROP TABLE adresseip');
        $this->addSql('DROP TABLE clessh');
        $this->addSql('DROP TABLE collaborateurVersion');
        $this->addSql('DROP TABLE dac');
        $this->addSql('DROP TABLE dar');
        $this->addSql('DROP TABLE etablissement');
        $this->addSql('DROP TABLE expertise');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE formationVersion');
        $this->addSql('DROP TABLE individu');
        $this->addSql('DROP TABLE invitation');
        $this->addSql('DROP TABLE journal');
        $this->addSql('DROP TABLE laboratoire');
        $this->addSql('DROP TABLE param');
        $this->addSql('DROP TABLE projet');
        $this->addSql('DROP TABLE publicationProjet');
        $this->addSql('DROP TABLE publication');
        $this->addSql('DROP TABLE rallonge');
        $this->addSql('DROP TABLE rapportActivite');
        $this->addSql('DROP TABLE ressource');
        $this->addSql('DROP TABLE serveur');
        $this->addSql('DROP TABLE sso');
        $this->addSql('DROP TABLE statut');
        $this->addSql('DROP TABLE thematique');
        $this->addSql('DROP TABLE thematiqueExpert');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE version');
    }
}
