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
 * Expa
 *
 * Expertise - Attribution
 * 
 * @ORM\Table(name="expa", options={"collation"="utf8mb4_general_ci"})

 * @ORM\Entity
 */
class Expa
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_dac", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idExpa;

    /**
     * @var \App\Entity\Expertise
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Expertise")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id", referencedColumnName="id")
     * })
     */
    private $expertise;

    /**
     * @var \App\Entity\Ressource
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Ressource")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_ressource", referencedColumnName="id_ressource")
     * })
     */
    private $ressource;

    /**
     * @var integer
     *
     * @ORM\Column(name="attribution", type="integer", options={"comment":"attribution, l'unité est celle de la ressource associée"})
     * 
     */
    private $attribution=0;

    /**
     * Get idExpa
     *
     * @return integer
     */
    public function getIdExpa(): int
    {
        return $this->idExpa;
    }

    public function getId(): int
    {
        return $this->idExpa;
    }

    /**
     * Set Expertise
     *
     * @param \App\Entity\Expertise $expertise
     *
     * @return Expa
     */
    public function setExpertise(\App\Entity\Expertise $expertise): self
    {
        $this->expertise = $expertise;

        return $this;
    }

    /**
     * Get Expertise
     *
     * @return \App\Entity\Expertise
     */
    public function getExpertise(): \App\Entity\Expertise
    {
        return $this->expertise;
    }

    /**
     * Set ressource
     *
     * @param \App\Entity\Ressource $ressource
     *
     * @return Dac
     */
    public function setRessource(\App\Entity\Ressource $ressource): self
    {
        $this->ressource = $ressource;

        return $this;
    }

    /**
     * Get ressource
     *
     * @return \App\Entity\Ressource
     */
    public function getRessource()
    {
        return $this->ressource;
    }

    /**
     * Get attribution
     *
     * @return integer
     */
    public function getAttribution(): int
    {
        return $this->attribution;
    }

    /**
     * Set attribution
     *
     * @param integer
     * @return Dac
     */
    public function setAttribution(int $attribution): Self
    {
        $this->attribution = $attribution;
        return $this;
    }


    public function __toString(): string
    {
        return $this->getId();
    }
}
