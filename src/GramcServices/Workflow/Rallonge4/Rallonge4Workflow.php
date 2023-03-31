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

use App\GramcServices\Workflow\Workflow;
use App\GramcServices\Workflow\Transition;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceSessions;

use App\GramcServices\Etat;
use App\GramcServices\Signal;
use App\GramcServices\Workflow\NoTransition;

use App\GramcServices\ServiceNotifications;
use Doctrine\ORM\EntityManagerInterface;

class Rallonge4Workflow extends Workflow
{
    protected $states             = [];
    protected $workflowIdentifier = null;

    public function __construct(ServiceNotifications $sn,
                                ServiceJournal $sj,
                                ServiceSessions $ss,
                                EntityManagerInterface $em)
    {
        if ($this->workflowIdentifier != null) {
            return;
        }
        parent::__construct($sn, $sj, $ss, $em);

        $this
            ->addState(
                Etat::EDITION_DEMANDE,
                [
                Signal::CLK_VAL_DEM      => new Rallonge4Transition(
                    Etat::EDITION_EXPERTISE,
                    Signal::CLK_VAL_DEM,
                    [ 'R' => 'depot_rallonge_pour_demandeur',
                      'A' => 'depot_rallonge_pour_admin' ]
                ),
                Signal::CLK_FERM         => new Rallonge4Transition(Etat::ANNULE, Signal::CLK_FERM),
                ]
            )
            ->addState(
                Etat::EDITION_EXPERTISE,
                [
                Signal::CLK_VAL_EXP_OK  =>  new Rallonge4Transition(
                    Etat::ACTIF,
                    Signal::CLK_VAL_EXP_OK,
                    [ 'R' => 'rallonge_validation4',
                      'V' => 'rallonge_validation_pour_valideur',
                      'A' => 'rallonge_validation_pour_admin' ]
                ),
                Signal::CLK_VAL_EXP_KO  =>  new Rallonge4Transition(
                    Etat::REFUSE,
                    Signal::CLK_VAL_EXP_KO,
                    [ 'V' => 'rallonge_validation_refusee',
                      'A' => 'rallonge_validation_pour_admin' ]
                ),
                Signal::CLK_FERM         => new Rallonge4Transition(Etat::ANNULE, Signal::CLK_FERM),
                ]
            )
            ->addState(
                Etat::ACTIF,
                [
                Signal::CLK_FERM         => new Rallonge4Transition(Etat::TERMINE, Signal::CLK_FERM),
                ]
            )
            ->addState(
                Etat::TERMINE,
                [
                Signal::CLK_FERM         => new NoTransition(0, 0),
                ]
            )
            ->addState(
                Etat::ANNULE,
                [
                Signal::CLK_FERM         => new NoTransition(0,0)
                ]
                )
            ->addState(
                Etat::REFUSE,
                [
                Signal::CLK_FERM         => new NoTransition(0,0)
                ]
                );
    }
}
