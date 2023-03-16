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
 *  authors : Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

// src/Command/NettCompta.php

/***************************
 *
 * Mars 2023 - On vient d'ajouter les structures de données Ressource et Serveur 
 *
 **************************************************/

namespace App\Command;

use App\GramcServices\GramcDate;
use App\GramcServices\ServiceProjets;
use App\GramcServices\ServiceVersions;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceUsers;
use App\GramcServices\ServiceServeurs;

use App\Entity\Projet;
use App\Entity\Version;
use App\Entity\Serveur;
use App\Entity\Ressource;
use App\Entity\CollaborateurVersion;

use App\Entity\Dac;
use App\Entity\User;

use App\Utils\Etat;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;

// the name of the command (the part after "bin/console")
#[AsCommand( name: 'app:initressources', )]
class InitRessources extends Command
{

    public function __construct(private ServiceJournal $sj, private ServiceUsers $su, private ServiceServeurs $sr, private EntityManagerInterface $em)
    {
        // best practices recommend to call the parent constructor first and
        // then set your own properties. That wouldn't work in this case
        // because configure() needs the properties set in this constructor
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Initialiser deux ressources pour Turpan et Boréale');
        $this->setHelp('');
    }

    private function remove_user(int $id) : void
    {
        $em = $this->em;
        $u_a_jeter = $em->getRepository(user::class)->findBy(["id" => $id]);
        if (count($u_a_jeter)==1)
        {
            echo "Suppression du user $id\n";
            $em->remove($u_a_jeter[0]);
            $em->flush();
        }
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        // this method must return an integer number with the "exit status code"
        // of the command.

        // return this if there was no problem running the command
        $em = $this->em;
        $su = $this->su;
        $sj = $this->sj;

        // PB AVEC LA BD ACTUELLE !
        $this->remove_user(18);
        $this->remove_user(14);
        $this->remove_user(16);

        // On crée deux ressources: TURPAN et BOREALE

        // Les serveurs
        $s_boreale = $em->getRepository(Serveur::class)->findOneBy(['nom' => 'BOREALE']);
        $s_turpan = $em->getRepository(Serveur::class)->findOneBy(['nom' => 'TURPAN']);

        // Les ressources
        $r_boreale = new Ressource();
        $r_boreale->setServeur($s_boreale);
        $r_boreale->setNom('BOREALE');
        $r_boreale->setDesc("L’intérêt de la machine Boreale réside dans l’utilisation de ses cartes vectorielles NEC SX-Aurora TSUBASA (Vector Engine). Boréale dispose de 9 noeuds de calcul biprocesseurs x86 dotés chacun de 8 Vector Engines. Plus d’informations sur la documentation technique du calculateur: https://services.criann.fr/services/hpc/mesonet-project/.\nLe nombre d’heures que vous demandez correspond au nombre d’heures d’utilisation des Vector Engines.\nExemple: 1 heure sur 4 VE comptera pour 4 heures.");
        $r_boreale->setDocUrl("https://services.criann.fr/services/hpc/mesonet-project/");
        $r_boreale->setUnite('h');
        $r_boreale->setMaxDem('1000000');
        $em->persist($r_boreale);

        $r_turpan = new Ressource();
        $r_turpan->setServeur($s_turpan);
        $r_turpan->setNom('TURPAN');
        $r_turpan->setDesc("Cette machine comprend 15 Nœuds de calcul monoprocesseurs ARM Ampere de 80 coeurs et deux GPUs Nvidia Ampere (soit 1200 coeurs et 30 GPUs)\n\nEgalement disponible un noeud de visualisation Intel x86\n\nLe stockage est constitué de 10 SSD de 3.8Tb et 60 disques durs de 8Tb");
        $r_turpan->setDocUrl("");
        $r_turpan->setUnite('h');
        $r_turpan->setMaxDem('2000000');
        $em->persist($r_turpan);
        $em->flush();


        $versions = $em->getRepository(Version::class)->findAll();
        foreach ($versions as $v)
        {
            $d_boreale = new Dac();
            $d_boreale->setRessource($r_boreale);
            $d_boreale->setVersion($v);
            $d_boreale->setDemande($v->getDemHeuresCriann());
            $d_boreale->setAttribution($v->getAttrHeuresCriann());
            $d_boreale->setConsommation(0);
            $em->persist($d_boreale);

            $d_turpan = new Dac();
            $d_turpan->setRessource($r_turpan);
            $d_turpan->setVersion($v);
            $d_turpan->setDemande($v->getDemHeuresUft());
            $d_turpan->setAttribution($v->getAttrHeuresUft());
            $d_turpan->setConsommation(0);
            $em->persist($d_turpan);
            $em->flush();

            foreach ($v->getCollaborateurVersion() as $cv)
            {
                $ub = $su->getUser($cv->getCollaborateur(),$v->getProjet(),$s_boreale);
                $ub->setLogin($cv->getLoginb());
                $ut = $su->getUser($cv->getCollaborateur(),$v->getProjet(),$s_turpan);
                $ut->setLogin($cv->getLogint());
                $em->flush();
            }
        }

        // nb de cv avec demande de compte sur turpan/boreale:
        $tmp = $em->getRepository(CollaborateurVersion::class)->findBy( [ 'logint' => 1 ]);
        $nb_cv_logint = count($tmp);

        $tmp = $em->getRepository(CollaborateurVersion::class)->findBy( [ 'loginb' => 1 ]);
        $nb_cv_loginb = count($tmp);

        // nb de user avec demande de login turpan/boreale:
        $tmp = $em->getRepository(User::class)->findBy( [ 'login' => 1,'serveur' => $s_turpan ] );
        $nb_user_login_turpan = count($tmp);

        $tmp = $em->getRepository(User::class)->findBy( [ 'login' => 1,'serveur' => $s_boreale ]);
        $nb_user_login_boreale = count($tmp);
        
        echo "VERIFICATIONS\n";
        echo "nb_cv_logint          = $nb_cv_logint\n";
        echo "nb_user_login_turpan  = $nb_user_login_turpan\n";
        echo "nb_cv_loginb          = $nb_cv_loginb\n";
        echo "nb_user_login_boreale = $nb_user_login_boreale\n";
            
        return 0;
    }
}
