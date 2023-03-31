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

use App\GramcServices\Workflow\Transition;
use App\Utils\Functions;
use App\GramcServices\Etat;
use App\GramcServices\Signal;
use App\Entity\Version;
use App\GramcServices\Workflow\Rallonge4\Rallonge4Workflow;
use App\GramcServices\Workflow\Projet\ProjetWorkflow;

class Version4Transition extends Transition
{
    private static $execute_en_cours     = false;

    ////////////////////////////////////////////////////
    public function canExecute(object $version): bool
    {
        if (!$version instanceof Version) {
            throw new \InvalidArgumentException();
        }

        // Pour éviter une boucle infinie entre projet et version !
        if (self::$execute_en_cours) {
            return true;
        } else {
            self::$execute_en_cours = true;
        }

        $rtn = true;
        if (Transition::FAST == false && $this->getPropageSignal()) {
            $rallonges = $version->getRallonge();
            if ($rallonges != null) {
                $workflow = new Rallonge4Workflow($this->sn, $this->sj, $this->ss, $this->em);
                foreach ($rallonges as $rallonge) {
                    $rtn = $rtn && $workflow->canExecute($this->getSignal(), $rallonge);
                }
            }
        }

        self::$execute_en_cours = false;
        return $rtn;
    }

    ////////////////////////////////////////////////////
    public function execute(object $version): bool
    {
        if (!$version instanceof Version) {
            throw new \InvalidArgumentException();
        }
        if (Transition::DEBUG) {
            $this->sj->debugMessage(">>> " .  __FILE__ . ":" . __LINE__ . " $this $version");
        }

        // Pour éviter une boucle infinie entre projet et version !
        if (self::$execute_en_cours) {
            return true;
        }
        self::$execute_en_cours = true;

        $rtn = true;

        // Propage le signal aux rallonges si demandé
        if ($this->getPropageSignal()) {
            if (Transition::DEBUG) $this->sj->debugMessage("<<< " . __FILE__ . ":" . __LINE__ . " Propagations aux rallonges (".count($rallonges).")");
            $rallonges = $version->getRallonge();

            if (count($rallonges) > 0) {
                $workflow = new Rallonge4Workflow($this->sn, $this->sj, $this->ss, $this->em);

                // Propage le signal à toutes les rallonges qui dépendent de cette version
                foreach ($rallonges as $rallonge) {
                    $output = $workflow->execute($this->getSignal(), $rallonge);
                    $rtn = Functions::merge_return($rtn, $output);
                }
            }
        }

        // Propage le signal au projet si demandé
        if ($this->getPropageSignal()) {
            if (Transition::DEBUG) $this->sj->debugMessage("<<< " . __FILE__ . ":" . __LINE__ . " Propagations au projet");
            $projet = $version->getProjet();
            $workflow = new ProjetWorkflow($this->sn, $this->sj, $this->ss, $this->em);
            $output   = $workflow->execute($this->getSignal(), $projet);
            $rtn = Functions::merge_return($rtn, $output);
        }

        // Si on passe en état ACTIF, on signale aux hébergeurs qu'ils ont des choses à faire
        // Pas besoin de sauvegarder ce sera fait par changeEtat
        if ($this->getetat() === Etat::ACTIF)
        {
            foreach ($version->getDac() as $d)
            {
                if ($d->getAttribution() > 0) $d->setTodof(true);
            }
        }

        // Change l'état de la version
        $this->changeEtat($version);

        // Envoi des notifications
        $this->sendNotif($version);

        self::$execute_en_cours = false;
        if (Transition::DEBUG) {
            $this->sj->debugMessage(">>> " . __FILE__ . ":" . __LINE__ . " rtn = " . Functions::show($rtn));
        }

        return $rtn;
    }
}
