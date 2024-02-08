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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\IndividuRepository;
use App\State\IndividuCollectionProvider;
use App\State\IndividuProvider;
use App\Utils\Functions;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Individu.
 *
 * Le "compte gramc-meso"...
 */
#[ORM\Table(name: 'individu')]
#[ORM\Index(columns: ['id_labo'], name: 'id_labo')]
#[ORM\Index(name: 'id_statut', columns: ['id_statut'])]
#[ORM\Index(name: 'id_etab', columns: ['id_etab'])]
#[ORM\UniqueConstraint(name: 'mail', columns: ['mail'])]
#[ORM\Entity(repositoryClass: IndividuRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [new GetCollection(
        provider: IndividuCollectionProvider::class
    ),
        new Get(),
        new Patch(
            //uriTemplate: '/setloginname/{individu}/{projet}/{user}/{loginname}',
        )
],
    normalizationContext: ['groups' => ['individu_lecture']],
    denormalizationContext: ['groups' => ['individu_ecriture']],
    //provider: IndividuProvider::class
)]
class Individu implements UserInterface, EquatableInterface, PasswordAuthenticatedUserInterface
{
    public const INCONNU = 0;
    public const POSTDOC = 1;
    public const ATER = 2;
    public const DOCTORANT = 3;
    public const ENSEIGNANT = 11;
    public const CHERCHEUR = 12;
    public const INGENIEUR = 14;

    /* LIBELLE DES STATUTS */
    public const LIBELLE_STATUT =
        [
        self::INCONNU => 'INCONNU',
        self::POSTDOC => 'Post-doctorant',
        self::ATER => 'ATER',
        self::DOCTORANT => 'Doctorant',
        self::ENSEIGNANT => 'Enseignant',
        self::CHERCHEUR => 'Chercheur',
        self::INGENIEUR => 'Ingénieur',
        ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->thematique = new ArrayCollection();
        $this->expertise = new ArrayCollection();
        $this->journal = new ArrayCollection();
        $this->sso = new ArrayCollection();
        $this->collaborateurVersion = new ArrayCollection();
        $this->clessh = new ArrayCollection();
        $this->user = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'creation_stamp', type: 'datetime', nullable: false)]
    #[Groups('individu_lecture')]
    private $creationStamp;

    /**
     * @var string
     */
    #[ORM\Column(name: 'nom', type: 'string', length: 50, nullable: true)]
    #[Groups('individu_lecture')]
    private $nom;

    /**
     * @var string
     */
    #[ORM\Column(name: 'prenom', type: 'string', length: 50, nullable: true)]
    #[Groups(['individu_lecture'])]
    private $prenom;

    /**
     * @var string
     */
    #[ORM\Column(name: 'mail', type: 'string', length: 200, nullable: false)]
    #[Assert\Email(message: "The email '{{ value }}' is not a valid email.")]
    #[Groups('individu_lecture')]
    private $mail;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'admin', type: 'boolean', nullable: false)]
    private $admin = false;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'sysadmin', type: 'boolean', nullable: false)]
    private $sysadmin = false;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'obs', type: 'boolean', nullable: false)]
    private $obs = false;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'expert', type: 'boolean', nullable: false)]
    private $expert = false;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'valideur', type: 'boolean', nullable: false)]
    private $valideur = false;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'president', type: 'boolean', nullable: false)]
    private $president = false;

    /**
     * @var Statut
     */
    #[ORM\JoinColumn(name: 'id_statut', referencedColumnName: 'id_statut')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Statut', inversedBy: 'individu')]
    private $statut;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'desactive', type: 'boolean', nullable: false)]
    #[Groups('individu_lecture')]
    private $desactive = false;

    #[ORM\Column(name: 'id_individu', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups('individu_lecture')]
    private int $id;

    /**
     * @var Laboratoire
     */
    #[ORM\JoinColumn(name: 'id_labo', referencedColumnName: 'id_labo')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Laboratoire', cascade: ['persist'], inversedBy: 'individu')]
    private $labo;

    /**
     * @var Etablissement
     */
    #[ORM\JoinColumn(name: 'id_etab', referencedColumnName: 'id_etab')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Etablissement', inversedBy: 'individu')]
    private $etab;

    /**
     * @var Collection
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Thematique', mappedBy: 'expert')]
    private $thematique;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(mappedBy: 'individu', targetEntity: '\App\Entity\Sso')]
    private $sso;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Clessh', mappedBy: 'individu')]
    private $clessh;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\CollaborateurVersion', mappedBy: 'collaborateur')]
    private $collaborateurVersion;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\User', mappedBy: 'individu', cascade: ['persist'])]
    #[Groups(['individu_lecture', 'individu_ecriture',
        ])]
    private $user;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Expertise', mappedBy: 'expert')]
    private $expertise;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: 'App\Entity\Journal', mappedBy: 'individu')]
    private $journal;

    #[ORM\OneToMany(mappedBy: 'individu', targetEntity: Notification::class)]
    private Collection $notifications;

    // /////////////////////////////////////////
    #[ORM\PrePersist]
    public function setInitialMajStamp(): void
    {
        $this->creationStamp = new \DateTime();
    }

    // ////////////////////////////////////////

    public function __toString()
    {
        if (null !== $this->getPrenom() || null != $this->getNom()) {
            return $this->getPrenom().' '.$this->getNom();
        } elseif (null !== $this->getMail()) {
            return $this->getMail();
        } else {
            return 'sans prénom, nom et mail';
        }
    }

    // //////////////////////////////////////////////////////////////////////////

    /* Pour verifier que deux objets sont égaux, utiliser cet interface et pas == ! */
    public function isEqualTo(UserInterface $user): bool
    {
        if (null === $user || !$user instanceof Individu) {
            return false;
        }

        if ($this->id !== $user->getId()) {
            return false;
        } else {
            return true;
        }
    }

    // implementation UserInterface
    public function getUserIdentifier(): string
    {
        return $this->getId();
    }

    public function getUsername(): ?string
    {
        return $this->getMail();
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getPassword(): ?string
    {
        return '';
    }

    public function eraseCredentials(): void
    {
    }

    // //////////////////////////////////////////////////////////////////////////

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

        if (true === $this->getAdmin()) {
            $roles[] = 'ROLE_ADMIN';
            $roles[] = 'ROLE_OBS';
            $roles[] = 'ROLE_ALLOWED_TO_SWITCH';
        }

        if (true === $this->getPresident()) {
            $roles[] = 'ROLE_PRESIDENT';
            $roles[] = 'ROLE_EXPERT';
        } elseif (true === $this->getExpert()) {
            $roles[] = 'ROLE_EXPERT';
        }

        if (true === $this->getValideur()) {
            $roles[] = 'ROLE_VALIDEUR';
            $roles[] = 'ROLE_OBS';
        }

        if (true === $this->getObs()) {
            $roles[] = 'ROLE_OBS';
        }

        if (true === $this->getSysadmin()) {
            $roles[] = 'ROLE_SYSADMIN';
            $roles[] = 'ROLE_OBS';
        }

        return $roles;
    }

    // ////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Set creationStamp.
     */
    public function setCreationStamp(\DateTime $creationStamp): self
    {
        $this->creationStamp = $creationStamp;

        return $this;
    }

    /**
     * Get creationStamp.
     */
    public function getCreationStamp(): \DateTime
    {
        return $this->creationStamp;
    }

    /**
     * Set nom.
     */
    public function setNom(?string $nom): self
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
     * Set prenom.
     */
    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * Get prenom.
     */
    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    /**
     * Set mail.
     */
    public function setMail(string $mail): self
    {
        // Suppression des accents et autres ç
        // voir https://stackoverflow.com/questions/1284535/php-transliteration
        // $mail_ascii = transliterator_transliterate('Any-Latin;Latin-ASCII;', $mail);
        // $this->mail = $mail_ascii;
        // Ne fonctionne pas ! plantage dans connection_dbg (???)
        $this->mail = $mail;

        return $this;
    }

    /**
     * Get mail.
     */
    public function getMail(): ?string
    {
        return $this->mail;
    }

    /**
     * Set admin.
     */
    public function setAdmin(bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Get admin.
     */
    public function getAdmin(): bool
    {
        return $this->admin;
    }

    /**
     * Set sysadmin.
     */
    public function setSysadmin(bool $sysadmin): self
    {
        $this->sysadmin = $sysadmin;

        return $this;
    }

    /**
     * Get sysadmin.
     */
    public function getSysadmin(): bool
    {
        return $this->sysadmin;
    }

    /**
     * Set obs.
     */
    public function setObs(bool $obs): self
    {
        $this->obs = $obs;

        return $this;
    }

    /**
     * Get obs.
     */
    public function getObs(): bool
    {
        return $this->obs;
    }

    /**
     * Set expert.
     */
    public function setExpert(bool $expert): self
    {
        $this->expert = $expert;

        return $this;
    }

    /**
     * Get expert.
     */
    public function getExpert(): bool
    {
        return $this->expert;
    }

    /**
     * Set valideur.
     */
    public function setValideur(bool $valideur): self
    {
        $this->valideur = $valideur;

        return $this;
    }

    /**
     * Get valideur.
     */
    public function getValideur(): bool
    {
        return $this->valideur;
    }

    /**
     * Set president.
     */
    public function setPresident(bool $president): self
    {
        $this->president = $president;

        return $this;
    }

    /**
     * Get president.
     */
    public function getPresident(): bool
    {
        return $this->president;
    }

    /**
     * Set desactive.
     */
    public function setDesactive(bool $desactive): self
    {
        $this->desactive = $desactive;

        return $this;
    }

    /**
     * Get desactive.
     */
    public function getDesactive(): bool
    {
        return $this->desactive;
    }

    /**
     * Get idIndividu.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set statut.
     */
    public function setStatut(Statut $statut = null): self
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get statut.
     */
    public function getStatut(): ?Statut
    {
        return $this->statut;
    }

    /**
     * Set labo.
     */
    public function setLabo(Laboratoire $labo = null): self
    {
        $this->labo = $labo;

        return $this;
    }

    /**
     * Get labo.
     */
    public function getLabo(): ?Laboratoire
    {
        return $this->labo;
    }

    /**
     * Set etab.
     *
     * @return Individu
     */
    public function setEtab(Etablissement $etab = null)
    {
        $this->etab = $etab;

        return $this;
    }

    /**
     * Get etab.
     */
    public function getEtab(): ?Etablissement
    {
        return $this->etab;
    }

    /**
     * Add thematique.
     */
    public function addThematique(Thematique $thematique): self
    {
        if (!$this->thematique->contains($thematique)) {
            $this->thematique[] = $thematique;
        }

        return $this;
    }

    /**
     * Remove thematique.
     */
    public function removeThematique(Thematique $thematique): self
    {
        $this->thematique->removeElement($thematique);

        return $this;
    }

    /**
     * Get thematique.
     *
     * @return Collection
     */
    public function getThematique()
    {
        return $this->thematique;
    }

    /**
     * Add sso.
     */
    public function addSso(Sso $sso): self
    {
        if (!$this->sso->contains($sso)) {
            $this->sso[] = $sso;
        }

        return $this;
    }

    /**
     * Remove sso.
     */
    public function removeSso(Sso $sso): self
    {
        $this->sso->removeElement($sso);

        return $this;
    }

    /**
     * Get sso.
     *
     * @return Collection
     */
    public function getSso()
    {
        return $this->sso;
    }

    /**
     * Add clessh.
     */
    public function addClessh(Clessh $clessh): self
    {
        if (!$this->clessh->contains($clessh)) {
            $this->clessh[] = $clessh;
        }

        return $this;
    }

    /**
     * Remove clessh.
     */
    public function removeClessh(Clessh $clessh): self
    {
        $this->clessh->removeElement($clessh);

        return $this;
    }

    /**
     * Get clessh.
     *
     * @return Collection
     */
    public function getClessh()
    {
        return $this->clessh;
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
     * Add expertise.
     */
    public function addExpertise(Expertise $expertise): self
    {
        if (!$this->expertise->contains($expertise)) {
            $this->expertise[] = $expertise;
        }

        return $this;
    }

    /**
     * Remove expertise.
     */
    public function removeExpertise(Expertise $expertise): self
    {
        $this->expertise->removeElement($expertise);

        return $this;
    }

    /**
     * Get expertise.
     *
     * @return Collection
     */
    public function getExpertise()
    {
        return $this->expertise;
    }

    /**
     * Add journal.
     */
    public function addJournal(Journal $journal): self
    {
        if (!$this->journal->contains($journal)) {
            $this->journal[] = $journal;
        }

        return $this;
    }

    /**
     * Remove journal.
     */
    public function removeJournal(Journal $journal): self
    {
        $this->journal->removeElement($journal);

        return $this;
    }

    /**
     * Get journal.
     *
     * @return Collection
     */
    public function getJournal()
    {
        return $this->journal;
    }

    // /////////////////////////////////////////////////////////////////////////

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

    // //

    public function isPermanent(): bool
    {
        $statut = $this->getStatut();
        if (null != $statut && $statut->isPermanent()) {
            return true;
        } else {
            return false;
        }
    }

    public function isFromLaboRegional(): bool
    {
        $labo = $this->getLabo();
        if (null != $labo && $labo->isLaboRegional()) {
            return true;
        } else {
            return false;
        }
    }

    // /

    public function getEppn()
    {
        $ssos = $this->getSso();
        $eppn = [];
        foreach ($ssos as $sso) {
            $eppn[] = $sso->getEppn();
        }

        return $eppn;
    }

    // /

    public function peutCreerProjets()
    {
        if ($this->isPermanent() && $this->isFromLaboRegional()) {
            return true;
        } else {
            return false;
        }
    }

    public function isAdmin(): ?bool
    {
        return $this->admin;
    }

    public function isSysadmin(): ?bool
    {
        return $this->sysadmin;
    }

    public function isObs(): ?bool
    {
        return $this->obs;
    }

    public function isValideur(): ?bool
    {
        return $this->valideur;
    }

    public function isPresident(): ?bool
    {
        return $this->president;
    }

    public function isDesactive(): ?bool
    {
        return $this->desactive;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setIndividu($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getIndividu() === $this) {
                $notification->setIndividu(null);
            }
        }

        return $this;
    }
}
