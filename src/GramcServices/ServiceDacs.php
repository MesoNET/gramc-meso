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
 *  authors : Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

namespace App\GramcServices;

use App\Entity\Dac;
use App\Entity\Ressource;
use App\Entity\Version;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ServiceDacs
{
    public function __construct(private ServiceRessources $sr, private EntityManagerInterface $em){}

    /*********************************************************
     * Renvoie UN dac et UN SEUL. Si le dac n'existe pas on le crée
     ***********************************************************/
    public function getDac(Version $v, Ressource $r): Dac
    {
        $em = $this->em;
        $dacs = $em->getRepository(Dac::class)->findBy(['version' => $v, 'ressource' => $r]);
        if (count($dacs) === 0)
        {
            $d = new Dac();
            $d->setRessource($r);
            $d->setVersion($v);
            $v->addDac($d);
            $em->persist($d);
            $em->flush($d);
        }
        elseif (count($dacs) === 1)
        {
            $d = $dacs[0];
        }
        else
        {
            throw $this->sj->throwException("ServiceDacs:getDac findBy renvoie " . count($dacs) . " objets " . "$v - $r");
        }
        return $d;
    }
    /***********************************************
     * Renvoie les dacs correspondant à la version, dans un hash indexé par
     * le nom complet de la ressource associée
     *******************************************************/
    public function getDacsByNr(Version $v): array
    {
        $sr = $this->sr;
        
        $dacs=[];
        $vdacs = $v->getDac();
        foreach ($vdacs as $dac)
        {
            $k = $sr->getNomComplet($dac->getRessource(),'_');
            $dacs[$k] = $dac;
        }
        ksort($dacs);
        return $dacs;
    }

    /***********************************************
     * Renvoie la somme des attributions de la version correspondant à la version et à la ressource
     * c'est-à-dire l'attribution totale pour une version et une ressource,
     * en tenant compte de l'attribution initiale et des rallonges actives ou terminées.
     *******************************************************/
    public function getAttributionConsolidee(Dac $dac): int
    {
        $attribution = $dac->getAttribution();
        $ressource = $dac->getRessource();
        $version = $dac->getVersion();
        foreach ($version->getRallonge() as $rallonge)
        {
            if ($rallonge->getEtatRallonge() === Etat::ACTIF || $rallonge->getEtatRallonge() === Etat::TERMINE)
            {
                foreach ($rallonge->getDar() as $dar)
                {
                    if ($dar->getRessource() === $ressource)
                    {
                        $attribution += $dar->getAttribution();
                    }
                }
            }
        }
        return $attribution;
    }

    /***********************************************
     * Renvoie true si la version ou une de ses rallonges n'est pas acquittée (todof vaut true)
     * Renvoie false si acquitté
     *******************************************************/
    public function getTodofConsolide(Dac $dac):bool
    {
        if ($dac->getTodof()) return true;

        $ressource = $dac->getRessource();
        $version = $dac->getVersion();
        foreach ($version->getRallonge() as $rallonge)
        {
            foreach ($rallonge->getDar() as $dar)
            {
                if ($dar->getRessource() === $ressource && $dar->getTodof()) return true;
            }
        }
        return false;
    }

    /***********************************************
     * Renvoie la somme des demandes de la version correspondant à la version et à la ressource
     * c'est-à-dire la demande totale pour une version et une ressource,
     * en tenant compte de la demande initiale et des rallonges actives ou terminées.
     *******************************************************/
    public function getDemandeConsolidee(Dac $dac): int
    {
        $demande = $dac->getDemande();
        $ressource = $dac->getRessource();
        $version = $dac->getVersion();
        foreach ($version->getRallonge() as $rallonge)
        {
            if ($rallonge->getEtatRallonge() === Etat::ACTIF || $rallonge->getEtatRallonge() === Etat::TERMINE)
            {
                foreach ($rallonge->getDar() as $dar)
                {
                    if ($dar->getRessource() === $ressource)
                    {
                        $demande += $dar->getDemande();
                    }
                }
            }
        }
        return $demande;
    }

}
