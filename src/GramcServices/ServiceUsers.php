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
     *************************************/
    public function collaborateurVersion2LoginNames(CollaborateurVersion $cv): array
    {
        $em = $this -> em;
        
        $users = $em->getRepository(User::class)->findBy(['collaborateurversion' => $cv ]);
        $loginnames = [];
        foreach ( $users as $u)
        {
            $loginnames[] = $this->getLoginname($u);
        }
        return $loginnames;
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
            throwException ($sj->throwException(__METHOD__ . ":" . __LINE__ . " $u n'est pas de la forme alice@serveur"));
        }
        return [ 'loginname' => $rvl[0], 'serveur' => $rvl[1]];
    }
    
} // class
