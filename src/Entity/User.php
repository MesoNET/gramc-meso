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

use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table(name="user", uniqueConstraints={@ORM\UniqueConstraint(name="loginname",
 *                         columns={"serveur","loginname"})},
 *                         indexes={@ORM\Index(name="loginname", columns={"serveur","loginname"})})
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $iduser;

    /**
     * @var string
     *
     * @ORM\Column(name="loginname", type="string",length=20 )
     */
    private $loginname;

    /**
     * @var \App\Entity\Serveur
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Serveur")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="serveur", referencedColumnName="nom")
     * })
     */
    private $serveur;

    /**
     * @var \App\Entity\CollaborateurVersion
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\CollaborateurVersion")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="collaborateurVersion", referencedColumnName="id")
     * })
     */
    private $collaborateurversion;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", nullable=true,length=200 )
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="cpassword", type="string", nullable=true,length=200 )
     */
    private $cpassword;

    /**
     * @var boolean
     *
     * @ORM\Column(name="expire", type="boolean", nullable=true)
     */
    private $expire;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="pass_expiration", type="datetime", nullable=true)
     */
    private $passexpir;

    /**
     * @var string
     *
     * @ORM\Column(name="pubkey", type="string", nullable=true,length=1000 )
     */
    private $pubkey;

    public function __toString()
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
     * Get idUser
     *
     * @return integer
     */
    public function getIdUser(): int
    {
        return $this->iduser;
    }

    /**
     * Set loginname
     *
     * @param string $loginname
     *
     * @return User
     */
    public function setLoginname($loginname)
    {
        $this->loginname = $loginname;

        return $this;
    }

    /**
     * Get loginname
     *
     * @return string
     */
    public function getLoginname()
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
     * Set collaborateurversion
     *
     * @param \App\Entity\CollaborateurVersion $collaborateurversion
     *
     * @return User
     */
    public function setCollaborateurVersion(\App\Entity\CollaborateurVersion $collaborateurversion): User
    {
        $this->collaborateurversion = $collaborateurversion;

        return $this;
    }

    /**
     * Get collaborateurversion
     *
     * @return \App\Entity\CollaobateurVersion
     */
    public function getCollaborateurVersion(): \App\Entity\CollaborateurVersion
    {
        return $this->collaborateurversion;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
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
    public function setCpassword($cpassword)
    {
        $this->cpassword = $cpassword;

        return $this;
    }

    /**
     * Get cpassword
     *
     * @return string
     */
    public function getCpassword()
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
    public function setPassexpir($passexpir)
    {
        $this->passexpir = $passexpir;

        return $this;
    }

    /**
     * Get passexpir
     *
     * @return \DateTime
     */
    public function getPassexpir()
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
    public function setExpire($expire)
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * Get expire
     *
     * @return boolean
     */
    public function getExpire()
    {
        return $this->expire;
    }
    /**
     * Set pubkey
     *
     * @param string $pubkey
     *
     * @return User
     */
    public function setPubkey($pubkey): User
    {
        $this->pubkey = $pubkey;

        return $this;
    }

    /**
     * Get pubkey
     *
     * @return string
     */
    public function getPubkey(): ?string
    {
        return $this->pubkey;
    }
}
