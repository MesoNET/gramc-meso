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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Thematique.
 */
#[ORM\Table(name: 'thematique')]
#[ORM\Entity]
class Thematique
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'libelle_thematique', type: 'string', length: 200, nullable: false)]
    private $libelleThematique;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id_thematique', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $idThematique;

    /**
     * @var Collection
     */
    #[ORM\JoinTable(name: 'thematiqueExpert')]
    #[ORM\JoinColumn(name: 'id_thematique', referencedColumnName: 'id_thematique')]
    #[ORM\InverseJoinColumn(name: 'id_expert', referencedColumnName: 'id_individu')]
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Individu', inversedBy: 'thematique')]
    private $expert;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Version', mappedBy: 'prjThematique')]
    private $version;

    // ////////////////////////////////////////////////////////

    public function getId(): ?int
    {
        return $this->getIdThematique();
    }

    public function __toString(): string
    {
        return $this->getLibelleThematique();
    }

    // ////////////////////////////////////////////////////////

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->expert = new ArrayCollection();
        $this->version = new ArrayCollection();
        // $this->projetTest = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set libelleThematique.
     */
    public function setLibelleThematique(string $libelleThematique): self
    {
        $this->libelleThematique = $libelleThematique;

        return $this;
    }

    /**
     * Get libelleThematique.
     */
    public function getLibelleThematique(): ?string
    {
        return $this->libelleThematique;
    }

    /**
     * Get idThematique.
     */
    public function getIdThematique(): ?int
    {
        return $this->idThematique;
    }

    /**
     * Add expert.
     */
    public function addExpert(Individu $expert): self
    {
        if (!$this->expert->contains($expert)) {
            $this->expert[] = $expert;
        }

        return $this;
    }

    /**
     * Remove expert.
     */
    public function removeExpert(Individu $expert): self
    {
        $this->expert->removeElement($expert);

        return $this;
    }

    /**
     * Get expert.
     *
     * @return Collection
     */
    public function getExpert()
    {
        return $this->expert;
    }

    /**
     * Add version.
     */
    public function addVersion(Version $version): self
    {
        if (!$this->version->contains($version)) {
            $this->version[] = $version;
        }

        return $this;
    }

    /**
     * Remove version.
     */
    public function removeVersion(Version $version): self
    {
        $this->version->removeElement($version);

        return $this;
    }

    /**
     * Get version.
     *
     * @return Collection
     */
    public function getVersion()
    {
        return $this->version;
    }
}
