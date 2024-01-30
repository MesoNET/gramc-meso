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
 * Ressource.
 */
#[ORM\Table(name: 'ressource', options: ['collation' => 'utf8mb4_general_ci'])]
#[ORM\Index(name: 'nom', columns: ['nom'])]
#[ORM\Entity(repositoryClass: 'App\Repository\RessourceRepository')]
#[ApiResource]
class Ressource
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->dac = new ArrayCollection();
        $this->dar = new ArrayCollection();
    }

    /**
     * @var int
     */
    #[ORM\Column(name: 'id_ressource', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $idRessource;

    /**
     * @var Serveur
     */
    #[ORM\JoinColumn(name: 'id_serveur', referencedColumnName: 'nom')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Serveur', inversedBy: 'ressource')]
    private $serveur;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Dac', mappedBy: 'ressource', cascade: ['persist'])]
    private $dac;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Dar', mappedBy: 'ressource', cascade: ['persist'])]
    private $dar;

    /**
     * @var string
     */
    #[ORM\Column(name: 'nom', type: 'string', length: 8, nullable: true, options: ['comment' => 'optionnel, voir la fonction ServiceRessources::getNomComplet'])]
    private $nom;

    /**
     * @var desc
     */
    #[ORM\Column(name: 'descr', type: 'string', length: 2000, nullable: true)]
    private $desc;

    /**
     * @var docUrl
     */
    #[ORM\Column(name: 'doc_url', type: 'string', nullable: true, length: 200)]
    private $docUrl;

    /**
     * @var unite
     *
     *
     ****/
    #[ORM\Column(name: 'unite', type: 'string', length: 20, nullable: true, options: ['comment' => 'unité utilisée pour les allocations'])]
    private $unite;

    /**
     * @var maxDem
     *
     *
     ****/
    #[ORM\Column(name: 'max_dem', type: 'integer', nullable: true, options: ['comment' => "Valeur max qu'on a le droit de demander"])]
    private $maxDem;

    /**
     * @var co2
     *
     *
     ****/
    #[ORM\Column(name: 'co2', type: 'integer', nullable: true, options: ['comment' => 'gramme de co2 émis par unite et par heure'])]
    private $co2;

    /**
     * Get idRessource.
     */
    public function getIdRessource(): ?int
    {
        return $this->idRessource;
    }

    public function getId(): ?int
    {
        return $this->idRessource;
    }

    /**
     * Set serveur.
     */
    public function setServeur(?Serveur $serveur): self
    {
        $this->serveur = $serveur;

        return $this;
    }

    /**
     * Get serveur.
     */
    public function getServeur(): ?Serveur
    {
        return $this->serveur;
    }

    /**
     * Add dac.
     *
     * @return Version
     */
    public function addDac(Dac $dac): self
    {
        if (!$this->dac->contains($dac)) {
            $this->dac[] = $dac;
        }

        return $this;
    }

    /**
     * Remove dac.
     */
    public function removeDac(Dac $dac): self
    {
        $this->dac->removeElement($dac);

        return $this;
    }

    /**
     * Get dac.
     *
     * @return Collection
     */
    public function getDac()
    {
        return $this->dac;
    }

    /**
     * Add dar.
     *
     * @return Version
     */
    public function addDar(Dar $dar): self
    {
        if (!$this->dar->contains($dar)) {
            $this->dar[] = $dar;
        }

        return $this;
    }

    /**
     * Remove dar.
     */
    public function removeDar(Dar $dar): self
    {
        $this->dar->removeElement($dar);

        return $this;
    }

    /**
     * Get dar.
     *
     * @return Collection
     */
    public function getDar()
    {
        return $this->dar;
    }

    /**
     * Get nom.
     */
    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * Set nom.
     *
     * @param string
     */
    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get desc.
     */
    public function getDesc(): ?string
    {
        return $this->desc;
    }

    /**
     * Set desc.
     *
     * @param string
     */
    public function setDesc(?string $desc): self
    {
        $this->desc = $desc;

        return $this;
    }

    /**
     * Get docUrl.
     */
    public function getDocUrl(): ?string
    {
        return $this->docUrl;
    }

    /**
     * Set docUrl.
     *
     * @param string
     */
    public function setDocUrl(?string $docUrl): self
    {
        $this->docUrl = $docUrl;

        return $this;
    }

    /**
     * Get Unite.
     */
    public function getUnite(): ?string
    {
        return $this->unite;
    }

    /**
     * Set Unite.
     *
     * @param string
     */
    public function setUnite(?string $unite): self
    {
        $this->unite = $unite;

        return $this;
    }

    /**
     * Get maxDem.
     */
    public function getMaxDem(): ?int
    {
        return $this->maxDem;
    }

    /**
     * Set maxDem.
     *
     * @param int
     */
    public function setMaxDem(?int $maxDem): self
    {
        $this->maxDem = $maxDem;

        return $this;
    }

    /**
     * Get Co2.
     */
    public function getCo2(): ?int
    {
        return $this->co2;
    }

    /**
     * Set Co2.
     *
     * @param int
     */
    public function setCo2(?int $co2): self
    {
        $this->co2 = $co2;

        return $this;
    }

    public function __toString(): string
    {
        if (null === $this->getNom()) {
            return '';
        } else {
            return $this->getNom();
        }
    }
}
