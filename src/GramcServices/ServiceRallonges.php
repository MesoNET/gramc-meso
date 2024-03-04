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

use App\Entity\Dar;
use App\Entity\Rallonge;
use App\Entity\Ressource;
use App\Entity\Version;
use App\Form\DarType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    ) {
    }

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
        $sroc = $this->sroc;
        $this->tok->getToken();
        $em = $this->em;

        $rallonge = new Rallonge();
        $rallonge->setEtatRallonge(Etat::EDITION_DEMANDE);
        $rallonge->setVersion($version);
        $count = count($version->getRallonge()) + 1;
        $rallonge->setIdRallonge($version->getIdVersion().'R'.$count);

        // Ecriture de la rallonge dans la BD
        $em->persist($rallonge);
        $em->flush($rallonge);

        // Création de nouveaux Dar (1 Dar par ressource)
        $ressources = $sroc->getRessources();
        foreach ($ressources as $r) {
            $dar = new Dar();
            $dar->setRallonge($rallonge);
            $dar->setRessource($r);
            $em->persist($dar);
            $rallonge->addDar($dar);
        }
        $em->flush();

        return $rallonge;
    }

    /*******************
     * Renvoie le méta état (pour affichage) d'une ressource
     **********************************************************/
    public function getMetaEtat(Rallonge $r): string
    {
        $etat = $r->getEtatRallonge();
        if (Etat::EDITION_DEMANDE === $etat) {
            return 'EDITION';
        } elseif (Etat::EDITION_EXPERTISE === $etat) {
            return 'EXPERTISE';
        } elseif (Etat::ACTIF === $etat) {
            return 'ACCEPTE';
        } elseif (Etat::EN_ATTENTE === $etat) {
            return 'ATTENTE';
        } elseif (Etat::REFUSE === $etat) {
            return 'REFUSE';
        } elseif (Etat::ANNULE === $etat) {
            return 'TERMINE';
        } else {
            return '';
        }
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

    public function prepareRessources(Rallonge $rallonge): array
    {
        $em = $this->em;
        $sj = $this->sj;

        if (null == $rallonge) {
            $sj->throwException('ServiceRallonges:prepareRessources : rallonge null');
        }

        $ressources = $em->getRepository(Ressource::class)->findAll();

        // Un array indexé par l'identifiant de ressource
        $dars = [];
        foreach ($rallonge->getDar() as $dar) {
            $k = $dar->getRessource()->getId();
            $dars[$k] = $dar;
        }

        $data = [];
        foreach ($ressources as $r) {
            if (array_key_exists($r->getId(), $dars)) {
                $dar = $dars[$r->getId()];
            } else {
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
        return $this->ff
                   ->createNamedBuilder('form_ressource', FormType::class, ['ressource' => $this->prepareRessources($rallonge)])
                   ->add('ressource', CollectionType::class, [
                       'entry_type' => DarType::class,
                       'label' => true,
                   ])
                   ->getForm();
    }

    /*********************************
     *
     * Validation du formulaire des ressources - Retourne true car toujours valide !
     *
     * params = Tableau de formulaires
     ***********************************************************************/
    public function validateRessourceForms(array &$ressource_forms): bool
    {
        $val = true;
        foreach ($ressource_forms as &$dar) {
            if ($dar->getDemande() < 0) {
                $val = false;
                $dar->setdemande(0);
            }
        }

        return $val;
    }
}
