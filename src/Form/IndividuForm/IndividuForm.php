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

namespace App\Form\IndividuForm;

use App\Entity\Individu;
use App\GramcServices\ServiceJournal;
use App\Utils\Functions;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**************************************************************************
 * Cette classe (qui ne dérive PAS de form !) est utilisée par l'écran des collaborateurs
 * A chaque ligne du tableau correspond un IndividuForm
 * TODO - elle devrait s'appeler IndividuData
 ************************************************************************/
class IndividuForm
{
    // Un tableau associatif, indexé par les noms de serveurs
    protected $logins = [];
    protected $deleted;
    protected $responsable;

    protected $mail;
    protected $prenom;
    protected $nom;

    protected $statut;
    protected $laboratoire;
    protected $etablissement;
    protected $id;

    public function __construct(private array $srv_noms = [], ?Individu $individu = null)
    {
        foreach ($srv_noms as $n) {
            $this->logins[$n] = false;
        }
        $this->deleted = false;
        $this->responsable = false;
        if (null != $individu) {
            $this->mail = $individu->getMail();
            $this->prenom = $individu->getPrenom();
            $this->nom = $individu->getNom();
            $this->statut = $individu->getStatut();
            $this->laboratoire = $individu->getLabo();
            $this->etablissement = $individu->getEtab();
            $this->id = $individu->getId();
        }
    }

    public function __toString()
    {
        $output = '';
        if (true == $this->getDeleted()) {
            $output .= 'SUPPRIMER COLLABORATEUR ';
        }
        if (true == $this->getResponsable()) {
            $output .= 'RESPONSABLE:';
        }
        $output .= $this->getMail().':'.$this->getPrenom().':'.$this->getNom().':'.$this->getStatut().':';
        $output .= $this->getLaboratoire().':'.$this->getEtablissement().':'.$this->getId();

        return $output;
    }

    public function getLogins(): array
    {
        return $this->logins;
    }

    public function setLogins(array $logins): self
    {
        $this->logins = [];
        foreach ($logins as $k => $l) {
            $this->logins[$k] = empty($l) ? false : true;
        }

        return $this;
    }

    public function getResponsable()
    {
        return $this->responsable;
    }

    public function setResponsable($responsable)
    {
        $this->responsable = $responsable;

        return $this;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getMail()
    {
        return $this->mail;
    }

    public function setMail($mail)
    {
        $this->mail = $mail;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getPrenom()
    {
        return $this->prenom;
    }

    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($statut)
    {
        $this->statut = $statut;

        return $this;
    }

    public function getLaboratoire()
    {
        return $this->laboratoire;
    }

    public function setLaboratoire($laboratoire)
    {
        $this->laboratoire = $laboratoire;

        return $this;
    }

    public function getEtablissement()
    {
        return $this->etablissement;
    }

    public function setEtablissement($etablissement)
    {
        $this->etablissement = $etablissement;

        return $this;
    }

    // ////////////////////////////////////////////////////////////

    /************************************************************
     * Crée un nouvel individu à partir des informations du formulaire
     * Si les données ne sont pas validées, on ne crée pas l'individu et on renvoie null
     * Si les données sont valides, l'individu est créé et renvoyé
     *****************************************************************/
    public function nouvelIndividu(ValidatorInterface $sval)
    {
        $individu = new Individu();
        $individu->setMail($this->getMail());
        $individu->setNom($this->getNom());
        $individu->setPrenom($this->getPrenom());
        $individu->setLabo($this->getLaboratoire());
        $individu->setLabo($this->getLaboratoire());
        $individu->setEtab($this->getEtablissement());
        $individu->setStatut($this->getStatut());

        // Validation
        $erreurs = Functions::dataError($sval, $individu);
        if (count($erreurs) > 0) {
            // //$this->sj->debugMessage(__METHOD__ . ':' . __LINE__ . ' ERREURS = '. print_r($erreurs,true));
            return null;
        }

        return $individu;
    }

    /*****
     *
     * Synchronise l'entité $individu à partir du formulaire
     * (à l'exception du mail qui sert à identifier l'individu)
     *
     * $onlyNom = Si true, la modif ne concerne QUE nom et prénom
     *
     *****/
    public function modifyIndividu(Individu $individu, ServiceJournal $sj, bool $onlyNom)
    {
        if (null != $individu) {
            if (!empty($this->getNom()) && $individu->getNom() != $this->getNom()) {
                $sj->warningMessage("Le nom de l'individu ".$individu.' id('.$individu->getId().') a été modifié de '.
                                    $individu->getNom().' vers '.$this->getNom());
                $individu->setNom($this->getNom());
            }

            if (!empty($this->getPrenom()) && $individu->getPrenom() != $this->getPrenom()) {
                $sj->warningMessage("Le prénom de l'individu ".$individu.' id('.$individu->getId().') a été modifié de '.
                                    $individu->getPrenom().' vers '.$this->getPrenom());
                $individu->setPrenom($this->getPrenom());
            }

            if (!$onlyNom) {
                if ($individu->getLabo() != $this->getLaboratoire()) {
                    $sj->warningMessage("Le laboratoire de l'individu ".$individu.' id('.$individu->getId().') a été modifié de '.
                                        $individu->getLabo().' vers '.$this->getLaboratoire());
                    $individu->setLabo($this->getLaboratoire());
                }

                if ($individu->getEtab() != $this->getEtablissement()) {
                    $sj->warningMessage("L'établissement de l'individu ".$individu.' id('.$individu->getId().') a été modifié de '.
                                        $individu->getEtab().' vers '.$this->getEtablissement());
                    $individu->setEtab($this->getEtablissement());
                }

                if ($individu->getStatut() != $this->getStatut()) {
                    $sj->warningMessage("Le statut de l'individu ".$individu.' id('.$individu->getId().') a été modifié de '.
                                        $individu->getStatut().' vers '.$this->getStatut());
                    $individu->setStatut($this->getStatut());
                }
            }
        }

        return $individu;
    }
}
