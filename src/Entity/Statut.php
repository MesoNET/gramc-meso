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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Sso.
 */
#[ORM\Table(name: 'statut')]
#[ORM\Index(name: 'id_statut', columns: ['id_statut'])]
#[ORM\Entity]
#[ApiResource(
    operations: []
)]
class Statut
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'id_statut', type: 'smallint')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $idStatut;

    /**
     * @var string
     */
    #[ORM\Column(name: 'libelle_statut', type: 'string', length: 50, nullable: false)]
    private $libelleStatut;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'permanent', type: 'boolean', nullable: false)]
    private $permanent = false;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Individu', mappedBy: 'statut')]
    private $individu;

    // ////////////////////////////////////////////////////

    public function __toString(): string
    {
        return $this->getLibelleStatut();
    }

    /**
     * Get idStatut.
     */
    public function getIdStatut(): ?int
    {
        return $this->idStatut;
    }

    public function getId(): ?int
    {
        return $this->getIdStatut();
    }

    /**
     * Set idStatut.
     */
    public function setIdStatut(int $idStatut): self
    {
        $this->idStatut = $idStatut;

        return $this;
    }

    // ////////////////////////////////////////////////

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->individu = new ArrayCollection();
    }

    /**
     * Set libelleStatut.
     */
    public function setLibelleStatut(string $libelleStatut): self
    {
        $this->libelleStatut = $libelleStatut;

        return $this;
    }

    /**
     * Get libelleStatut.
     */
    public function getLibelleStatut(): ?string
    {
        return $this->libelleStatut;
    }

    /**
     * Set permanent.
     */
    public function setPermanent(bool $permanent): self
    {
        $this->permanent = $permanent;

        return $this;
    }

    /**
     * Get permanent.
     */
    public function getPermanent(): bool
    {
        return $this->permanent;
    }

    /**
     * Is permanent.
     */
    public function isPermanent(): bool
    {
        return $this->getPermanent();
    }

    /**
     * Add individu.
     */
    public function addIndividu(Individu $individu): self
    {
        if (!$this->individu->contains($individu)) {
            $this->individu[] = $individu;
        }

        return $this;
    }

    /**
     * Remove individu.
     */
    public function removeIndividu(Individu $individu): self
    {
        $this->individu->removeElement($individu);

        return $this;
    }

    /**
     * Get individu.
     *
     * @return Collection
     */
    public function getIndividu()
    {
        return $this->individu;
    }
}
