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
 *  authors : Miloslav Grundmann - C.N.R.S. - UMS 3667 - CALMIP
 *            Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

namespace App\GramcServices\Workflow\Projet4;

use App\Entity\Projet;
use App\GramcServices\Workflow\Transition;
use App\Utils\Functions;

/***********************************************************************
 *
 * TProjet4Transition
 *
 ***********************************************************************/

class TProjet4Transition extends Transition
{
    public function canExecute(object $projet): bool
    {
        return true;
    }

    public function execute(object $projet): bool
    {
        4 == $projet->getTypeProjet() || throw new \InvalidArgumentException();

        if (Transition::DEBUG) {
            $this->sj->debugMessage('>>> '.__FILE__.':'.__LINE__." $this $projet");
        }

        // Change l'état du projet
        $this->__changeTetat($projet);

        // Envoi des notifications
        $this->sendNotif($projet);

        return true;
    }

    /********************************************************************
     * Changer l'état de l'objet passé en paramètre
     *
     * La variable utilisée est tetat
     **************************************************************************************/
    private function __changeTetat(Projet $projet): void
    {
        if (Transition::DEBUG) {
            $old_etat = $projet->getTetatProjet();
            $projet->setTetatProjet($this->getEtat());
            Functions::sauvegarder($projet, $this->em);
            $this->sj->debugMessage('>>> '.__FILE__.':'.__LINE__.' projet '.$projet." est passé de l'état ".$old_etat.' à '.$projet->getTetatProjet().' suite au signal '.$this->getSignal());
        } else {
            $projet->setTetatProjet($this->getEtat());
            Functions::sauvegarder($projet, $this->em);
        }
    }
}
