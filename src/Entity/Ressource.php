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
 * Ressource
 *
 * @ORM\Table(name="ressource", indexes={@ORM\Index(name="nom", columns={"nom"})}, options={"collation"="utf8mb4_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\RessourceRepository")
 */
class Ressource
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dac = new \Doctrine\Common\Collections\ArrayCollection();
        $this->dar = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id_ressource", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idRessource;

    /**
     * @var \App\Entity\Serveur
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Serveur",inversedBy="ressource")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_serveur", referencedColumnName="nom")
     * })
     */
    private $serveur;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Dac", mappedBy="ressource", cascade={"persist"})
     */
    private $dac;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Dar", mappedBy="ressource", cascade={"persist"})
     */
    private $dar;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=8, nullable=true, options={"comment":"optionnel, voir la fonction ServiceRessources::getNomComplet"})
     * 
     */
    private $nom;

    /**
     * @var desc
     *
     * @ORM\Column(name="descr", type="string", length=2000, nullable=true)
     * 
     */
    private $desc;

    /**
     * @var docUrl
     *
     * @ORM\Column(name="doc_url", type="string", nullable=true, length=200)
     * 
     */
    private $docUrl;

    /**
     * @var unite
     * 
     * @ORM\Column(name="unite", type="string", length=20, nullable=true, options={"comment":"unité utilisée pour les allocations"}) )
     *
     ****/
    private $unite;
    
    /**
     * @var maxDem
     * 
     * @ORM\Column(name="max_dem", type="integer", nullable=true, options={"comment":"Valeur max qu'on a le droit de demander"}) )
     *
     ****/
    private $maxDem;
    
    /**
     * @var co2
     * 
     * @ORM\Column(name="co2", type="integer", nullable=true, options={"comment":"gramme de co2 émis par unite et par heure"}) )
     *
     ****/
    private $co2;
    

    /**
     * Get idRessource
     *
     * @return integer
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
     * Set serveur
     *
     * @param \App\Entity\Serveur $serveur
     *
     * @return Ressource
     */
    public function setServeur(?\App\Entity\Serveur $serveur): self
    {
        $this->serveur = $serveur;

        return $this;
    }

    /**
     * Get serveur
     *
     * @return \App\Entity\Serveur
     */
    public function getServeur(): ?\App\Entity\Serveur
    {
        return $this->serveur;
    }

    /**
     * Add dac
     *
     * @param \App\Entity\Dac $dac
     *
     * @return Version
     */
    public function addDac(\App\Entity\Dac $dac): self
    {
        if (! $this->dac->contains($dac))
        {
            $this->dac[] = $dac;
        }
        return $this;
    }

    /**
     * Remove dac
     *
     * @param \App\Entity\Dac $dac
     */
    public function removeDac(\App\Entity\Dac $dac): self
    {
        $this->dac->removeElement($dac);
        return $this;
    }

    /**
     * Get dac
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDac()
    {
        return $this->dac;
    }

    /**
     * Add dar
     *
     * @param \App\Entity\Dar $dar
     *
     * @return Version
     */
    public function addDar(\App\Entity\Dar $dar): self
    {
        if (! $this->dar->contains($dar))
        {
            $this->dar[] = $dar;
        }
        return $this;
    }

    /**
     * Remove dar
     *
     * @param \App\Entity\Dar $dar
     */
    public function removeDar(\App\Entity\Dar $dar): self
    {
        $this->dar->removeElement($dar);
        return $this;
    }

    /**
     * Get dar
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDar()
    {
        return $this->dar;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * Set nom
     *
     * @param string
     * @return Ressource
     */
    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    /**
     * Get desc
     *
     * @return string
     */
    public function getDesc(): ?string
    {
        return $this->desc;
    }

    /**
     * Set desc
     *
     * @param string
     * @return Ressource
     */
    public function setDesc(?string $desc): self
    {
        $this->desc = $desc;
        return $this;
    }

    /**
     * Get docUrl
     *
     * @return string
     */
    public function getDocUrl(): ?string
    {
        return $this->docUrl;
    }

    /**
     * Set docUrl
     *
     * @param string
     * @return Ressource
     */
    public function setDocUrl(?string $docUrl): self
    {
        $this->docUrl = $docUrl;
        return $this;
    }

    /**
     * Get Unite
     *
     * @return string
     */
    public function getUnite(): ?string
    {
        return $this->unite;
    }

    /**
     * Set Unite
     *
     * @param string
     * @return Ressource
     */
    public function setUnite(?string $unite): self
    {
        $this->unite = $unite;
        return $this;
    }

    /**
     * Get maxDem
     *
     * @return integer
     */
    public function getMaxDem(): ?int
    {
        return $this->maxDem;
    }

    /**
     * Set maxDem
     *
     * @param int
     * @return Ressource
     */
    public function setMaxDem(?int $maxDem): self
    {
        $this->maxDem = $maxDem;
        return $this;
    }

    /**
     * Get Co2
     *
     * @return integer
     */
    public function getCo2(): ?int
    {
        return $this->co2;
    }

    /**
     * Set Co2
     *
     * @param int
     * @return Ressource
     */
    public function setCo2(?int $co2): self
    {
        $this->co2 = $co2;
        return $this;
    }

    public function __toString(): string
    {
        if ($this->getNom() === null)
        {
            return '';
        }
        else
        {
            return $this->getNom();
        }
    }
}
