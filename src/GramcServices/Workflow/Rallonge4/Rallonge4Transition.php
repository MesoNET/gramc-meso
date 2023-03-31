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

namespace App\GramcServices\Workflow\Rallonge4;

use App\GramcServices\Workflow\Transition;

use App\Utils\Functions;
use App\GramcServices\Etat;
use App\GramcServices\Signal;
use App\Entity\Rallonge;
use App\GramcServices\Workflow\Rallonge4\Rallonge4Workflow;

class Rallonge4Transition extends Transition
{
    ////////////////////////////////////////////////////

    public function canExecute(object $rallonge): bool {
        $rallonge instanceof Rallonge || throw new \InvalidArgumentException();
        return true;
    }

    ///////////////////////////////////////////////////////

    public function execute(object $rallonge): bool
    {
        $rallonge instanceof Rallonge || throw new \InvalidArgumentException();

        // Si on passe en état ACTIF, on signale aux hébergeurs qu'ils ont des choses à faire
        // Pas besoin de sauvegarder ce sera fait par changeEtat
        if ($this->getetat() === Etat::ACTIF)
        {
            foreach ($rallonge->getDar() as $d)
            {
                if ($d->getAttribution() > 0) $d->setTodof(true);
            }
        }
        
        // Change l'état de la rallonge
        $this->changeEtat($rallonge);

        // Envoyer les notifications
        $this->sendNotif($rallonge);

        return true;
    }
}
