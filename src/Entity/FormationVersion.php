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
 * FormationVersion
 *
 * @ORM\Table(name="formationVersion",
 *            uniqueConstraints={@ORM\UniqueConstraint(name="id_version2", columns={"id_version", "id_formation"})},
 *            indexes={@ORM\Index(name="id_formation", columns={"id_formation"}),
 *                     @ORM\Index(name="id_version", columns={"id_version"})})
 * @ORM\Entity(repositoryClass="App\Repository\FormationVersionRepository")
 */
class FormationVersion
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \App\Entity\Version
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Version", inversedBy="formationVersion")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_version", referencedColumnName="id_version")
     * })
     */
    private $version;

    /**
     * @var \App\Entity\Formation
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Formation",inversedBy="formationVersion")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_formation", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $formation;

    /**
     * @var integer
     *
     * @ORM\Column(name="nombre", type="integer", nullable=false)
     */
    private $nombre = 0;


    public function __toString(): string
    {
        $output .= 'version=' . $this->getVersion();
        $output .= 'id=' . $this->getId() . ':';
        $output .= 'formation=' . $this->getFormation();
        return $output;
    }

    public function __construct(Formation $formation = null, Version $version = null)
    {
        if ($formation != null)
        {
            $this->formation = $formation;
        }
        if ($version != null)
        {
            $this->version  =   $version;
        }
    }

    /**
     * Set nombre
     *
     * @param boolean $nombre
     *
     * @return FormationVersion
     */
    public function setNombre(int $nombre): FormationVersion
    {
        $this->nombre = $nombre;

        return $this;
    }

    /**
     * Get nombre
     *
     * @return integer
     */
    public function getNombre(): int
    {
        return $this->nombre;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set version
     *
     * @param \App\Entity\Version $version
     *
     * @return CollaborateurVersion
     */
    public function setVersion(\App\Entity\Version $version = null): FormationVersion
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return \App\Entity\Version
     */
    public function getVersion(): \App\Entity\Version
    {
        return $this->version;
    }

    /**
     * Set formation
     *
     * @param \App\Entity\Formation $formation
     *
     * @return CollaborateurFormation
     */
    public function setFormation(\App\Entity\Formation $formation = null): FormationVersion
    {
        $this->formation = $formation;

        return $this;
    }

    /**
     * Get formation
     *
     * @return \App\Entity\Formation
     */
    public function getFormation(): \App\Entity\Formation
    {
        return $this->formation;
    }

}
