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
 * Dac
 *
 * Demande, Attribution, consommation
 * 
 * @ORM\Table(name="dac", options={"collation"="utf8mb4_general_ci"})

 * @ORM\Entity
 */
class Dac
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_dac", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idDac;

    /**
     * @var \App\Entity\Version
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Version",inversedBy="dac")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_version", referencedColumnName="id_version")
     * })
     */
    private $version;

    /**
     * @var \App\Entity\Ressource
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Ressource", inversedBy="dac")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_ressource", referencedColumnName="id_ressource")
     * })
     */
    private $ressource;

    /**
     * @var integer
     *
     * @ORM\Column(name="demande", type="integer", nullable=false, options={"comment":"demande, l'unité est celle de la ressource associée"})
     * 
     */
    private $demande = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="attribution", type="integer", nullable=false, options={"comment":"attribution, l'unité est celle de la ressource associée"})
     * 
     */
    private $attribution = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="todof", type="boolean")
     */
     // Le "todo flag": si true, il y a un truc à faire sur la machine !

    private $todof = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="consommation", type="integer", nullable=false, options={"comment":"consommation, l'unité est celle de la ressource associée"})
     * 
     */
    private $consommation = 0;

    /**
     * Get idDac
     *
     * @return integer
     */
    public function getIdDac(): ?int
    {
        return $this->idDac;
    }

    public function getId(): ?int
    {
        return $this->idDac;
    }

    public function __construct(Ressource $ressource = null, Version $version = null)
    {
        if ($ressource != null)
        {
            $this->ressource = $ressource;
        }
        if ($version != null)
        {
            $this->version  =   $version;
        }
    }

    /**
     * Set version
     *
     * @param \App\Entity\Version $version
     *
     * @return Dac
     */
    public function setVersion(?\App\Entity\Version $version): self
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
     * Set ressource
     *
     * @param \App\Entity\Ressource $ressource
     *
     * @return Dac
     */
    public function setRessource(?\App\Entity\Ressource $ressource): self
    {
        $this->ressource = $ressource;

        return $this;
    }

    /**
     * Get ressource
     *
     * @return \App\Entity\Ressource
     */
    public function getRessource(): ?\App\Entity\Ressource
    {
        return $this->ressource;
    }

    /**
     * Get demande
     *
     * @return integer
     */
    public function getDemande(): ?int
    {
        return $this->demande;
    }

    /**
     * Set demande
     *
     * @param int
     * @return Dac
     */
    public function setDemande(int $demande): self
    {
        $this->demande = $demande;
        return $this;
    }

    /**
     * Get attribution
     *
     * @return integer
     */
    public function getAttribution(): ?int
    {
        return $this->attribution;
    }

    /**
     * Set attribution
     *
     * @param integer
     * @return Dac
     */
    public function setAttribution(int $attribution): self
    {
        $this->attribution = $attribution;
        return $this;
    }

    /**
     * Get consommation
     *
     * @return integer
     */
    public function getConsommation(): ?int
    {
        return $this->consommation;
    }

    /**
     * Set consommation
     *
     * @param integer
     * @return Dac
     */
    public function setConsommation(int $consommation): self
    {
        $this->consommation = $consommation;
        return $this;
    }

    /**
     * Set todof
     *
     * @param boolean $todof
     *
     * @return Version
     */
    public function setTodof(bool $todof): self
    {
        $this->todof = $todof;

        return $this;
    }

    /**
     * Get todof
     *
     * @return boolean
     */
    public function getTodof(): bool
    {
        return $this->todof;
    }
}
