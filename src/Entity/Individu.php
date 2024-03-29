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
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

use App\Utils\Functions;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Individu
 *
 * Le "compte gramc-meso"...
 *
 * @ORM\Table(name="individu", uniqueConstraints={@ORM\UniqueConstraint(name="mail", columns={"mail"})}, indexes={@ORM\Index(name="id_labo", columns={"id_labo"}), @ORM\Index(name="id_statut", columns={"id_statut"}), @ORM\Index(name="id_etab", columns={"id_etab"})})
 * @ORM\Entity(repositoryClass="App\Repository\IndividuRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Individu implements UserInterface, EquatableInterface, PasswordAuthenticatedUserInterface
{
    public const INCONNU       = 0;
    public const POSTDOC       = 1;
    public const ATER          = 2;
    public const DOCTORANT     = 3;
    public const ENSEIGNANT    = 11;
    public const CHERCHEUR     = 12;
    public const INGENIEUR     = 14;

    /* LIBELLE DES STATUTS */
    public const LIBELLE_STATUT =
        [
        self::INCONNU     => 'INCONNU',
        self::POSTDOC     => 'Post-doctorant',
        self::ATER        => 'ATER',
        self::DOCTORANT   => 'Doctorant',
        self::ENSEIGNANT  => 'Enseignant',
        self::CHERCHEUR   => 'Chercheur',
        self::INGENIEUR   => 'Ingénieur'
        ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->thematique = new \Doctrine\Common\Collections\ArrayCollection();
        $this->expertise = new \Doctrine\Common\Collections\ArrayCollection();
        $this->journal = new \Doctrine\Common\Collections\ArrayCollection();
        $this->sso = new \Doctrine\Common\Collections\ArrayCollection();
        $this->collaborateurVersion = new \Doctrine\Common\Collections\ArrayCollection();
        $this->clessh = new \Doctrine\Common\Collections\ArrayCollection();
        $this->user = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_stamp", type="datetime", nullable=false)
     */
    private $creationStamp;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=50, nullable=true)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom", type="string", length=50, nullable=true)
     */
    private $prenom;

    /**
     * @var string
     *
     * @ORM\Column(name="mail", type="string", length=200, nullable=false)
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email."
     * )
     */
    private $mail;

    /**
     * @var boolean
     *
     * @ORM\Column(name="admin", type="boolean", nullable=false)
     */
    private $admin = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sysadmin", type="boolean", nullable=false)
     */
    private $sysadmin = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="obs", type="boolean", nullable=false)
     */
    private $obs = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="expert", type="boolean", nullable=false)
     */
    private $expert = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="valideur", type="boolean", nullable=false)
     */
    private $valideur = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="president", type="boolean", nullable=false)
     */
    private $president = false;

    /**
     * @var \App\Entity\Statut
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Statut",inversedBy="individu")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_statut", referencedColumnName="id_statut")
     * })
     */
    private $statut;

    /**
     * @var boolean
     *
     * @ORM\Column(name="desactive", type="boolean", nullable=false)
     */
    private $desactive = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_individu", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idIndividu;

    /**
     * @var \App\Entity\Laboratoire
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Laboratoire",cascade={"persist"},inversedBy="individu")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_labo", referencedColumnName="id_labo")
     * })
     */
    private $labo;

    /**
     * @var \App\Entity\Etablissement
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Etablissement", inversedBy="individu")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_etab", referencedColumnName="id_etab")
     * })
     */
    private $etab;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Thematique", mappedBy="expert")
     */
    private $thematique;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Sso", mappedBy="individu")
     */
    private $sso;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Clessh", mappedBy="individu")
     */
    private $clessh;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\CollaborateurVersion", mappedBy="collaborateur")
     */
    private $collaborateurVersion;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\User", mappedBy="individu", cascade={"persist"})
     */
    private $user;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Expertise", mappedBy="expert")
     */
    private $expertise;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Journal", mappedBy="individu")
     */
    private $journal;

    ///////////////////////////////////////////

    /**
    * @ORM\PrePersist
    */
    public function setInitialMajStamp(): void
    {
        $this->creationStamp = new \DateTime();
    }

    //////////////////////////////////////////

    public function __toString()
    {
        if ($this->getPrenom() !== null ||  $this->getNom() != null)
        {
            return $this->getPrenom() . ' ' . $this->getNom();
        }
        elseif ($this->getMail() !== null)
        {
            return $this->getMail();
        }
        else
        {
            return 'sans prénom, nom et mail';
        }
    }

    ////////////////////////////////////////////////////////////////////////////

    /* Pour verifier que deux objets sont égaux, utiliser cet interface et pas == ! */
    public function isEqualTo(UserInterface $user) : bool
    {
        if ($user === null || !$user instanceof Individu)
        {
            return false;
        }

        if ($this->idIndividu !== $user->getId())
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function getId(): ?int
    {
        return $this->idIndividu;
    }

    // implementation UserInterface
    public function getUserIdentifier(): ?string { return $this->getId();}
    public function getUsername(): ?string { return $this->getMail();}
    public function getSalt(): ?string { return null;}
    public function getPassword(): ?string { return "";}
    public function eraseCredentials() {}


    ////////////////////////////////////////////////////////////////////////////

    /* LES ROLES DEFINIS DANS L'APPLICATION
     *     - ROLE_DEMANDEUR = Peut demander des ressoureces - Le minimum
     *     - ROLE_OBS       = Peut observer beaucoup de choses, mais ne peut agir
     *     - ROLE_ADMIN     = Un OBS qui peut AUSSI paramétrer l'application et intervenir dans les projets ou le workflow
     *     - ROLE_VALIDEUR  = Un OBS qui peut AUSSI paramétrer certaines choses (laboratoires) et valider des projets dynamiques
     *     - ROLE_SYSADMIN  = Administrateur système, est observateur et reçoit certains mails
     *     - ROLE_ALLOWED_TO_SWITCH = Peut changer d'identité (actuellement kifkif admin)
     *     - ROLE_EXPERT    = Peut être affecté à un projet pour expertise (NON UTILISE)
     *     - ROLE_PRESIDENT = Peut affecter les experts à des projets (NON UTILISE)
     */
    public function getRoles(): array
    {
        $roles[] = 'ROLE_DEMANDEUR';

        if ($this->getAdmin() === true)
        {
            $roles[] = 'ROLE_ADMIN';
            $roles[] = 'ROLE_OBS';
            $roles[] = 'ROLE_ALLOWED_TO_SWITCH';
        }

        if ($this->getPresident() === true)
        {
            $roles[] = 'ROLE_PRESIDENT';
            $roles[] = 'ROLE_EXPERT';
        }

        elseif ($this->getExpert() === true)
        {
            $roles[] = 'ROLE_EXPERT';
        }

        if ($this->getValideur() === true)
        {
            $roles[] = 'ROLE_VALIDEUR';
            $roles[] = 'ROLE_OBS';
        }

        if ($this->getObs() === true)
        {
            $roles[] = 'ROLE_OBS';
        }

        if ($this->getSysadmin() === true)
        {
            $roles[] = 'ROLE_SYSADMIN';
            $roles[] = 'ROLE_OBS';
        }

        return $roles;
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Set creationStamp
     *
     * @param \DateTime $creationStamp
     *
     * @return Individu
     */
    public function setCreationStamp(\Datetime $creationStamp): self
    {
        $this->creationStamp = $creationStamp;

        return $this;
    }

    /**
     * Get creationStamp
     *
     * @return \DateTime
     */
    public function getCreationStamp(): \Datetime
    {
        return $this->creationStamp;
    }

    /**
     * Set nom
     *
     * @param string $nom
     *
     * @return Individu
     */
    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
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
     * Set prenom
     *
     * @param string $prenom
     *
     * @return Individu
     */
    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * Get prenom
     *
     * @return string
     */
    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    /**
     * Set mail
     *
     * @param string $mail
     *
     * @return Individu
     */
    public function setMail(string $mail): self
    {
        // Suppression des accents et autres ç
        // voir https://stackoverflow.com/questions/1284535/php-transliteration
        //$mail_ascii = transliterator_transliterate('Any-Latin;Latin-ASCII;', $mail);
        //$this->mail = $mail_ascii;
        // Ne fonctionne pas ! plantage dans connection_dbg (???)
        $this->mail = $mail;
        return $this;
    }

    /**
     * Get mail
     *
     * @return string
     */
    public function getMail(): ?string
    {
        return $this->mail;
    }

    /**
     * Set admin
     *
     * @param boolean $admin
     *
     * @return Individu
     */
    public function setAdmin(bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Get admin
     *
     * @return boolean
     */
    public function getAdmin(): bool
    {
        return $this->admin;
    }

    /**
     * Set sysadmin
     *
     * @param boolean $sysadmin
     *
     * @return Individu
     */
    public function setSysadmin(bool $sysadmin): self
    {
        $this->sysadmin = $sysadmin;

        return $this;
    }

    /**
     * Get sysadmin
     *
     * @return boolean
     */
    public function getSysadmin(): bool
    {
        return $this->sysadmin;
    }

    /**
     * Set obs
     *
     * @param boolean $obs
     *
     * @return Individu
     */
    public function setObs(bool $obs): self
    {
        $this->obs = $obs;

        return $this;
    }

    /**
     * Get obs
     *
     * @return boolean
     */
    public function getObs(): bool
    {
        return $this->obs;
    }

    /**
     * Set expert
     *
     * @param boolean $expert
     *
     * @return Individu
     */
    public function setExpert(bool $expert): self
    {
        $this->expert = $expert;

        return $this;
    }

    /**
     * Get expert
     *
     * @return boolean
     */
    public function getExpert(): bool
    {
        return $this->expert;
    }

    /**
     * Set valideur
     *
     * @param boolean $valideur
     *
     * @return Individu
     */
    public function setValideur(bool $valideur): self
    {
        $this->valideur = $valideur;

        return $this;
    }

    /**
     * Get valideur
     *
     * @return boolean
     */
    public function getValideur(): bool
    {
        return $this->valideur;
    }

    /**
     * Set president
     *
     * @param boolean $president
     *
     * @return Individu
     */
    public function setPresident(bool $president): self
    {
        $this->president = $president;
        return $this;
    }

    /**
     * Get president
     *
     * @return boolean
     */
    public function getPresident(): bool
    {
        return $this->president;
    }

    /**
     * Set desactive
     *
     * @param boolean $desactive
     *
     * @return Individu
     */
    public function setDesactive(bool $desactive): self
    {
        $this->desactive = $desactive;

        return $this;
    }

    /**
     * Get desactive
     *
     * @return boolean
     */
    public function getDesactive(): bool
    {
        return $this->desactive;
    }

    /**
     * Get idIndividu
     *
     * @return integer
     */
    public function getIdIndividu(): ?int
    {
        return $this->idIndividu;
    }

    /**
     * Set statut
     *
     * @param \App\Entity\Statut $statut
     *
     * @return Individu
     */
    public function setStatut(?\App\Entity\Statut $statut = null): self
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get statut
     *
     * @return \App\Entity\Statut
     */
    public function getStatut(): ?\App\Entity\Statut
    {
        return $this->statut;
    }

    /**
     * Set labo
     *
     * @param \App\Entity\Laboratoire $labo
     *
     * @return Individu
     */
    public function setLabo(?\App\Entity\Laboratoire $labo = null): self
    {
        $this->labo = $labo;

        return $this;
    }

    /**
     * Get labo
     *
     * @return \App\Entity\Laboratoire
     */
    public function getLabo(): ?\App\Entity\Laboratoire
    {
        return $this->labo;
    }

    /**
     * Set etab
     *
     * @param \App\Entity\Etablissement $etab
     *
     * @return Individu
     */
    public function setEtab(?\App\Entity\Etablissement $etab = null)
    {
        $this->etab = $etab;

        return $this;
    }

    /**
     * Get etab
     *
     * @return \App\Entity\Etablissement
     */
    public function getEtab(): ?\App\Entity\Etablissement
    {
        return $this->etab;
    }

    /**
     * Add thematique
     *
     * @param \App\Entity\Thematique $thematique
     *
     * @return Individu
     */
    public function addThematique(\App\Entity\Thematique $thematique): self
    {
        if (! $this->thematique->contains($thematique))
        {
            $this->thematique[] = $thematique;
        }

        return $this;
    }

    /**
     * Remove thematique
     *
     * @param \App\Entity\Thematique $thematique
     */
    public function removeThematique(\App\Entity\Thematique $thematique): self
    {
        $this->thematique->removeElement($thematique);
        return $this;
    }

    /**
     * Get thematique
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getThematique()
    {
        return $this->thematique;
    }

    /**
     * Add sso
     *
     * @param \App\Entity\Sso $sso
     *
     * @return Individu
     */
    public function addSso(\App\Entity\Sso $sso): self
    {
        if (! $this->sso->contains($sso))
        {
            $this->sso[] = $sso;
        }

        return $this;
    }

    /**
     * Remove sso
     *
     * @param \App\Entity\Sso $sso
     */
    public function removeSso(\App\Entity\Sso $sso): self
    {
        $this->sso->removeElement($sso);
        return $this;
    }

    /**
     * Get sso
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSso()
    {
        return $this->sso;
    }

    /**
     * Add clessh
     *
     * @param \App\Entity\Clessh $clessh
     *
     * @return Individu
     */
    public function addClessh(\App\Entity\Clessh $clessh): self
    {
        if (! $this->clessh->contains($clessh))
        {
            $this->clessh[] = $clessh;
        }
        return $this;
    }

    /**
     * Remove clessh
     *
     * @param \App\Entity\Clessh $clessh
     *
     * @return Individu
     */
    public function removeClessh(\App\Entity\Clessh $clessh): self
    {
        $this->clessh->removeElement($clessh);
        return $this;
    }

    /**
     * Get clessh
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getClessh()
    {
        return $this->clessh;
    }

    /**
     * Add collaborateurVersion
     *
     * @param \App\Entity\CollaborateurVersion $collaborateurVersion
     *
     * @return Individu
     */
    public function addCollaborateurVersion(\App\Entity\CollaborateurVersion $collaborateurVersion): self
    {
        if (! $this->collaborateurVersion->contains($collaborateurVersion))
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
     * Add user
     *
     * @param \App\Entity\User $user
     *
     * @return Individu
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
     *
     * @return Individu
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
     * Add expertise
     *
     * @param \App\Entity\Expertise $expertise
     *
     * @return Individu
     */
    public function addExpertise(\App\Entity\Expertise $expertise): self
    {
        if (! $this->expertise->contains($expertise))
        {
            $this->expertise[] = $expertise;
        }

        return $this;
    }

    /**
     * Remove expertise
     *
     * @param \App\Entity\Expertise $expertise
     */
    public function removeExpertise(\App\Entity\Expertise $expertise): self
    {
        $this->expertise->removeElement($expertise);
        return $this;
    }

    /**
     * Get expertise
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getExpertise()
    {
        return $this->expertise;
    }

    /**
     * Add journal
     *
     * @param \App\Entity\Journal $journal
     * @return Individu
     */
    public function addJournal(\App\Entity\Journal $journal): self
    {
        if (!$this->journal->contains($journal)) {
            $this->journal[] = $journal;
        }

        return $this;
    }

    /**
     * Remove journal
     *
     * @param \App\Entity\Journal $journal
     */
    public function removeJournal(\App\Entity\Journal $journal): self
    {
        $this->journal->removeElement($journal);
        return $this;
    }

    /**
      * Get journal
      *
      * @return \Doctrine\Common\Collections\Collection
      */
    public function getJournal()
    {
        return $this->journal;
    }

    ///////////////////////////////////////////////////////////////////////////

    public function getIDP()
    {
        return implode(',', $this->getSso()->toArray());
    }

    // TODO - Revoir cette fonction !!!!
    //        Suppression de Functions::warningMessage pas cool
    // NE SERT A RIEN - VIREE !
/*
    public function getEtablissement()
    {
        $server =  Request::createFromGlobals()->server;
        if ($server->has('REMOTE_USER') || $server->has('REDIRECT_REMOTE_USER'))
        {
            $eppn = '';
            if ($server->has('REMOTE_USER'))
            {
                $eppn =  $server->get('REMOTE_USER');
            }
            if ($server->has('REDIRECT_REMOTE_USER'))
            {
                $eppn =  $server->get('REDIRECT_REMOTE_USER');
            }
            preg_match('/^.+@(.+)$/', $$eppn, $matches);
            if ($matches[0] != null)
            {
                return $matches[0];
            }
            //else
            //    Functions::warningMessage('Individu::getEtablissements user '. $this .' a un EPPN bizarre');
        }
        return 'aucun établissement connu';
    }
*/

    public function isExpert(): bool
    {
        return $this->expert;
    }

    ////

    public function isPermanent(): bool
    {
        $statut = $this->getStatut();
        if ($statut != null && $statut->isPermanent())
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isFromLaboRegional(): bool
    {
        $labo = $this->getLabo();
        if ($labo != null && $labo->isLaboRegional())
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    ///

    public function getEppn()
    {
        $ssos = $this->getSso();
        $eppn = [];
        foreach ($ssos as $sso)
        {
            $eppn[] = $sso->getEppn();
        }
        return $eppn;
    }

    ///

    public function peutCreerProjets()
    {
        if ($this->isPermanent() && $this->isFromLaboRegional())
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}
