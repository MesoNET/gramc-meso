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

namespace App\GramcServices;

use App\Entity\CollaborateurVersion;
use App\Entity\User;
use App\Utils\Functions;

use Doctrine\ORM\EntityManagerInterface;

/*************************************************************
 * Quelques fonctions utiles pour la table User
 *
 *************************************************************/
class ServiceUsers
{
    public function __construct(private EntityManagerInterface $em)
    {}

    /******************************************
     * Renvoie la liste des loginnames pour un CollaborateurVersion donné
     * sous forme de tableau associatif:
     *      $s['TURPAN']['nom'] -> le nom de login (toto), ou 'nologin'
     *                             si $long=true, renvoie toto@TURPAN
     *      $s['TURPAN']['clessh'] -> la cléssh, ou null
     *      $s['TURPAN']['userid] -> le id du user
     *      $s['TURPAN']['deploy'] -> le flag deply (clé déployée ou pas)
     *************************************/
    public function collaborateurVersion2LoginNames(CollaborateurVersion $cv, bool $long = false): array
    {
        $em = $this->em;
        
        $users = $cv->getUser();
        $loginnames3 = [];
        foreach ( $users as $u)
        {
            $s = $u->getServeur()->getNom();
            $loginnames3[$s]['nom'] = $u->getLoginname() ? $u->getLoginname() : 'nologin';
            $loginnames3[$s]['login'] = $u->getLogin() ? 'true' : 'false';
            if ($long) $loginnames3[$s]['nom'] .= '@'.$s;
            $clessh = $u->getClessh();
            if ($clessh === null)
            {
                $loginnames3[$s]['clessh'] = null;
            }
            else
            {
                $loginnames3[$s]['clessh']['idCle'] = $u->getClessh()->getId();
                $loginnames3[$s]['clessh']['nom'] = $u->getClessh()->getNom();
                $loginnames3[$s]['clessh']['pub'] = $u->getClessh()->getPub();
                $loginnames3[$s]['clessh']['rvk'] = $u->getClessh()->getRvk();
                $loginnames3[$s]['clessh']['deploy'] = $u->getDeply();
            }
            $loginnames3[$s]['userid'] = $u->getId();
        }
        
        return $loginnames3;
    }

    /*******************************************************
     * Renvoie le loginname sous la forme alice@serveur
     ******************************************************/
    public function getLoginname(User $u): string
    {
        return $u->getLoginname() . '@' . $u->getServeur();
    }

    /************************************************
     * Renvoie le couple 'user', 'serveur' pour un user alice@example.com
     *********************************************************************/
    public function parseLoginname(string $u): array
    {
        $rvl = explode('@', $u,2);
        if (count($rvl) != 2)
        {
            throw new \Exception (__METHOD__ . ":" . __LINE__ . " $u n'est pas de la forme alice@serveur");
        }
        return [ 'loginname' => $rvl[0], 'serveur' => $rvl[1]];
    }
    
} // class
