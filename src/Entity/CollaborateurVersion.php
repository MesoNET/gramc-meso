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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CollaborateurVersion
 */
#[ORM\Table(name: 'collaborateurVersion')]
#[ORM\Index(name: 'id_coll_labo', columns: ['id_coll_labo'])]
#[ORM\Index(name: 'id_coll_statut', columns: ['id_coll_statut'])]
#[ORM\Index(name: 'id_coll_etab', columns: ['id_coll_etab'])]
#[ORM\Index(name: 'collaborateur_collaborateurprojet_fk', columns: ['id_collaborateur'])]
#[ORM\Index(name: 'id_version', columns: ['id_version'])]
#[ORM\UniqueConstraint(name: 'id_version_2', columns: ['id_version', 'id_collaborateur'])]
#[ORM\Entity(repositoryClass: 'App\Repository\CollaborateurVersionRepository')]
class CollaborateurVersion
{

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'responsable', type: 'boolean', nullable: false)]
    private $responsable;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'deleted', type: 'boolean', nullable: false, options: ['comment' => 'supprimé prochainement'])]
    private $deleted = false;

    /**
     * @var \App\Entity\Statut
     */
    #[ORM\JoinColumn(name: 'id_coll_statut', referencedColumnName: 'id_statut')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Statut')]
    private $statut;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \App\Entity\Version
     */
    #[ORM\JoinColumn(name: 'id_version', referencedColumnName: 'id_version', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Version', inversedBy: 'collaborateurVersion')]
    private $version;

    /**
     * @var \App\Entity\Laboratoire
     */
    #[ORM\JoinColumn(name: 'id_coll_labo', referencedColumnName: 'id_labo')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Laboratoire', inversedBy: 'collaborateurVersion')]
    private $labo;

    /**
     * @var \App\Entity\Etablissement
     */
    #[ORM\JoinColumn(name: 'id_coll_etab', referencedColumnName: 'id_etab')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Etablissement', inversedBy: 'collaborateurVersion')]
    private $etab;

    /**
     * @var \App\Entity\Individu
     */
    #[ORM\JoinColumn(name: 'id_collaborateur', referencedColumnName: 'id_individu')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Individu', inversedBy: 'collaborateurVersion')]
    private $collaborateur;

    public function __toString()
    {
        $output = '{';
        if ($this->getResponsable() === true) {
            $output .= 'responsable:';
        }
        $output .= 'version=' . $this->getVersion() .':';
        $output .= 'id=' . $this->getId() . ':';
        $output .= 'statut=' .$this->getStatut() .':';
        $output .= 'labo=' . $this->getLabo() .':';
        $output .= 'etab=' .$this->getEtab() .':';
        $output .= 'collab=' .$this->getCollaborateur() .'}';
        return $output;
    }

    public function __construct(Individu $individu = null, Version $version = null)
    {
        $this->responsable = false;

        if ($individu != null) {
            $this->statut = $individu->getStatut();
            $this->labo = $individu->getLabo();
            $this->etab = $individu->getEtab();
            $this->collaborateur = $individu;
        }

        if ($version != null) {
            $this->version  =   $version;
        }
    }

    /*
     *  Lors du clonage d'un CollaborateurVersion, on recherche les informations
     * sur le collaborateur, car elles peuvent avoir changé
     *
     */
    public function __clone()
    {
        $individu = $this->getCollaborateur();
        $this->statut = $individu->getStatut();
        $this->labo = $individu->getLabo();
        $this->etab = $individu->getEtab();
    }
        
    /**
     * Set responsable
     *
     * @param boolean $responsable
     *
     * @return CollaborateurVersion
     */
    public function setResponsable(bool $responsable): self
    {
        $this->responsable = $responsable;

        return $this;
    }

    /**
     * Get responsable
     *
     * @return boolean
     */
    public function getResponsable(): bool
    {
        return $this->responsable;
    }

    /**
     * Set deleted
     *
     * @param boolean $deleted
     *
     * @return CollaborateurVersion
     */
    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return boolean
     */
    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set statut
     *
     * @param \App\Entity\Statut $statut
     *
     * @return CollaborateurVersion
     */
    public function setStatut(?\App\Entity\Statut $statut = null): self
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get statut
     *
     * @return \App\Entity\Statut
     */
    public function getStatut(): ?\App\Entity\Statut
    {
        return $this->statut;
    }

    /**
     * Set version
     *
     * @param \App\Entity\Version $version
     *
     * @return CollaborateurVersion
     */
    public function setVersion(?\App\Entity\Version $version = null): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return \App\Entity\Version
     */
    public function getVersion(): ?\App\Entity\Version
    {
        return $this->version;
    }

    /**
     * Set labo
     *
     * @param \App\Entity\Laboratoire $labo
     *
     * @return CollaborateurVersion
     */
    public function setLabo(?\App\Entity\Laboratoire $labo = null): self
    {
        $this->labo = $labo;

        return $this;
    }

    /**
     * Get labo
     *
     * @return \App\Entity\Laboratoire
     */
    public function getLabo(): ?\App\Entity\Laboratoire
    {
        return $this->labo;
    }

    /**
     * Set etab
     *
     * @param \App\Entity\Etablissement $etab
     *
     * @return CollaborateurVersion
     */
    public function setEtab(?\App\Entity\Etablissement $etab = null): self
    {
        $this->etab = $etab;

        return $this;
    }

    /**
     * Get etab
     *
     * @return \App\Entity\Etablissement
     */
    public function getEtab(): ?\App\Entity\Etablissement
    {
        return $this->etab;
    }

    /**
     * Set collaborateur
     *
     * @param \App\Entity\Individu $collaborateur
     *
     * @return CollaborateurVersion
     */
    public function setCollaborateur(?\App\Entity\Individu $collaborateur = null): self
    {
        $this->collaborateur = $collaborateur;

        return $this;
    }

    /**
     * Get collaborateur
     *
     * @return \App\Entity\Individu
     */
    public function getCollaborateur(): ?\App\Entity\Individu
    {
        return $this->collaborateur;
    }

    public function isResponsable(): ?bool
    {
        return $this->responsable;
    }

    public function isDeleted(): ?bool
    {
        return $this->deleted;
    }
}
