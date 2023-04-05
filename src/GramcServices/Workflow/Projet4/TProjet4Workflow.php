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

/***********************************************************************
 * 
 * TProjet4Workflow   = Permet de définir plusieurs états lors de la période de standby
 *
 ***********************************************************************/

namespace App\GramcServices\Workflow\Projet4;

use App\GramcServices\Workflow\Workflow;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceSessions;

use App\GramcServices\Etat;
use App\GramcServices\Signal;
use App\GramcServices\Workflow\NoTransition;

use App\GramcServices\ServiceNotifications;
use Doctrine\ORM\EntityManagerInterface;

/***********************************************************************
 * 
 * TProjet4Workflow   = L'implémentation du workflow "TIME" des Projet4
 *
 * Utilisé uniquement pour envoyer des notifications au responsable de projet
 * 99, 30, 15, 7 et 1 jour(s) avant la fin du projet
 *
 ***********************************************************************/

class TProjet4Workflow extends Workflow
{
    public function __construct(ServiceNotifications $sn,
                                ServiceJournal $sj,
                                ServiceSessions $ss,
                                EntityManagerInterface $em)
    {
        $this->workflowIdentifier   = get_class($this);
        parent::__construct($sn, $sj, $ss, $em);

        $this
            ->addState(
                Etat::STANDBY,
                [
                    Signal::DAT_CAL_99 => new TProjet4Transition(Etat::J_99, Signal::DAT_CAL_99, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_30 => new TProjet4Transition(Etat::J_30, Signal::DAT_CAL_30, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_15 => new TProjet4Transition(Etat::J_15, Signal::DAT_CAL_15, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_7 => new TProjet4Transition(Etat::J_7, Signal::DAT_CAL_7, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_1 => new TProjet4Transition(Etat::J_1, Signal::DAT_CAL_1, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_0 => new TProjet4Transition(Etat::TERMINE, Signal::DAT_CAL_0)
               ]
            )
            ->addState(
                Etat::J_99,
                [
                    Signal::DAT_CAL_99 => new NoTransition(0,0),
                    Signal::DAT_CAL_30 => new TProjet4Transition(Etat::J_30, Signal::DAT_CAL_30, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_15 => new TProjet4Transition(Etat::J_15, Signal::DAT_CAL_15, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_7 => new TProjet4Transition(Etat::J_7, Signal::DAT_CAL_7, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_1 => new TProjet4Transition(Etat::J_1, Signal::DAT_CAL_1, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_0 => new TProjet4Transition(Etat::TERMINE, Signal::DAT_CAL_0)
                ]
            )
            ->addState(
                Etat::J_30,
                [
                    Signal::DAT_CAL_99 => new NoTransition(0,0),
                    Signal::DAT_CAL_30 => new NoTransition(0,0),
                    Signal::DAT_CAL_15 => new TProjet4Transition(Etat::J_15, Signal::DAT_CAL_15, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_7 => new TProjet4Transition(Etat::J_7, Signal::DAT_CAL_7, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_1 => new TProjet4Transition(Etat::J_1, Signal::DAT_CAL_1, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_0 => new TProjet4Transition(Etat::TERMINE, Signal::DAT_CAL_0)
                ]
            )
            ->addState(
                Etat::J_15,
                [
                    Signal::DAT_CAL_99 => new NoTransition(0,0),
                    Signal::DAT_CAL_30 => new NoTransition(0,0),
                    Signal::DAT_CAL_15 => new NoTransition(0,0,),
                    Signal::DAT_CAL_7 => new TProjet4Transition(Etat::J_7, Signal::DAT_CAL_7, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_1 => new TProjet4Transition(Etat::J_1, Signal::DAT_CAL_1, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_0 => new TProjet4Transition(Etat::TERMINE, Signal::DAT_CAL_0)
                ]
            )
            ->addState(
                Etat::J_7,
                [
                    Signal::DAT_CAL_99 => new NoTransition(0,0),
                    Signal::DAT_CAL_30 => new NoTransition(0,0),
                    Signal::DAT_CAL_15 => new NoTransition(0,0),
                    Signal::DAT_CAL_7 => new NoTransition(0,0),
                    Signal::DAT_CAL_1 => new TProjet4Transition(Etat::J_1, Signal::DAT_CAL_1, ['R' => 'alerte_pour_demandeur' ]),
                    Signal::DAT_CAL_0 => new TProjet4Transition(Etat::TERMINE, Signal::DAT_CAL_0)
                ]
            )
            ->addState(
                Etat::J_1,
                [
                    Signal::DAT_CAL_99 => new NoTransition(0,0),
                    Signal::DAT_CAL_30 => new NoTransition(0,0),
                    Signal::DAT_CAL_15 => new NoTransition(0,0),
                    Signal::DAT_CAL_7 => new NoTransition(0,0),
                    Signal::DAT_CAL_1 => new NoTransition(0,0),
                    Signal::DAT_CAL_0 => new TProjet4Transition(Etat::TERMINE, Signal::DAT_CAL_0)
                ]
            )
            ->addState(
                Etat::TERMINE,
                [
                    Signal::DAT_CAL_99 => new NoTransition(0,0),
                    Signal::DAT_CAL_30 => new NoTransition(0,0),
                    Signal::DAT_CAL_15 => new NoTransition(0,0),
                    Signal::DAT_CAL_7 => new NoTransition(0,0),
                    Signal::DAT_CAL_1 => new NoTransition(0,0),
                    Signal::DAT_CAL_0 => new NoTransition(0,0)
                ]
            );
    }

    /***********************************************
     * Renvoie l'état de l'objet $object sous forme numérique
     * Fonction surchargée de Workflow, car l'état est donné par getTetatProjet()
     * 
     ************************************************************************/
    protected function getObjectState(object $object): int
    {
        if ($object === null)
        {
            $this->sj->errorMessage(__METHOD__  . ":" . __LINE__ . " getObjectState on object null");
            return Etat::INVALIDE;
        }
        elseif (method_exists($object, 'getTetatProjet'))
        {
            //echo "bonjour " . $object->getId() ." Tetat=" . $object->getTetatProjet() . "\n";
            return $object->getTetatProjet();
        }
        else
        {
            $this->sj->errorMessage(__METHOD__ . ":" . __LINE__ . " getTetatProjet n'existe pas pour la class ". get_class($object));
            return Etat::INVALIDE;
        }
    }

}
