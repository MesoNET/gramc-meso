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
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Sso.
 */
#[ORM\Table(name: 'clessh')]
#[ORM\UniqueConstraint(name: 'nom_individu', columns: ['id_individu', 'nom'])]
#[ORM\UniqueConstraint(name: 'pubuniq', columns: ['emp'])]
#[ORM\Entity]
#[ApiResource]
class Clessh
{
    public function __construct()
    {
        $this->user = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getNom();
    }

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups('individu_lecture')]
    private $id;

    /**
     * @var Individu
     *
     * ORM\Column(name="id_individu", type="integer")
     */
    #[ORM\JoinColumn(name: 'id_individu', referencedColumnName: 'id_individu')]
    #[ORM\ManyToOne(targetEntity: 'Individu', inversedBy: 'clessh')]
    private $individu;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: 'User', mappedBy: 'clessh')]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'nom', type: 'string', length: 20)]
    #[Groups('individu_lecture')]
    private $nom;

    /**
     * @var string
     */
    #[ORM\Column(name: 'pub', type: 'string', length: 5000)]
    #[Groups('individu_lecture')]
    private $pub;

    /**
     * @var string
     */
    #[ORM\Column(name: 'emp', type: 'string', length: 100, nullable: false)]
    #[Groups('individu_lecture')]
    private $emp;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'rvk', type: 'boolean')]
    #[Groups('individu_lecture')]
    private $rvk = false;

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set individu.
     */
    public function setIndividu(Individu $individu = null): self
    {
        $this->individu = $individu;

        return $this;
    }

    /**
     * Get individu.
     */
    public function getIndividu(): ?Individu
    {
        return $this->individu;
    }

    /**
     * Add user.
     */
    public function adduser(User $user): self
    {
        if (!$this->user->contains($user)) {
            $this->user[] = $user;
        }

        return $this;
    }

    /**
     * Remove user.
     */
    public function removeUser(User $user): self
    {
        $this->user->removeElement($user);

        return $this;
    }

    /**
     * Get user.
     *
     * @return Collection
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set nom.
     *
     * @param string
     */
    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom.
     */
    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * Set pub.
     *
     * @param string
     */
    public function setPub(string $pub): self
    {
        $this->pub = $pub;

        return $this;
    }

    /**
     * Get pub.
     */
    public function getPub(): ?string
    {
        return $this->pub;
    }

    /**
     * Set emp.
     *
     * @param string
     */
    public function setEmp(string $emp): self
    {
        $this->emp = $emp;

        return $this;
    }

    /**
     * Get emp.
     */
    public function getEmp(): ?string
    {
        return $this->emp;
    }

    /**
     * Set rvk.
     *
     * @return Version
     */
    public function setRvk(bool $rvk): self
    {
        $this->rvk = $rvk;

        return $this;
    }

    /**
     * Get rvk.
     */
    public function getRvk(): bool
    {
        return $this->rvk;
    }

    public function isRvk(): ?bool
    {
        return $this->rvk;
    }
}
