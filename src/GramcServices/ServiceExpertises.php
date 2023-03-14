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

use App\Entity\Version;
use App\GramcServices\ServiceVersions;
use App\Form\DacType;

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

class ServiceExpertises
{
    public function __construct(private ServiceVersions $sv, private FormFactoryInterface $ff, private EntityManagerInterface $em){}

    /********************************************************************
     * Génère et renvoie un form pour modifier les attributions de ressources
     ********************************************************************/
    public function getRessourceForm(Version $version): FormInterface
    {
        $ff = $this->ff;
        $sv = $this->sv;

        $data = $sv->prepareRessources($version);

        // S'il n'y a aucune attribution, on initialise en attribuant ce qui a été demandé
        $attrib = false;
        foreach ($data as $dac)
        {
            if ($dac->getAttribution() != 0)
            {
                $attrib = true;
                break;
            }
        }
        if ($attrib == false)
        {
            foreach ($data as $dac)
            {
                $dac->setAttribution($dac->getDemande());
            }
        }

        $form = $this->ff
                   ->createNamedBuilder('form_ressource', FormType::class, [ 'ressource' => $data ])
                   ->add('ressource', CollectionType::class, [
                       'entry_type' =>  DacType::class,
                       'label' =>  true,
                       'entry_options' =>['attribution' => true ]
                   ])
                   ->getForm();
        return $form;
    }
}

