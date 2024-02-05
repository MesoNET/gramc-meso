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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Laboratoire.
 */
#[ORM\Table(name: 'laboratoire')]
#[ORM\Entity(repositoryClass: 'App\Repository\LaboratoireRepository')]
#[ApiResource(
    operations: []
]
class Laboratoire
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->collaborateurVersion = new ArrayCollection();
        $this->individu = new ArrayCollection();
        $this->adresseip = new ArrayCollection();
    }

    /**
     * @var int
     */
    #[ORM\Column(name: 'numero_labo', type: 'integer', nullable: false)]
    #[Assert\NotBlank]
    private $numeroLabo = '99999';

    /**
     * @var ?string
     */
    #[ORM\Column(name: 'acro_labo', type: 'string', length: 100, nullable: true)]
    #[Assert\NotBlank]
    private $acroLabo = '';

    /**
     * @var string
     */
    #[ORM\Column(name: 'nom_labo', type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank]
    private $nomLabo = '';

    /**
     * @var int
     */
    #[ORM\Column(name: 'id_labo', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $idLabo;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\CollaborateurVersion', mappedBy: 'labo')]
    private $collaborateurVersion;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Individu', mappedBy: 'labo')]
    private $individu;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Adresseip', mappedBy: 'labo', cascade: ['remove'])]
    private $adresseip;

    #[ORM\Column(length: 50)]
    private ?string $numeroNationalStructure = '';

    #[ORM\Column(nullable: false)]
    private bool $actif = true;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $numeroDeStructureSuccesseur = null;

    public function __toString(): string
    {
        if (null != $this->getAcroLabo() && null != $this->getNomLabo()) {
            return $this->getAcroLabo().' - '.$this->getNomLabo();
        } elseif (null != $this->getAcroLabo()) {
            return $this->getAcroLabo();
        } elseif (null != $this->getNomLabo()) {
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
     * Set numeroLabo.
     */
    public function setNumeroLabo(int $numeroLabo): self
    {
        $this->numeroLabo = $numeroLabo;

        return $this;
    }

    /**
     * Get numeroLabo.
     */
    public function getNumeroLabo(): ?int
    {
        return $this->numeroLabo;
    }

    /**
     * Set acroLabo.
     */
    public function setAcroLabo(?string $acroLabo): self
    {
        $this->acroLabo = $acroLabo;

        return $this;
    }

    /**
     * Get acroLabo.
     */
    public function getAcroLabo(): ?string
    {
        return $this->acroLabo;
    }

    /**
     * Set nomLabo.
     */
    public function setNomLabo(string $nomLabo): self
    {
        $this->nomLabo = $nomLabo;

        return $this;
    }

    /**
     * Get nomLabo.
     */
    public function getNomLabo(): ?string
    {
        return $this->nomLabo;
    }

    /**
     * Get idLabo.
     *
     * @return int
     */
    public function getIdLabo()
    {
        return $this->idLabo;
    }

    /**
     * Add collaborateurVersion.
     */
    public function addCollaborateurVersion(CollaborateurVersion $collaborateurVersion): self
    {
        if (!$this->collaborateurVersion->contains($collaborateurVersion)) {
            $this->collaborateurVersion[] = $collaborateurVersion;
        }

        return $this;
    }

    /**
     * Remove collaborateurVersion.
     */
    public function removeCollaborateurVersion(CollaborateurVersion $collaborateurVersion): self
    {
        $this->collaborateurVersion->removeElement($collaborateurVersion);

        return $this;
    }

    /**
     * Get collaborateurVersion.
     *
     * @return Collection
     */
    public function getCollaborateurVersion()
    {
        return $this->collaborateurVersion;
    }

    /**
     * Add adresseip.
     *
     * @return Projet
     */
    public function addAdresseip(Adresseip $adresseip): self
    {
        if (!$this->adresseip->contains($adresseip)) {
            $this->adresseip[] = $adresseip;
        }

        return $this;
    }

    /**
     * Remove adresseip.
     */
    public function removeAdresseip(Adresseip $adresseip): self
    {
        $this->adresseip->removeElement($adresseip);

        return $this;
    }

    /**
     * Get adresseip.
     *
     * @return Collection
     */
    public function getAdresseip()
    {
        return $this->adresseip;
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
     */
    public function getIndividu(): Collection
    {
        return $this->individu;
    }

    // ////////////////////////////////////////////////////////////////////

    public function isLaboRegional(): bool
    {
        return $this->idLabo > 1;
    }

    public function getNumeroNationalStructure(): ?string
    {
        return $this->numeroNationalStructure;
    }

    public function setNumeroNationalStructure(string $numeroNationalStructure): static
    {
        $this->numeroNationalStructure = $numeroNationalStructure;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getNumeroDeStructureSuccesseur(): ?string
    {
        return $this->numeroDeStructureSuccesseur;
    }

    public function setNumeroDeStructureSuccesseur(?string $numeroDeStructureSuccesseur): static
    {
        $this->numeroDeStructureSuccesseur = $numeroDeStructureSuccesseur;

        return $this;
    }
}
