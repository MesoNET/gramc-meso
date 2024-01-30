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
 * Serveur.
 */
#[ORM\Table(name: 'serveur', options: ['collation' => 'utf8mb4_general_ci'])]
#[ORM\UniqueConstraint(name: 'admname', columns: ['admname'])]
#[ORM\Entity(repositoryClass: 'App\Repository\ServeurRepository')]
#[ApiResource(normalizationContext: ['groups' => ['Serveur']])]
class Serveur
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->ressource = new ArrayCollection();
        $this->user = new ArrayCollection();
    }

    /**
     * @var string
     */
    #[ORM\Column(name: 'nom', type: 'string', length: 20)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups('Serveur')]
    private $nom;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Ressource', mappedBy: 'serveur', cascade: ['persist'])]
    #[Groups('Serveur')]
    private $ressource;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\User', mappedBy: 'serveur', cascade: ['persist'])]
    #[Groups('Serveur')]
    private $user;

    /**
     * @var string $desc
     *
     * Attention desc est un nom réservé !
     */
    #[ORM\Column(name: 'descr', type: 'string', length: 200, nullable: true, options: ['default' => ''])]
    #[Groups('Serveur')]
    private $desc;

    /**
     * @var string cguUrl
     */
    #[ORM\Column(name: 'cgu_url', type: 'string', nullable: true, length: 200)]
    #[Groups('Serveur')]
    private $cguUrl;

    /**
     * @var admname
     */
    #[ORM\Column(name: 'admname', type: 'string', length: 20, nullable: true, options: ['comment' => "username symfony pour l'api"])]
    #[Groups('Serveur')]
    private $admname;

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
     *
     * @return Sso
     */
    public function setNom(string $nom): Serveur
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Add ressource.
     */
    public function addRessource(Ressource $ressource): self
    {
        if (!$this->ressource->contains($ressource)) {
            $this->ressource[] = $ressource;
        }

        return $this;
    }

    /**
     * Remove resource.
     */
    public function removeRessource(Ressource $ressource): self
    {
        $this->ressource->removeElement($ressource);

        return $this;
    }

    /**
     * Get ressource.
     *
     * @return Collection
     */
    public function getRessource()
    {
        return $this->ressource;
    }

    /**
     * Add user.
     */
    public function addUser(User $user): self
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
     *
     * @return Sso
     */
    public function setDesc(?string $desc): Serveur
    {
        $this->desc = $desc;

        return $this;
    }

    /**
     * Get cguUrl.
     */
    public function getCguUrl(): ?string
    {
        return $this->cguUrl;
    }

    /**
     * Set cguUrl.
     *
     * @param string
     *
     * @return Ressource
     */
    public function setCguUrl(?string $cguUrl): self
    {
        $this->cguUrl = $cguUrl;

        return $this;
    }

    /**
     * Get Admname.
     */
    public function getAdmname(): ?string
    {
        return $this->admname;
    }

    /**
     * Set Admname.
     *
     * @param string
     */
    public function setAdmname(?string $admname): Serveur
    {
        $this->admname = $admname;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getNom();
    }
}
