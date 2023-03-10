<?php
/**
 * This file is part of GRAMC (Computing Ressource Granting Software)
 * GRAMC stands for : Gestion des Ressources et de leurs Attributions pour Mésocentre de Calcul
 *
 * GRAMC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 *  GRAMC is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with GRAMC.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  authors : Miloslav Grundmann - C.N.R.S. - UMS 3667 - CALMIP
 *            Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

init();


if ( $argc != 2) usage();

$file_name = $argv[1];

echo "Efface la BD\n";
efface_bd();

echo "recrée la BD et remplit à partir de ".$file_name."\n";
restore_database($file_name);

# Env dev = On ne modifie plus les adresses mail car symfony enverra tout à l'utilisateur
#           marqué dans le paramètre recipients, fichier config/packages/dev/mailer.yaml
#           On finit par l'envoi d'un mail à une adresse bidon, pour vérifier
#
# Env prod= Symfony envoie tout à l'utilisateur lui-même
#           CEPENDANT pour tester l'application en environnement prod il peut être nécessaire
#           d'utiliser le même stratagème en mode prod
#           Pour cela:
#
#                      cp -a config/packages/dev/mailer.yaml config/packages/prod
#
#           Pour savoir dans quelle configuration on se trouve, ce script essaie d'envoyer un mail à toto@exemple.fr
#           Si on ne le reçoit pas, MEFIANCE !

echo "Appelle bin/console de Symfony\n";
console_update();

echo "Appelle /bin/console app:send-a-mail toto@exemple.fr, vérifiez que vous l'avez bien reçu !";
envoie_mail_de_controle();

echo "That's REALLY all Folks\n";


/*************************************************************
 * Initialise certaines constantes a partir de parameters.yml
 *************************************************************/
 function init() {

	$db_url = parse_url(getenv('DATABASE_URL'));
	
	if (!isset($db_url["scheme"]) || $db_url['scheme'] != "mysql")
	{
		echo "ERREUR - scheme doit être mysql - DATABASE_URL=".getenv('DATABASE_URL')."\n";
		exit;
	}
	
	# On suppose que la DATABASE_URL est correcte !
    define('DATABASE_HOST', $db_url['host']);
    define('DATABASE_USER', $db_url['user']);
    define('DATABASE_PASSWORD', $db_url['pass']);
    define('DATABASE_NAME', explode ('/',$db_url['path'])[1]);
    //define('MAIL_DEVT', "devt1@exemple.com");
     
    //echo DATABASE_HOST." ".DATABASE_USER." ".DATABASE_PASSWORD." ".DATABASE_NAME."\n";
    //exit;
 }




/**********
 * Appelle bin/console pour recréer le schéma de B.D. avec Symfony *
 **************/
function console()
{
    $cmd = 'cd ..; bin/console doctrine:schema:create';
    passthru($cmd);
}

/**********
 * Appelle bin/console pour modifier le schéma de B.D. si nécessaire *
 **************/
function console_update()
{
    $cmd = 'cd ..; bin/console doctrine:schema:update --dump-sql';
    passthru($cmd);
    $cmd = 'cd ..; bin/console doctrine:schema:update --force';
    passthru($cmd);
}

/**********
 * Appeller bin/console fixtures avec Symfony
 *
 **************/
function fixtures()
{
    $cmd = 'cd ..;bin/console doctrine:fixtures:load  --append';
    passthru($cmd);
}

/**********
 * Appeller bin/console pour envoyer un mail de controle
 *
 **************/
function envoie_mail_de_controle()
{
    $cmd = 'cd ..;bin/console app:send-a-mail toto@exemple.fr';
    passthru($cmd);
}

/**********
 * @brief Remplir la BD à partir du fichier créé précédemment par mysqldump
 *        On commence par désactiver les FOREIGN KEY sinon ça ne marche pas
 *
 * @param $file Le fichier (temporaire) créé par mysldump
 *
 **************/
function remplit_bd($file)
{
    $data = "SET FOREIGN_KEY_CHECKS=0;\n";
    $data .= file_get_contents($file);
    $data .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
    file_put_contents($file,$data);
    $cmd = 'mysql --user '.DATABASE_USER.' --password='.DATABASE_PASSWORD.' '.DATABASE_NAME.' < '.$file;
    passthru($cmd);
}

/*****
 * @brief Sauver seulement les data dans un fichier temporaire
 *
 * @return Le nom du fichier temporaire
 *
 *****************/
function mysqlDump()
{
    $dumpName = tempnam('/tmp','gramc-data');
    $cmd = 'mysqldump --complete-insert --no-create-info --user '.DATABASE_USER.' --password='.DATABASE_PASSWORD.' '.DATABASE_NAME.' > '.$dumpName;
    passthru($cmd);
    return $dumpName;
}

/**
 * @brief Mettre à jour la BD en utilisant majdb.php
 *
 **************/
function majbd()
{
    passthru('php majbd.php');
}

/**
 * requete_mysql_1: Appelle la requête passée en paramètres en travaillant proprement
 *                  Renvoie le résultat
 *
 ****/
function requete_mysql_1($sql) {
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);

    /* check connection */
    if ($mysqli->connect_errno) {
        printf("Connect failed: %s\n", $mysqli->connect_error);
        exit();
    }

    if ($mysqli->real_query($sql)) {
        $res = $mysqli->store_result();
        return $res;
    } else {
        return [];
    }
}

/**
 * requete_mysql: Appelle la requête passée en paramètres
 *                La reformatte en tant que tableau de tableaux
 *                Renvoie le résultat
 *
 ****/
function requete_mysql($sql) {
    $cmd    = appelle_mysql($sql);
    $result = explode("\n",`$cmd`);
    $data   = [];
    foreach ($result as $r) {
        if ($r=="") continue;
        $l = preg_split( '/\s+/', $r);
        array_push($data,$l);
    }
    return $data;
}

/**
 * requete_mysql_from_file: le sql est dans le fichier dont le nom est passé en paramètres
 *
 * return: rien, on utilise plutôt pour des INSERT ou UPDATE
 *
 ***********/
function requete_mysql_from_file($f) {
    $cmd    = appelle_mysql();
    $cmd .= " < $f";
    system($cmd);
}

/**
 * replace_sql = Remplace les ?1, ?2 etc. trouvées dans $sql. par les cellules du tableau $values
 *
 * $sql = Le sql à modfier
 * $values = Un tableau de valeurs
 * return = Le sql modifié
 *
 ****/
function replace_sql($sql,$values) {
    $i = 1;
    foreach ($values as $v) {
        $pattern='?'.$i;
        $sql = str_replace($pattern, "'".$v."'", $sql);
        $i++;
    }
    return $sql;
}

/**
 * @brief Modifier tous les mail de la table individu: toto@titi.fr ==> devt1+gramc_toto_titi.fr@exemple.com
 *        (cf. parameters.yml.dist)
 *        tous les mails arriveront chez devt1@exemple.com
 *
 */
function modif_mail() {

    $a_maildevt = explode('@',MAIL_DEVT,2);

    // Lecture de individus
    $individus = requete_mysql_1("SELECT id_individu,mail,admin FROM individu");

    // écriture du mail modifié
    $ecriture = "UPDATE individu SET mail=?1 WHERE mail=?2";

    // écriture de l'eppn pour debug
    $ecriture_eppn = "UPDATE sso SET eppn=?1 WHERE id_individu=?2 LIMIT 1";

    // modification du mail - Aussi modification de l'eppn pour les admins
    // ATTENTION - On ne peut pas recharger une B.D. datant d'avant mai 2016.
    //             cf. les anciennes versions pour ça (par exemple release d49fca447ce258822c52dccc41abe5c7817c6b83)

    $f_sql = "/tmp/tmp.sql";
    $fh_sql = fopen($f_sql,"w");
    if ($fh_sql==false)
    {
		echo "ERREUR - Ne peut pas créer le fichier temporaire $f_sql\n";
		exit(1);
	}

    while ($i = $individus->fetch_row()) {
        $mail = $i[1];
       // echo '#'.$i[1].'#'."\n";

        $new_mail = str_replace('@','_',$mail);
        $new_mail = $a_maildevt[0] . '+gramc_' . $new_mail . '@' . $a_maildevt[1];

        fwrite($fh_sql,replace_sql($ecriture,[$new_mail,$mail]).";\n");
        // Pour un admin
        if ($i[2] != 0) {
            $eppn = 'eppn.'.$new_mail;
            $id_individu = $i[0];
            fwrite($fh_sql,replace_sql($ecriture_eppn,[$eppn,$id_individu]).";\n");
        }
    }
    fclose($fh_sql);
    requete_mysql_from_file($f_sql);
    unlink ($f_sql);
}

function usage() {
    global $argv;
    echo "Usage: $argv[0] file.sql\n";
    exit(1);
}

/**
 * @brief restore la base de données depuis un fichier
 *
 * @param $file_name
 *
 */
function restore_database($file_name) {
    $cmd = appelle_mysql();
    $cmd .= ' < ';
    $cmd .= $file_name;
    system($cmd);
}

/**
 * @brief Appel mysql avec les user/passwd/db qui vont bien
 *
 * params: $sql commandes sql (optionnelles) à envoyer à mysql
 * return: Une string prête à être exécutée par system() ou `` ou autre.
 */
function appelle_mysql($sql="") {
    if ($sql != "") {
        $cmd = "echo $sql | ";
    } else {
        $cmd = "";
    }
    $cmd .= 'mysql -N -s --user ';
    $cmd .= DATABASE_USER;
    $cmd .= ' --password=';
    $cmd .= '"' . DATABASE_PASSWORD . '"';
    $cmd .= ' ';
    $cmd .= DATABASE_NAME;
    return $cmd;
}

/**
 * @brief Efface toutes les tables de la base de données
 *
 */
function efface_bd() {
    $cmd = "echo 'SHOW TABLES;' | ";
    $cmd .= appelle_mysql();
    $a_tables=explode("\n",`$cmd`);
    array_pop($a_tables); // retirer le dernier élément (vide)
    $tables = implode(',',$a_tables);
    if ($tables == "") {
        echo "Aucune table à supprimer\n";
    } else {
        $cmd = "echo 'SET FOREIGN_KEY_CHECKS = 0; DROP TABLE $tables; SET FOREIGN_KEY_CHECKS = 1' | " . appelle_mysql();
        system($cmd);
    }
};
