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
 *  authors : Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 *
 */
#[ORM\Table(name: 'user')]
#[ORM\UniqueConstraint(name: 'loginname', columns: ['id_serveur', 'loginname'])]
#[ORM\UniqueConstraint(name: 'i_p_s', columns: ['id_individu', 'id_projet', 'id_serveur'])]
#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
class User
{

    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'loginname', nullable: true, type: 'string', length: 20)]
    private $loginname;

    /**
     * @var \App\Entity\Serveur
     */
    #[ORM\JoinColumn(name: 'id_serveur', referencedColumnName: 'nom')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Serveur', inversedBy: 'user')]
    private $serveur;

    /**
     * @var \App\Entity\Individu
     */
    #[ORM\JoinColumn(name: 'id_individu', referencedColumnName: 'id_individu')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Individu', inversedBy: 'user')]
    private $individu;

    /**
     * @var \App\Entity\Projet
     */
    #[ORM\JoinColumn(name: 'id_projet', referencedColumnName: 'id_projet')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Projet', inversedBy: 'user')]
    private $projet;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'login', type: 'boolean', nullable: false, options: ['comment' => 'login sur le serveur lié'])]
    private $login = false;

    /**
     * @var string
     */
    #[ORM\Column(name: 'password', type: 'string', nullable: true, length: 200)]
    private $password;

    /**
     * @var string
     */
    #[ORM\Column(name: 'cpassword', type: 'string', nullable: true, length: 200)]
    private $cpassword;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'expire', type: 'boolean', nullable: true)]
    private $expire;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'pass_expiration', type: 'datetime', nullable: true)]
    private $passexpir;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'cgu', type: 'boolean')]
    private $CGU = false;

    /**
     *
     * @var \App\Entity\Clessh
     *
     * ORM\Column(name="id_clessh", type="integer", nullable=true)
     *
     */
    #[ORM\JoinColumn(name: 'id_clessh', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Clessh', inversedBy: 'user')]
    private $clessh;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'deply', type: 'boolean')]
    private $deply = false;

    public function __toString(): string
    {
        $output = '{';
        $output .= 'loginname=' . $this->getLoginname() .'}';
        return $output;
    }

    public function __construct()
    {
        $this->password  = null;
        $this->passexpir = null;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set loginname
     *
     * @param string $loginname
     *
     * @return User
     */
    public function setLoginname(?string $loginname): self
    {
        $this->loginname = $loginname;

        return $this;
    }

    /**
     * Get loginname
     *
     * @return string
     */
    public function getLoginname(): ?string
    {
        return $this->loginname;
    }

    /**
     * Set serveur
     *
     * @param \App\Entity\Serveur $serveur
     *
     * @return User
     */
    public function setServeur(\App\Entity\Serveur $serveur): User
    {
        $this->serveur = $serveur;

        return $this;
    }

    /**
     * Get serveur
     *
     * @return \App\Entity\Serveur
     */
    public function getServeur(): \App\Entity\Serveur
    {
        return $this->serveur;
    }

    /**
     * Set individu
     *
     * @param \App\Entity\Individu $individu
     *
     * @return User
     */
    public function setIndividu(?\App\Entity\Individu $individu): self
    {
        $this->individu = $individu;

        return $this;
    }

    /**
     * Get individu
     *
     * @return \App\Entity\Individu
     */
    public function getIndividu(): ?\App\Entity\Individu
    {
        return $this->individu;
    }

    /**
     * Set projet
     *
     * @param \App\Entity\Projet $projet
     *
     * @return User
     */
    public function setProjet(?\App\Entity\Projet $projet): self
    {
        $this->projet = $projet;

        return $this;
    }

    /**
     * Get projet
     *
     * @return \App\Entity\Projet
     */
    public function getProjet(): ?\App\Entity\Projet
    {
        return $this->projet;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set cpassword
     *
     * @param string $cpassword
     *
     * @return User
     */
    public function setCpassword(?string $cpassword): self
    {
        $this->cpassword = $cpassword;

        return $this;
    }

    /**
     * Get cpassword
     *
     * @return string
     */
    public function getCpassword(): ?string
    {
        return $this->cpassword;
    }

    /**
     * Set passexpir
     *
     * @param \DateTime $passexpir
     *
     * @return User
     */
    public function setPassexpir(?\Datetime $passexpir): self
    {
        $this->passexpir = $passexpir;

        return $this;
    }

    /**
     * Get passexpir
     *
     * @return \DateTime
     */
    public function getPassexpir(): ?\Datetime
    {
        return $this->passexpir;
    }

    /**
     * Set expire
     *
     * @param boolean $expire
     *
     * @return CollaborateurVersion
     */
    public function setExpire(bool $expire): self
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * Get expire
     *
     * @return boolean
     */
    public function getExpire(): ?bool
    {
        return $this->expire;
    }
    /**
     * Set Clessh
     *
     * @param string \App\Entity\Clessh $clessh
     *
     * @return User
     */
    public function setClessh(?\App\Entity\Clessh $clessh): self
    {
        $this->clessh = $clessh;
        return $this;
    }

    /**
     * Get clessh
     *
     * @return \App\Entity\Clessh
     */
    public function getClessh(): ?\App\Entity\Clessh
    {
        return $this->clessh;
    }

    /**
     * Set login
     *
     * @param boolean $login
     *
     * @return User
     */
    public function setLogin(bool $login): self
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login
     *
     * @return boolean
     */
    public function getLogin(): bool
    {
        return $this->login;
    }

    /**
     * Set CGU
     *
     * @param boolean $CGU
     *
     * @return User
     */
    public function setCGU(bool $CGU): self
    {
        $this->CGU = $CGU;
        return $this;
    }

    /**
     * Get CGU
     *
     * @return boolean
     */
    public function getCGU(): bool
    {
        return $this->CGU;
    }

    /**
     * Set deply
     *
     * @param boolean $deply
     *
     * @return User
     */
    public function setDeply(bool $deply): self
    {
        $this->deply = $deply;
        return $this;
    }

    /**
     * Get deply
     *
     * @return boolean
     */
    public function getDeply(): bool
    {
        return $this->deply;
    }

    public function isLogin(): ?bool
    {
        return $this->login;
    }

    public function isExpire(): ?bool
    {
        return $this->expire;
    }

    public function isCGU(): ?bool
    {
        return $this->CGU;
    }

    public function isDeply(): ?bool
    {
        return $this->deply;
    }
}
