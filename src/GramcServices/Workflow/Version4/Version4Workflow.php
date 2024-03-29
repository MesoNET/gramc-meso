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

namespace App\GramcServices\Workflow\Version4;

use App\GramcServices\Workflow\Workflow;
use App\GramcServices\Workflow\NoTransition;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceSessions;

use App\GramcServices\Etat;
use App\GramcServices\Signal;

use App\GramcServices\ServiceNotifications;
use Doctrine\ORM\EntityManagerInterface;

/***********************************************************************
 * 
 * Version4Workflow   = L'implémentation du workflow des versions
 * Version4Transition   pour des projets / versions de type 4 (projets dynamiques)
 *
 ***********************************************************************/

class Version4Workflow extends Workflow
{
    public function __construct(ServiceNotifications $sn, ServiceJournal $sj, ServiceSessions $ss, EntityManagerInterface $em)
    {
        $this->workflowIdentifier = get_class($this);
        parent::__construct($sn, $sj, $ss, $em);

        $this
            ->addState(
                Etat::EDITION_DEMANDE,
                [
                Signal::CLK_VAL_DEM => new Version4Transition(
                    Etat::EDITION_EXPERTISE,
                    Signal::CLK_VAL_DEM,
                    [ 'R' => 'depot_pour_demandeur' ,
                      'V' => 'depot_pour_valideurs' ]
                ),
                Signal::CLK_FERM        => new Version4Transition(Etat::TERMINE, Signal::CLK_FERM),
                ]
            )
            ->addState(
                Etat::EDITION_EXPERTISE,
                [
                Signal::CLK_VAL_EXP_OK  => new Version4Transition(
                    Etat::ACTIF,
                    Signal::CLK_VAL_EXP_OK,
                    [ 'R' => 'validation4',
                      'V' => 'validation_pour_valideur',
                      'A' => 'validation_pour_admin' ]
                ),
                Signal::CLK_VAL_EXP_KO  => new Version4Transition(
                    Etat::REFUSE,
                    Signal::CLK_VAL_EXP_KO,
                    [ 'V' => 'validation_refusee' ]
                ),
                Signal::CLK_FERM        => new Version4Transition(Etat::TERMINE, Signal::CLK_FERM),
                Signal::CLK_ARR         => new Version4Transition(Etat::EDITION_DEMANDE, Signal::CLK_ARR),
                ]
            )
            ->addState(
                Etat::ACTIF,
                [
                Signal::DAT_ACTR       => new Version4Transition(Etat::ACTIF_R, Signal::DAT_ACTR, [ 'R' => 'version_a_renouveler']),
                Signal::CLK_VAL_EXP_OK => new Version4Transition(Etat::TERMINE, Signal::CLK_VAL_EXP_OK),
                Signal::CLK_VAL_EXP_KO => new Version4Transition(Etat::TERMINE, Signal::CLK_VAL_EXP_KO),
                Signal::CLK_ARR        => new NoTransition(0, 0),
                Signal::CLK_VAL_DEM    => new NoTransition(0, 0),
                Signal::CLK_FERM       => new Version4Transition(Etat::TERMINE, Signal::CLK_FERM),
                ]
            )
            ->addState(
                Etat::ACTIF_R,
                [
                Signal::DAT_ACTR       => new NoTransition(0,0),
                Signal::CLK_ARR        => new NoTransition(0,0),
                Signal::CLK_VAL_EXP_OK => new Version4Transition(Etat::TERMINE, Signal::CLK_VAL_EXP_OK),
                Signal::CLK_VAL_EXP_KO => new Version4Transition(Etat::TERMINE, Signal::CLK_VAL_EXP_KO),
                Signal::CLK_VAL_DEM    => new NoTransition(0, 0),
                Signal::CLK_FERM       => new Version4Transition(Etat::TERMINE, Signal::CLK_FERM),
                ]
            )
             ->addState(
                 Etat::TERMINE,
                 [
                Signal::CLK_SESS_DEB    => new NoTransition(0, 0),
                Signal::CLK_SESS_FIN    => new NoTransition(0, 0),
                Signal::CLK_FERM        => new NoTransition(0, 0),
                ]
             );
    }
}
