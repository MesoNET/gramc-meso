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
 * Serveur
 *
 * @ORM\Table(name="serveur", options={"collation"="utf8mb4_general_ci"}, uniqueConstraints={@ORM\UniqueConstraint(name="admname", columns={"admname"})})
 * @ORM\Entity(repositoryClass="App\Repository\ServeurRepository")

 */
class Serveur
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ressource = new \Doctrine\Common\Collections\ArrayCollection();
        $this->user = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=20)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $nom;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Ressource", mappedBy="id_serveur", cascade={"persist"})
     */
    private $ressource;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\User", mappedBy="id_serveur", cascade={"persist"})
     */
    private $user;

    /**
     * @var desc
     *
     * Attention desc est un nom réservé !
     * @ORM\Column(name="descr", type="string", length=200, nullable=true, options={"default":""})
     * 
     */
    private $desc;

    /**
     * @var admname
     *
     * @ORM\Column(name="admname", type="string", length=20, nullable=true, options={"comment":"username symfony pour l'api"}) )
     * 
     */
    private $admname;

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
     * @return Sso
     */
    public function setNom(string $nom): Serveur
    {
        $this->nom = $nom;
        return $this;
    }

    /**
     * Add ressource
     *
     * @param \App\Entity\Ressource $ressource
     *
     * @return Serveur
     */
    public function addRessource(\App\Entity\Ressource $ressource): self
    {
        if (! $this->ressource->contains($ressource))
        {
            $this->ressource[] = $ressource;
        }
        return $this;
    }

    /**
     * Remove resource
     *
     * @param \App\Entity\Ressource $ressource
     */
    public function removeRessource(\App\Entity\Ressource $ressource): self
    {
        $this->ressource->removeElement($ressource);
        return $this;
    }

    /**
     * Get ressource
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRessource()
    {
        return $this->ressource;
    }

    /**
     * Add user
     *
     * @param \App\Entity\User $user
     *
     * @return Serveur
     */
    public function addUser(\App\Entity\User $user): self
    {
        if (! $this->user->contains($user))
        {
            $this->user[] = $user;
        }
        return $this;
    }

    /**
     * Remove user
     *
     * @param \App\Entity\User $user
     */
    public function removeUser(\App\Entity\User $user): self
    {
        $this->user->removeElement($user);
        return $this;
    }

    /**
     * Get user
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUser()
    {
        return $this->user;
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
     * @return Sso
     */
    public function setDesc(string $desc): Serveur
    {
        $this->desc = $desc;
        return $this;
    }

    /**
     * Get Admname
     *
     * @return string
     */
    public function getAdmname(): ?string
    {
        return $this->admname;
    }

    /**
     * Set Admname
     *
     * @param string
     * @return Serveur
     */
    public function setAdmname(string $admname): Serveur
    {
        $this->admname = $admname;
        return $this;
    }

    public function __toString(): string
    {
        return $this->getNom();
    }
}
