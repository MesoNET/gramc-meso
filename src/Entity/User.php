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
 *  authors : Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\State\UserProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * User.
 */
#[ORM\Table(name: 'user')]
#[ORM\UniqueConstraint(name: 'loginname', columns: ['id_serveur', 'loginname'])]
#[ORM\UniqueConstraint(name: 'i_p_s', columns: ['id_individu', 'id_projet', 'id_serveur'])]
#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
#[ApiResource(operations: [
    new GetCollection(),
    new Patch(
        uriTemplate: '/users/{individu}/{projet}',
        provider: UserProvider::class
    ),
    new Patch(),
    new Post(),
    ],
    normalizationContext: ['groups' => ['user_lecture']],
    denormalizationContext: ['groups' => ['user_ecriture']],
)]
class User
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['individu_lecture', 'user_lecture'])]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'loginname', nullable: true, type: 'string', length: 20)]
    #[Groups(['individu_lecture', 'individu_ecriture', 'user_lecture', 'user_ecriture'])]
    private $loginname;

    #[ORM\JoinColumn(name: 'id_serveur', referencedColumnName: 'nom')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Serveur', inversedBy: 'user')]
    private Serveur $serveur;

    #[ORM\JoinColumn(name: 'id_individu', referencedColumnName: 'id_individu', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Individu', inversedBy: 'user')]
    #[Groups(['user_lecture', 'user_ecriture'])]
    private Individu $individu;

    #[ORM\JoinColumn(name: 'id_projet', referencedColumnName: 'id_projet')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Projet', inversedBy: 'user')]
    #[Groups(['individu_lecture', 'user_lecture', 'user_ecriture'])]
    private Projet $projet;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'login', type: 'boolean', nullable: false, options: ['comment' => 'login sur le serveur lié'])]
    #[Groups(['individu_lecture', 'individu_ecriture', 'user_lecture', 'user_ecriture'])]
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
     * @var bool
     */
    #[ORM\Column(name: 'expire', type: 'boolean', nullable: true)]
    #[Groups(['individu_lecture', 'individu_ecriture', 'user_lecture', 'user_ecriture'])]
    private $expire;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'pass_expiration', type: 'datetime', nullable: true)]
    #[Groups(['individu_lecture', 'user_lecture'])]
    private $passexpir;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'cgu', type: 'boolean')]
    private $CGU = false;

    /**
     * @var Clessh
     *
     * ORM\Column(name="id_clessh", type="integer", nullable=true)
     */
    #[ORM\JoinColumn(name: 'id_clessh', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Clessh', inversedBy: 'user')]
    #[Groups('individu_lecture', 'user_lecture')]
    private $clessh;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'deply', type: 'boolean')]
    #[Groups(['individu_lecture', 'individu_ecriture', 'user_lecture', 'user_ecriture'])]
    private $deply = false;

    public function __toString(): string
    {
        $output = '{';

        return $output.('loginname='.$this->getLoginname().'}');
    }

    public function __construct()
    {
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set loginname.
     */
    public function setLoginname(?string $loginname): self
    {
        $this->loginname = $loginname;

        return $this;
    }

    /**
     * Get loginname.
     */
    public function getLoginname(): ?string
    {
        return $this->loginname;
    }

    /**
     * Set serveur.
     */
    public function setServeur(Serveur $serveur): User
    {
        $this->serveur = $serveur;

        return $this;
    }

    /**
     * Get serveur.
     */
    public function getServeur(): Serveur
    {
        return $this->serveur;
    }

    /**
     * Set individu.
     */
    public function setIndividu(?Individu $individu): self
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
     * Set projet.
     */
    public function setProjet(?Projet $projet): self
    {
        $this->projet = $projet;

        return $this;
    }

    /**
     * Get projet.
     */
    public function getProjet(): ?Projet
    {
        return $this->projet;
    }

    /**
     * Set password.
     */
    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set cpassword.
     */
    public function setCpassword(?string $cpassword): self
    {
        $this->cpassword = $cpassword;

        return $this;
    }

    /**
     * Get cpassword.
     */
    public function getCpassword(): ?string
    {
        return $this->cpassword;
    }

    /**
     * Set passexpir.
     */
    public function setPassexpir(?\DateTime $passexpir): self
    {
        $this->passexpir = $passexpir;

        return $this;
    }

    /**
     * Get passexpir.
     */
    public function getPassexpir(): ?\DateTime
    {
        return $this->passexpir;
    }

    /**
     * Set expire.
     *
     * @return CollaborateurVersion
     */
    public function setExpire(bool $expire): self
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * Get expire.
     */
    public function getExpire(): ?bool
    {
        return $this->expire;
    }

    /**
     * Set Clessh.
     *
     * @param string \App\Entity\Clessh $clessh
     */
    public function setClessh(?Clessh $clessh): self
    {
        $this->clessh = $clessh;

        return $this;
    }

    /**
     * Get clessh.
     */
    public function getClessh(): ?Clessh
    {
        return $this->clessh;
    }

    /**
     * Set login.
     */
    public function setLogin(bool $login): self
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login.
     */
    public function getLogin(): bool
    {
        return $this->login;
    }

    /**
     * Set CGU.
     */
    public function setCGU(bool $CGU): self
    {
        $this->CGU = $CGU;

        return $this;
    }

    /**
     * Get CGU.
     */
    public function getCGU(): bool
    {
        return $this->CGU;
    }

    /**
     * Set deply.
     */
    public function setDeply(bool $deply): self
    {
        $this->deply = $deply;

        return $this;
    }

    /**
     * Get deply.
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
