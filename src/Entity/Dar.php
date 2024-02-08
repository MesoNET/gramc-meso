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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dar.
 *
 * Demande, Attribution pour Rallonges
 */
#[ORM\Table(name: 'dar', options: ['collation' => 'utf8mb4_general_ci'])]
#[ORM\Entity]
#[ApiResource(
    operations: []
)]
class Dar
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id_dar', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $idDar;

    #[ORM\JoinColumn(name: 'id_rallonge', referencedColumnName: 'id_rallonge')]
    #[ORM\Column(name: 'id_rallonge', type: 'string', length: 15)]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Rallonge', inversedBy: 'dar')]
    private $rallonge;

    /**
     * @var Ressource
     */
    #[ORM\JoinColumn(name: 'id_ressource', referencedColumnName: 'id_ressource')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Ressource', inversedBy: 'dar')]
    private $ressource;

    /**
     * @var int
     */
    #[ORM\Column(name: 'demande', type: 'integer', nullable: false, options: ['comment' => "demande, l'unité est celle de la ressource associée"])]
    private $demande = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'attribution', type: 'integer', nullable: false, options: ['comment' => "attribution, l'unité est celle de la ressource associée"])]
    private $attribution = 0;

    /**
     * @var bool
     */
    // Le "todo flag": si true, il y a un truc à faire sur la machine !
    #[ORM\Column(name: 'todof', type: 'boolean')]
    private $todof = false;

    /**
     * Get idDar.
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
        if (null != $ressource) {
            $this->ressource = $ressource;
        }
        if (null != $rallonge) {
            $this->rallonge = $rallonge;
        }
    }

    /**
     * Set rallonge.
     */
    public function setRallonge(?Rallonge $rallonge): self
    {
        $this->rallonge = $rallonge;

        return $this;
    }

    /**
     * Get rallonge.
     */
    public function getRallonge(): ?Rallonge
    {
        return $this->rallonge;
    }

    /**
     * Set ressource.
     */
    public function setRessource(?Ressource $ressource): self
    {
        $this->ressource = $ressource;

        return $this;
    }

    /**
     * Get ressource.
     */
    public function getRessource(): ?Ressource
    {
        return $this->ressource;
    }

    /**
     * Get demande.
     */
    public function getDemande(): ?int
    {
        return $this->demande;
    }

    /**
     * Set demande.
     *
     * @param int
     */
    public function setDemande(int $demande): self
    {
        $this->demande = $demande;

        return $this;
    }

    /**
     * Get attribution.
     */
    public function getAttribution(): ?int
    {
        return $this->attribution;
    }

    /**
     * Set attribution.
     *
     * @param int
     */
    public function setAttribution(int $attribution): self
    {
        $this->attribution = $attribution;

        return $this;
    }

    /**
     * Set todof.
     *
     * @return Version
     */
    public function setTodof(bool $todof): self
    {
        $this->todof = $todof;

        return $this;
    }

    /**
     * Get todof.
     */
    public function getTodof(): bool
    {
        return $this->todof;
    }

    public function isTodof(): ?bool
    {
        return $this->todof;
    }
}
