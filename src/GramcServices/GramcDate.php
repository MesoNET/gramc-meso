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

use Doctrine\ORM\EntityManagerInterface;

/* Ce service est utilisé pour afficher la date avec un décalage
 * éventuellement stoqué dans la table Params
 * cf. aussi GramcDateTime
 */

/*
 * Exemples de paramètres à mettre dans Param:
 *
 * DateString: -2 years + 3 days
 * DateShift: P3Y30D
 * NewDate: 20130101
 * OldDate: 20130101
 *
 */

class GramcDate extends GramcDateTime
{
    public function __construct(
        private ServiceParam $sp,
        private EntityManagerInterface $em
    ) {
        parent::__construct($sp, $em);
        $this->setTime(0, 0, 0);
    }

    // retourne une nouvelle instance
    public function getNew(): GramcDate
    {
        $date = new GramcDate(
            $this->sp,
            $this->em
        );

        return $date;
    }
}
