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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Laboratoire
 *
 * @ORM\Table(name="laboratoire",
 *            uniqueConstraints={@ORM\UniqueConstraint(name="acro", columns={"acro_labo"})})
 * @ORM\Entity(repositoryClass="App\Repository\LaboratoireRepository")
 * 
 */
class Laboratoire
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->collaborateurVersion = new \Doctrine\Common\Collections\ArrayCollection();
        $this->individu = new \Doctrine\Common\Collections\ArrayCollection();
        $this->adresseip = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="numero_labo", type="integer", nullable=false)
     * @Assert\NotBlank
     */
    private $numeroLabo = '99999';

    /**
     * @var string
     *
     * @ORM\Column(name="acro_labo", type="string", length=100, nullable=false)
     * @Assert\NotBlank
     */
    private $acroLabo = '';

    /**
     * @var string
     *
     * @ORM\Column(name="nom_labo", type="string", length=100, nullable=false)
     * @Assert\NotBlank
     */
    private $nomLabo = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="id_labo", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLabo;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\CollaborateurVersion", mappedBy="labo")
     */
    private $collaborateurVersion;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Individu", mappedBy="labo")
     */
    private $individu;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Adresseip", mappedBy="labo", cascade={"remove"} )
     * 
     */
    private $adresseip;

    public function __toString(): string
    {
        if ($this->getAcroLabo() != null && $this->getNomLabo() != null) {
            return $this->getAcroLabo() . ' - ' . $this->getNomLabo();
        } elseif ($this->getAcroLabo() != null) {
            return $this->getAcroLabo();
        } elseif ($this->getNomLabo() != null) {
            return $this->getNomLabo();
        } else {
            return $this->getIdLabo();
        }
    }

    public function getId(): ?int
    {
        return $this->getIdLabo();
    }

    /**
     * Set numeroLabo
     *
     * @param integer $numeroLabo
     *
     * @return Laboratoire
     */
    public function setNumeroLabo(int $numeroLabo): self
    {
        $this->numeroLabo = $numeroLabo;

        return $this;
    }

    /**
     * Get numeroLabo
     *
     * @return integer
     */
    public function getNumeroLabo(): ?int
    {
        return $this->numeroLabo;
    }

    /**
     * Set acroLabo
     *
     * @param string $acroLabo
     *
     * @return Laboratoire
     */
    public function setAcroLabo(string $acroLabo): self
    {
        $this->acroLabo = $acroLabo;

        return $this;
    }

    /**
     * Get acroLabo
     *
     * @return string
     */
    public function getAcroLabo(): ?string
    {
        return $this->acroLabo;
    }

    /**
     * Set nomLabo
     *
     * @param string $nomLabo
     *
     * @return Laboratoire
     */
    public function setNomLabo(string $nomLabo): self
    {
        $this->nomLabo = $nomLabo;

        return $this;
    }

    /**
     * Get nomLabo
     *
     * @return string
     */
    public function getNomLabo(): ?string
    {
        return $this->nomLabo;
    }

    /**
     * Get idLabo
     *
     * @return integer
     */
    public function getIdLabo()
    {
        return $this->idLabo;
    }

    /**
     * Add collaborateurVersion
     *
     * @param \App\Entity\CollaborateurVersion $collaborateurVersion
     *
     * @return Laboratoire
     */
    public function addCollaborateurVersion(\App\Entity\CollaborateurVersion $collaborateurVersion): self
    {
        if ( ! $this->collaborateurVersion->contains($collaborateurVersion))
        {
            $this->collaborateurVersion[] = $collaborateurVersion;
        }

        return $this;
    }

    /**
     * Remove collaborateurVersion
     *
     * @param \App\Entity\CollaborateurVersion $collaborateurVersion
     */
    public function removeCollaborateurVersion(\App\Entity\CollaborateurVersion $collaborateurVersion): self
    {
        $this->collaborateurVersion->removeElement($collaborateurVersion);
        return $this;
    }

    /**
     * Get collaborateurVersion
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCollaborateurVersion()
    {
        return $this->collaborateurVersion;
    }

    /**
     * Add adresseip
     *
     * @param \App\Entity\Adresseip $adresseip
     *
     * @return Projet
     */
    public function addAdresseip(\App\Entity\Adresseip $adresseip): self
    {
        if (! $this->adresseip->contains($adresseip)) {
            $this->adresseip[] = $adresseip;
        }

        return $this;
    }

    /**
     * Remove adresseip
     *
     * @param \App\Entity\Adresseip $adresseip
     */
    public function removeAdresseip(\App\Entity\Adresseip $adresseip): self
    {
        $this->adresseip->removeElement($adresseip);
        return $this;
    }

    /**
     * Get adresseip
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAdresseip()
    {
        return $this->adresseip;
    }

    /**
     * Add individu
     *
     * @param \App\Entity\Individu $individu
     *
     * @return Laboratoire
     */
    public function addIndividu(\App\Entity\Individu $individu): self
    {
        if (! $this->individu->contains($individu))
        {
            $this->individu[] = $individu;
        }

        return $this;
    }

    /**
     * Remove individu
     *
     * @param \App\Entity\Individu $individu
     */
    public function removeIndividu(\App\Entity\Individu $individu): self
    {
        $this->individu->removeElement($individu);
        return $this;
    }

    /**
     * Get individu
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIndividu(): \Doctrine\Common\Collections\Collection
    {
        return $this->individu;
    }

    //////////////////////////////////////////////////////////////////////

    public function isLaboRegional() : bool
    {
        return $this->idLabo > 1;
    }
}
