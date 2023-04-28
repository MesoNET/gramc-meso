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
 * Dar
 *
 * Demande, Attribution pour Rallonges
 * 
 * @ORM\Table(name="dar", options={"collation"="utf8mb4_general_ci"})

 * @ORM\Entity
 */
class Dar
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_dar", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idDar;

    /**
     * @var \App\Entity\Rallonge
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Rallonge",inversedBy="dar")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_rallonge", referencedColumnName="id_rallonge")
     * })
     */
    private $rallonge;

    /**
     * @var \App\Entity\Ressource
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Ressource", inversedBy="dar")
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
     * Get idDar
     *
     * @return integer
     */
    public function getIdDar(): ?int
    {
        return $this->idDar;
    }

    public function getId(): ?int
    {
        return $this->idDar;
    }

    public function __construct(Ressource $ressource = null, Rallonge $rallonge = null)
    {
        if ($ressource != null)
        {
            $this->ressource = $ressource;
        }
        if ($rallonge != null)
        {
            $this->rallonge = $rallonge;
        }
    }

    /**
     * Set rallonge
     *
     * @param \App\Entity\Rallonge $rallonge
     *
     * @return Dar
     */
    public function setRallonge(?\App\Entity\Rallonge $rallonge): self
    {
        $this->rallonge = $rallonge;

        return $this;
    }

    /**
     * Get rallonge
     *
     * @return \App\Entity\Rallonge
     */
    public function getRallonge(): ?\App\Entity\Rallonge
    {
        return $this->rallonge;
    }

    /**
     * Set ressource
     *
     * @param \App\Entity\Ressource $ressource
     *
     * @return Dar
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
     * @return Dar
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
     * @return Dar
     */
    public function setAttribution(int $attribution): self
    {
        $this->attribution = $attribution;
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
