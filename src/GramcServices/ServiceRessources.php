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

use App\Entity\Ressource;


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

class ServiceRessources
{
    public function __construct(private EntityManagerInterface $em){}

    /* Renvoie la liste de tous les noms complets de ressources, triée en ordre alphabétique */
    public function getNoms(): array
    {
        $ressources = $this->getRessources();
        $noms = [];
        foreach ($ressources as $r)
        {
            $noms[] = $this->getNomComplet($r);
        }
        return $noms;
    }

    /***********************************************************
     * Renvoie la liste des ressources connues,
     * en ordre alphabétique par rapport au nom complet de ressource
     *********************************************************************/
    public function getRessources() : array
    {
        $em = $this->em;
        $ressources = $em->getRepository(Ressource::class)->findAll();

        uasort($ressources, function($a, $b){
            if ($a === $b) {
                return 0;
            }
            return $this->getNomComplet($a) < $this->getNomComplet($b) ? -1 : 1;
        });
        return $ressources;
    }

    /**********************************************************************
     * Renvoie le nom complet de la ressource, c-à-d: nom-du-serveur nom-de-la-ressource
     ****************************************************************************************/
    public function getNomComplet(Ressource $ressource, $sep=' ') : string
    {
        $serveur = $ressource->getServeur();
        $nc = ($serveur === null) ? "null" : $serveur->getNom();
        if ($ressource->getNom() !== null)
        {
            $nc = $nc . $sep . $ressource->getNom();
        }
        return $nc;
    }
}
