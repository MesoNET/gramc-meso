<?php

/**
 * This file is part of GRAMC (Computing Ressource Granting Software)
 * GRAMC stands for : Gestion des Ressources et de leurs Attributions pour Mésocentre de Calcul.
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

use App\Entity\Serveur;
use Doctrine\ORM\EntityManagerInterface;

class ServiceServeurs
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /* Renvoie la liste de tous les noms de serveurs, triée en ordre alphabétique */
    public function getNoms(): array
    {
        $serveurs = $this->getServeurs();
        $noms = [];
        foreach ($serveurs as $s) {
            $noms[] = $s->getNom();
        }

        return $noms;
    }

    /***********************************************************
     * Renvoie la liste des serveurs connus, en ordre alphabétique par rapport au nom
     *********************************************************************/
    public function getServeurs(): array
    {
        $em = $this->em;

        return $em->getRepository(Serveur::class)->findAllsorted();
    }
}
