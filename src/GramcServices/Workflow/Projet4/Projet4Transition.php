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
use App\Entity\Version;
use App\GramcServices\Etat;
use App\GramcServices\Signal;
use App\GramcServices\Workflow\Transition;
use App\GramcServices\Workflow\Version4\Version4Workflow;
use App\Utils\Functions;

/***********************************************************************
 *
 * Version4Workflow   = L'implémentation du workflow des versions
 * Version4Transition   pour des projets / versions de type 4 (projets dynamiques)
 *
 ***********************************************************************/

class Projet4Transition extends Transition
{
    private static $execute_en_cours = false;

    // //////////////////////////////////////////////////
    public function canExecute(object $projet): bool
    {
        4 == $projet->getTypeProjet() || throw new \InvalidArgumentException();

        // Pour éviter une boucle infinie entre projet et version !
        if (self::$execute_en_cours) {
            return true;
        } else {
            self::$execute_en_cours = true;
        }

        $rtn = true;
        if (Transition::FAST == false && $this->getPropageSignal()) {
            $versionWorkflow = new Version4Workflow($this->sn, $this->sj, $this->ss, $this->em);
            foreach ($projet->getVersion() as $version) {
                if (Etat::TERMINE != $version->getEtatVersion() && Etat::ANNULE != $version->getEtatVersion()) {
                    $output = $versionWorkflow->canExecute($this->getSignal(), $version);
                    if (true != $output) {
                        $this->sj->warningMessage(__METHOD__.':'.__LINE__.' Version '.$version.' état '
                            .Etat::getLibelle($version->getEtatVersion()).' signal = '.Signal::getLibelle($this->getSignal()));
                    }
                    $rtn = $rtn && $output;
                }
            }
        }
        self::$execute_en_cours = false;

        return $rtn;
    }

    // /////////////////////////////////////////////////////
    // Transmet le signal aux versions du projet qui ne sont ni annulées ni terminées

    public function execute(object $projet): bool
    {
        4 == $projet->getTypeProjet() || throw new \InvalidArgumentException();

        if (Transition::DEBUG) {
            $this->sj->debugMessage('>>> '.__FILE__.':'.__LINE__." $this $projet");
        }

        // Pour éviter une boucle infinie entre projet et version !
        if (self::$execute_en_cours) {
            return true;
        }
        self::$execute_en_cours = true;

        $rtn = true;
        if ($this->getPropageSignal()) {
            $signal = $this->getSignal();
            $versionWorkflow = new Version4Workflow($this->sn, $this->sj, $this->ss, $this->em);
            foreach ($projet->getVersion() as $version) {
                if (Etat::TERMINE != $version->getEtatVersion() && Etat::ANNULE != $version->getEtatVersion()) {
                    $return = $versionWorkflow->execute($signal, $version);
                    if (Transition::DEBUG) {
                        $this->sj->debugMessage('>>> '.__FILE__.':'.__LINE__." version=$version signal=$signal rtn=".Functions::show($rtn));
                    }
                    $rtn = Functions::merge_return($rtn, $return);
                }
            }
        }

        // Change l'état du projet
        $this->changeEtat($projet);

        // Envoi des notifications
        $this->sendNotif($projet);

        self::$execute_en_cours = false;
        if (Transition::DEBUG) {
            $this->sj->debugMessage('<<< '.__FILE__.':'.__LINE__.' rtn = '.Functions::show($rtn));
        }

        return $rtn;
    }
}
