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

use App\Entity\Rallonge;
use App\Entity\Version;
use App\Form\DacType;
use App\Form\DarType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class ServiceExpertises
{
    public function __construct(private ServiceVersions $sv,
        private ServiceRallonges $srg,
        private FormFactoryInterface $ff,
        private EntityManagerInterface $em)
    {
    }

    /********************************************************************
     * Génère et renvoie un form pour modifier les attributions de ressources
     ********************************************************************/
    public function getRessourceFormForVersion(Version $version): FormInterface
    {
        $sv = $this->sv;

        $data = $sv->prepareRessources($version);

        // S'il n'y a aucune attribution, on initialise en attribuant ce qui a été demandé
        $attrib = false;
        foreach ($data as $dac) {
            if (0 !== $dac->getAttribution()) {
                $attrib = true;
                break;
            }
        }
        if (false === $attrib) {
            foreach ($data as $dac) {
                $dac->setAttribution($dac->getDemande());
            }
        }

        return $this->ff
                   ->createNamedBuilder('form_ressource', FormType::class, ['ressource' => $data])
                   ->add('ressource', CollectionType::class, [
                       'entry_type' => DacType::class,
                       'label' => true,
                       'entry_options' => ['attribution' => true],
                   ])
                   ->getForm();
    }

    /********************************************************************
     * Génère et renvoie un form pour modifier les attributions de ressources
     ********************************************************************/
    public function getRessourceFormForRallonge(Rallonge $rallonge): FormInterface
    {
        $srg = $this->srg;

        $data = $srg->prepareRessources($rallonge);

        // S'il n'y a aucune attribution, on initialise en attribuant ce qui a été demandé
        $attrib = false;
        foreach ($data as $dar) {
            if (0 !== $dar->getAttribution()) {
                $attrib = true;
                break;
            }
        }
        if (false === $attrib) {
            foreach ($data as $dar) {
                $dar->setAttribution($dar->getDemande());
            }
        }

        return $this->ff
                   ->createNamedBuilder('form_ressource', FormType::class, ['ressource' => $data])
                   ->add('ressource', CollectionType::class, [
                       'entry_type' => DarType::class,
                       'label' => true,
                       'entry_options' => ['attribution' => true],
                   ])
                   ->getForm();
    }
}
