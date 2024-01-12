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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CollaborateurVersion.
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
     * @var bool
     */
    #[ORM\Column(name: 'responsable', type: 'boolean', nullable: false)]
    private $responsable;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'deleted', type: 'boolean', nullable: false, options: ['comment' => 'supprimé prochainement'])]
    private $deleted = false;

    /**
     * @var Statut
     */
    #[ORM\JoinColumn(name: 'id_coll_statut', referencedColumnName: 'id_statut')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Statut')]
    private $statut;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var Version
     */
    #[ORM\JoinColumn(name: 'id_version', referencedColumnName: 'id_version', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Version', inversedBy: 'collaborateurVersion')]
    private $version;

    /**
     * @var Laboratoire
     */
    #[ORM\JoinColumn(name: 'id_coll_labo', referencedColumnName: 'id_labo')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Laboratoire', inversedBy: 'collaborateurVersion')]
    private $labo;

    /**
     * @var Etablissement
     */
    #[ORM\JoinColumn(name: 'id_coll_etab', referencedColumnName: 'id_etab')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Etablissement', inversedBy: 'collaborateurVersion')]
    private $etab;

    /**
     * @var Individu
     */
    #[ORM\JoinColumn(name: 'id_collaborateur', referencedColumnName: 'id_individu')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Individu', inversedBy: 'collaborateurVersion')]
    private $collaborateur;

    public function __toString()
    {
        $output = '{';
        if (true === $this->getResponsable()) {
            $output .= 'responsable:';
        }
        $output .= 'version='.$this->getVersion().':';
        $output .= 'id='.$this->getId().':';
        $output .= 'statut='.$this->getStatut().':';
        $output .= 'labo='.$this->getLabo().':';
        $output .= 'etab='.$this->getEtab().':';
        $output .= 'collab='.$this->getCollaborateur().'}';

        return $output;
    }

    public function __construct(Individu $individu = null, Version $version = null)
    {
        $this->responsable = false;

        if (null != $individu) {
            $this->statut = $individu->getStatut();
            $this->labo = $individu->getLabo();
            $this->etab = $individu->getEtab();
            $this->collaborateur = $individu;
        }

        if (null != $version) {
            $this->version = $version;
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
     * Set responsable.
     */
    public function setResponsable(bool $responsable): self
    {
        $this->responsable = $responsable;

        return $this;
    }

    /**
     * Get responsable.
     */
    public function getResponsable(): bool
    {
        return $this->responsable;
    }

    /**
     * Set deleted.
     */
    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted.
     */
    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set statut.
     */
    public function setStatut(Statut $statut = null): self
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get statut.
     */
    public function getStatut(): ?Statut
    {
        return $this->statut;
    }

    /**
     * Set version.
     */
    public function setVersion(Version $version = null): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     */
    public function getVersion(): ?Version
    {
        return $this->version;
    }

    /**
     * Set labo.
     */
    public function setLabo(Laboratoire $labo = null): self
    {
        $this->labo = $labo;

        return $this;
    }

    /**
     * Get labo.
     */
    public function getLabo(): ?Laboratoire
    {
        return $this->labo;
    }

    /**
     * Set etab.
     */
    public function setEtab(Etablissement $etab = null): self
    {
        $this->etab = $etab;

        return $this;
    }

    /**
     * Get etab.
     */
    public function getEtab(): ?Etablissement
    {
        return $this->etab;
    }

    /**
     * Set collaborateur.
     */
    public function setCollaborateur(Individu $collaborateur = null): self
    {
        $this->collaborateur = $collaborateur;

        return $this;
    }

    /**
     * Get collaborateur.
     */
    public function getCollaborateur(): ?Individu
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
