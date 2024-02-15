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
use App\Repository\ServeurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Serveur.
 */
#[ORM\Table(name: 'serveur', options: ['collation' => 'utf8mb4_general_ci'])]
#[ORM\UniqueConstraint(name: 'admname', columns: ['admname'])]
#[ORM\Entity(repositoryClass: ServeurRepository::class)]
#[ApiResource()]
class Serveur implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const SSH = [
        'ED25519 ' => 'ED25519',
        'RSA' => 'RSA',
        'ECDSA' => 'ECDSA',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->ressource = new ArrayCollection();
        $this->user = new ArrayCollection();
    }

    #[ORM\Column(name: 'nom', type: 'string', length: 20)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups('serveur_lecture')]
    private string $nom;

    #[ORM\Column(type: 'json')]
    private ?array $roles = [];

    #[ORM\Column(type: 'string', nullable: true)]
    private string $password;

    #[ORM\OneToMany(mappedBy: 'serveur_lecture', targetEntity: '\App\Entity\Ressource', cascade: ['persist'])]
    #[Groups('serveur_lecture')]
    private Collection $ressource;

    #[ORM\OneToMany(mappedBy: 'serveur', targetEntity: '\App\Entity\User', cascade: ['persist'])]
    #[Groups('serveur_lecture')]
    private Collection $user;

    /**
     * @var string $desc
     *
     * Attention desc est un nom réservé !
     */
    #[ORM\Column(name: 'descr', type: 'string', length: 200, nullable: true, options: ['default' => ''])]
    #[Groups('serveur_lecture')]
    private $desc;

    /**
     * @var string cguUrl
     */
    #[ORM\Column(name: 'cgu_url', type: 'string', nullable: true, length: 200)]
    #[Groups('serveur_lecture')]
    private $cguUrl;

    /**
     * @var string admname
     */
    #[ORM\Column(name: 'admname', type: 'string', length: 20, nullable: true, options: ['comment' => "username symfony pour l'api"])]
    #[Groups('serveur_lecture')]
    private $admname;

    #[ORM\Column]
    private array $formatSSH = [];

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

    /**
     * The public representation of the user (e.g. a username, an email address, etc.).
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->admname;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';
        $roles[] = 'ROLE_API';

        return array_unique($roles);
    }

    public function setRoles(?array $roles): self
    {
        if (null != $roles) {
            $this->roles = $roles;
        }

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFormatSSH(): array
    {
        return $this->formatSSH;
    }

    public function setFormatSSH(array $formatSSH): static
    {
        $this->formatSSH = $formatSSH;

        return $this;
    }
}
