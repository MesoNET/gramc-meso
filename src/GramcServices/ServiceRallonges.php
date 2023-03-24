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

use App\Entity\Projet;
use App\Entity\Version;
use App\Entity\Rallonge;
use App\Entity\Session;
use App\Entity\Individu;
use App\Entity\Formation;
use App\Entity\FormationVersion;
use App\Entity\Ressource;
use App\Entity\Dar;
use App\Entity\Serveur;

use App\Entity\User;
use App\Entity\CollaborateurVersion;

use App\GramcServices\Etat;
use App\GramcServices\ServiceForms;
use App\GramcServices\ServiceInvitations;
use App\GramcServices\GramcDate;

use App\Form\IndividuFormType;
use App\Form\IndividuForm\IndividuForm;
use App\Form\FormationVersionType;
use App\Form\DarType;


use App\Utils\Functions;

use App\Validator\Constraints\PagesNumber;

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

class ServiceRallonges
{
    public function __construct(
                                private ServiceJournal $sj,
                                private ServiceServeurs $sr,
                                private ServiceRessources $sroc,
                                private ServiceUsers $su,
                                private ServiceInvitations $sid,
                                private ValidatorInterface $vl,
                                private ServiceForms $sf,
                                private FormFactoryInterface $ff,
                                private TokenStorageInterface $tok,
                                private GramcDate $grdt,
                                private EntityManagerInterface $em
                                )
    {}

    /****************
     * Création d'une nouvelle rallonge liée à une version existante, c'est-à-dire:
     *    - Création de la rallonge
     *    - Création des Dars associés
     *
     * Params: $version la version associée
     *    
     * Retourne: La nouvelle rallonge
     * 
     ************************************************/

    public function creerRallonge(Version $version): Rallonge
    {
        $su = $this->su;
        $sr = $this->sr;
        $sroc = $this->sroc;
        $token = $this->tok->getToken();
        $em = $this->em;

        $rallonge = new Rallonge();
        $rallonge->setEtatRallonge(Etat::EDITION_DEMANDE);
        $rallonge->setVersion($version);
        $count   = count($version->getRallonge()) + 1;
        $rallonge->setIdRallonge($version->getIdVersion() . 'R' . $count);

        // Ecriture de la rallonge dans la BD
        $em->persist($rallonge);
        $em->flush($rallonge);

        // Création de nouveaux Dar (1 Dar par ressource)
        $ressources = $sroc->getRessources();
        foreach ($ressources as $r)
        {
            $dar = new Dar();
            $dar->setRallonge($rallonge);
            $dar->setRessource($r);
            $em->persist($dar);
            $rallonge->addDar($dar);
        }
        $em->flush();

        return $rallonge;
    }

    /*********************************************
     *
     * LES DEMANDES DE RESSOURCES
     * 
     ********************************************/

     // TODO - Copié-presque-collé depuis DEMANDES DE RESSOURCES de ServiceVersions
     //        Il faudrait rendre tout ça générique !

    /**************************
     * préparation de la liste des ressources disponibles
     * Récupère dans la base la liste des ressources
     * c'est-à-dire toutes les ressources (TODO - Ajouter un champ "disponible")
     * Pour chaque ressource, crée un enregistrement de type Dac s'il n'existe pas
     *
     * params = $rallonge
     *
     * return = Un tableau d'objets de type Dar
     *
     *****************************************************************************/

    public function prepareRessources(Rallonge $rallonge) : array
    {
        $em = $this->em;
        $sj = $this->sj;
        
        if ($rallonge == null) {
            $sj->throwException('ServiceRallonges:prepareRessources : rallonge null');
        }

        $ressources = $em->getRepository(Ressource::class)->findAll();

        // Un array indexé par l'identifiant de ressource
        $dars = [];
        foreach ( $rallonge->getDar() as $dar)
        {
            $k = $dar->getRessource()->getId();
            $dars[$k] = $dar;
        }

        $data = [];
        foreach ($ressources as $r)
        {
            if (array_key_exists($r->getId(), $dars))
            {
                $dar = $dars[$r->getId()];
            }
            else
            {
                $dar = new Dar($r, $version);
            }
            $data[] = $dar;
        }
        return $data;
    }

    /********************************************************************
     * Génère et renvoie un form pour modifier les demandes de ressources
     ********************************************************************/
    public function getRessourceForm(Rallonge $rallonge): FormInterface
    {
        $sj = $this->sj;
        $em = $this->em;
        $sval= $this->vl;

        $form = $this->ff
                   ->createNamedBuilder('form_ressource', FormType::class, [ 'ressource' => $this->prepareRessources($rallonge) ])
                   ->add('ressource', CollectionType::class, [
                       'entry_type' =>  DarType::class,
                       'label' =>  true,
                   ])
                   ->getForm();
        return $form;
    }

    /*********************************
     * 
     * Validation du formulaire des ressources - Retourne true car toujours valide !
     *
     * params = Tableau de formulaires
     ***********************************************************************/
    public function validateRessourceForms(array &$ressource_forms) : bool
    {
        $val = true;
        foreach ( $ressource_forms as &$dar)
        {
            if ($dar->getDemande() < 0)
            {
                $val = false;
                $dar->setdemande(0);
            }
        }
        return $val;
    }

    /***************************************
     * Traitement des formulaires des ressources
     *
     * $ressource_forms = Tableau contenant un formulaire par ressource
     * $version        = La version considérée
     ****************************************************************/
    public function handleRessourceForms(array $ressource_forms, Version $rallonge): void
    {
        $em   = $this->em;
        $sj   = $this->sj;
        $sval = $this->vl;

        //dd($ressource_forms);
        // On fait la modification sur la version passée en paramètre
        foreach ($ressource_forms as $idar)
        {
            $rallonge->addDar($idar);
        }
        $em->persist($rallonge);
        $em->flush();
    }
}
