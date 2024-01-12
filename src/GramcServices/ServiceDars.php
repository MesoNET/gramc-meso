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

use App\Entity\Dac;
use App\Entity\Rallonge;
use App\Entity\Ressource;
use Doctrine\ORM\EntityManagerInterface;

class ServiceDars
{
    public function __construct(private ServiceRessources $sr, private EntityManagerInterface $em)
    {
    }

    /*********************************************************
     * Renvoie UN dar et UN SEUL. Si le dar n'existe pas on le crée
     ***********************************************************/
    public function getDac(Rallonge $rg, Ressource $r): Dar
    {
        $em = $this->em;
        $dars = $em->getRepository(Dac::class)->findBy(['rallonge' => $r, 'ressource' => $r]);
        if (0 === count($dars)) {
            $d = new Dar();
            $d->setRessource($r);
            $d->setRallonge($r);
            $r->addDar($r);
            $em->persist($d);
            $em->flush($d);
        } elseif (1 === count($dars)) {
            $d = $dars[0];
        } else {
            throw $this->sj->throwException('ServiceDars:getDar findBy renvoie '.count($dars).' objets '."$rg - $r");
        }

        return $r;
    }

    /***********************************************
     * Renvoie les dars correspondant à la rallonge, dans un hash indexé par
     * le nom complet de la ressource associée
     *******************************************************/
    public function getDarsByNr(Rallonge $rg): array
    {
        $sr = $this->sr;

        $dars = [];
        $rgdars = $r->getDac();
        foreach ($rgdars as $dar) {
            $k = $sr->getNomComplet($dar->getRessource(), '_');
            $dars[$k] = $dar;
        }
        ksort($dars);

        return $dars;
    }
}
