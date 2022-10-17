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
 * Projet44Workflow   = L'implémentation du workflow des projets
 * Projet44Transition   pour des projets / versions de type 4 (projets dynamiques)
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
 * Version4Workflow   = L'implémentation du workflow des versions
 * Version4Transition   pour des projets / versions de type 4 (projets dynamiques)
 *
 ***********************************************************************/

class Projet4Workflow extends Workflow
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
                Etat::RENOUVELABLE,
                [
                // Utile seulement pour propagation aux versions
                Signal::CLK_VAL_DEM    => new Projet4Transition(Etat::RENOUVELABLE, Signal::CLK_VAL_DEM, [], true),
                Signal::CLK_ARR        => new Projet4Transition(Etat::RENOUVELABLE, Signal::CLK_ARR, [], true),
                Signal::CLK_VAL_EXP_OK => new Projet4Transition(Etat::RENOUVELABLE, Signal::CLK_VAL_EXP_OK, [], true),

                Signal::DAT_STDBY      => new Projet4Transition(Etat::RENOUVELABLE, Signal::DAT_STDBY, [], true),
                Signal::DAT_SURSIS     => new Projet4Transition(Etat::RENOUVELABLE, Signal::DAT_SURSIS, [], true),

                Signal::CLK_VAL_EXP_KO => new Projet4Transition(Etat::TERMINE, Signal::CLK_VAL_EXP_KO, [], true),
                Signal::CLK_FERM       => new Projet4Transition(Etat::TERMINE, Signal::CLK_FERM),
                ]
            )
            ->addState(
                Etat::TERMINE,
                [
                Signal::CLK_FERM       => new NoTransition(0, 0),
                ]
            );
    }
}
