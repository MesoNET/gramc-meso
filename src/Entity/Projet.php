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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\GramcServices\Etat;
use App\State\ProjetCollectionProvider;
use App\State\ProjetProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Projet.
 */
#[ORM\Table(name: 'projet')]
#[ORM\Index(columns: ['etat_projet'], name: 'etat_projet')]
#[ORM\Entity(repositoryClass: 'App\Repository\ProjetRepository')]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: 'projets/{id}',
            uriVariables: [
                'id' => 'idProjet',
            ],
            provider: ProjetProvider::class,
            security: "is_granted('ROLE_API')"
        ),
        new GetCollection(
            provider: ProjetCollectionProvider::class,
            security: "is_granted('ROLE_API')"
        ),
        new Get(
            uriTemplate: 'externe/projets/{id}',
            uriVariables: [
                'id' => 'idProjet',
            ],
            security: "is_granted('ROLE_API_SERVICE')"
        ),
        new GetCollection(
            uriTemplate: 'externe/projets',
            security: "is_granted('ROLE_API_SERVICE')"
        ),
    ],
    normalizationContext: ['groups' => ['projet_lecture']],
)]
class Projet
{
    public const PROJET_SESS = 1;   // Projet créé lors d'une session d'attribution
    public const PROJET_TEST = 2;   // Projet test, créé au fil de l'eau, non renouvelable
    public const PROJET_FIL = 3;   // Projet créé au fil de l'eau, renouvelable lors des sessions
    public const PROJET_DYN = 4;   // Projet dynamique (au sens du Dari), créé au fil de l'eau,
    // il dure 1 an et est indépendant des sessions

    public const LIBELLE_TYPE =
        [
            self::PROJET_SESS => 'S',
            self::PROJET_TEST => 'T',
            self::PROJET_FIL => 'F',
            self::PROJET_DYN => 'D',
        ];

    /**
     * Constructor.
     */
    public function __construct($type)
    {
        $this->publi = new ArrayCollection();
        $this->version = new ArrayCollection();
        $this->rapportActivite = new ArrayCollection();
        $this->user = new ArrayCollection();
        $this->typeProjet = $type;
    }

    /**
     * @var int
     */
    #[ORM\Column(name: 'etat_projet', type: 'integer', nullable: false)]
    #[Groups('projet_lecture')]
    private $etatProjet = Etat::EDITION_DEMANDE;

    /**
     * @var int
     */
    #[ORM\Column(name: 'type_projet', type: 'integer', nullable: false)]
    #[Groups('projet_lecture')]
    private $typeProjet;

    /**
     * @var string
     */
    #[ORM\Column(name: 'id_projet', type: 'string', length: 10)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups(['projet_lecture'])]
    #[ApiProperty(identifier: false)]
    private $idProjet;

    /**
     * @var Version
     */
    #[ORM\JoinColumn(name: 'id_veract', referencedColumnName: 'id_version', onDelete: 'SET NULL', nullable: true)]
    #[ORM\OneToOne(targetEntity: 'App\Entity\Version', inversedBy: 'versionActive')]
    #[Groups('projet_lecture')]
    private $versionActive;

    /**
     * @var Version
     */
    #[ORM\JoinColumn(name: 'id_verder', referencedColumnName: 'id_version', onDelete: 'SET NULL', nullable: true)]
    #[ORM\OneToOne(targetEntity: 'App\Entity\Version', inversedBy: 'versionDerniere')]
    #[Groups('projet_lecture')]
    private $versionDerniere;

    /**
     * @var \DateTime
     *                Date limite, la version n'ira pas au-delà
     */
    #[ORM\Column(name: 'limit_date', type: 'datetime', nullable: true)]
    #[Groups('projet_lecture')]
    private $limitDate;

    /**
     * @var Collection
     */
    #[ORM\JoinTable(name: 'publicationProjet')]
    #[ORM\JoinColumn(name: 'id_projet', referencedColumnName: 'id_projet')]
    #[ORM\InverseJoinColumn(name: 'id_publi', referencedColumnName: 'id_publi')]
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Publication', inversedBy: 'projet')]
    private $publi;

    // //////////////////////////////////////////////////////////////////////////////
    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Version', mappedBy: 'projet')]
    private $version;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\User', mappedBy: 'projet', cascade: ['persist'])]
    #[Groups('projet_lecture')]
    private $user;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\RapportActivite', mappedBy: 'projet')]
    private $rapportActivite;

    /**
     * @var int
     */
    #[ORM\Column(name: 'tetat_projet', type: 'integer', nullable: true)]
    #[Groups('projet_lecture')]
    private $tetatProjet;

    public function getId(): string
    {
        return $this->getIdProjet();
    }

    public function __toString(): string
    {
        return $this->getIdProjet();
    }

    /**
     * Set etatProjet.
     */
    public function setEtatProjet(int $etatProjet): self
    {
        $this->etatProjet = $etatProjet;

        return $this;
    }

    /**
     * Get etatProjet.
     */
    public function getEtatProjet(): ?int
    {
        return $this->etatProjet;
    }

    /**
     * Set typeProjet.
     */
    public function setTypeProjet(int $typeProjet): self
    {
        $this->typeProjet = $typeProjet;

        return $this;
    }

    /**
     * Get typeProjet.
     */
    public function getTypeProjet(): ?int
    {
        return $this->typeProjet;
    }

    /**
     * Set idProjet.
     */
    public function setIdProjet(string $idProjet): self
    {
        $this->idProjet = $idProjet;

        return $this;
    }

    public function setId(string $id): self
    {
        $this->idProjet = $id;

        return $this;
    }

    /**
     * Get idProjet.
     */
    public function getIdProjet(): ?string
    {
        return $this->idProjet;
    }

    /**
     * Set versionActive.
     */
    public function setVersionActive(?Version $version = null): self
    {
        $this->versionActive = $version;

        return $this;
    }

    /**
     * Get versionActive.
     */
    public function getVersionActive(): ?Version
    {
        return $this->versionActive;
    }

    /**
     * Set versionDerniere.
     */
    public function setVersionDerniere(?Version $version = null): self
    {
        $this->versionDerniere = $version;

        return $this;
    }

    /**
     * Get versionDerniere.
     */
    public function getVersionDerniere(): ?Version
    {
        return $this->versionDerniere;
    }

    /**
     * Set limitDate.
     *
     * @return Version
     */
    public function setLimitDate(?\DateTime $limitDate): self
    {
        $this->limitDate = $limitDate;

        return $this;
    }

    /**
     * Get limitDate.
     */
    public function getLimitDate(): ?\DateTime
    {
        return $this->limitDate;
    }

    /**
     * Set tetatProjet.
     */
    public function setTetatProjet(int $tetatProjet): self
    {
        $this->tetatProjet = $tetatProjet;

        return $this;
    }

    /**
     * Get tetatProjet.
     */
    public function getTetatProjet(): ?int
    {
        return $this->tetatProjet;
    }

    /**
     * Add publi.
     */
    public function addPubli(Publication $publi): self
    {
        if (!$this->publi->contains($publi)) {
            $this->publi[] = $publi;
        }

        return $this;
    }

    /**
     * Remove publi.
     */
    public function removePubli(Publication $publi): self
    {
        $this->publi->removeElement($publi);

        return $this;
    }

    /**
     * Get publi.
     *
     * @return Collection
     */
    public function getPubli()
    {
        return $this->publi;
    }

    /**
     * Add version.
     */
    public function addVersion(Version $version): self
    {
        if (!$this->version->contains($version)) {
            $this->version[] = $version;
        }

        return $this;
    }

    /**
     * Remove version.
     */
    public function removeVersion(Version $version): self
    {
        $this->version->removeElement($version);

        return $this;
    }

    /**
     * Get version.
     *
     * @return Collection
     */
    public function getVersion()
    {
        return $this->version;
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
     * Add rapportActivite.
     */
    public function addRapportActivite(RapportActivite $rapportActivite): self
    {
        if (!$this->rapportActivite->contains($rapportActivite)) {
            $this->rapportActivite[] = $rapportActivite;
        }

        return $this;
    }

    /**
     * Remove rapportActivite.
     */
    public function removeRapportActivite(RapportActivite $rapportActivite): self
    {
        $this->rapportActivite->removeElement($rapportActivite);

        return $this;
    }

    /**
     * Get rapportActivite.
     *
     * @return Collection
     */
    public function getRapportActivite()
    {
        return $this->rapportActivite;
    }

    /***************************************************
     * Fonctions utiles pour la class Workflow
     * Autre nom pour getEtatProjet/setEtatProjet !
     ***************************************************/
    public function getObjectState(): ?int
    {
        return $this->getEtatProjet();
    }

    public function setObjectState(int $state): self
    {
        return $this->setEtatProjet($state);
    }

    // //////////////////////////////////////

    // pour twig - TODO - A METTRE DANS ServiceProjets !

    public function getLibelleEtat()
    {
        return Etat::getLibelle($this->getEtatProjet());
    }

    public function getTitre()
    {
        if (null != $this->derniereVersion()) {
            return $this->derniereVersion()->getPrjTitre();
        } else {
            return null;
        }
    }

    public function getThematique()
    {
        if (null != $this->derniereVersion()) {
            return $this->derniereVersion()->getPrjThematique();
        } else {
            return null;
        }
    }

    public function getLaboratoire()
    {
        if (null != $this->derniereVersion()) {
            return $this->derniereVersion()->getPrjLLabo();
        } else {
            return null;
        }
    }

    public function getResponsable()
    {
        if (null != $this->derniereVersion()) {
            return $this->derniereVersion()->getResponsable();
        } else {
            return null;
        }
    }

    // hum hum pas fameux
    public function getResponsables()
    {
        if (null != $this->derniereVersion()) {
            return $this->derniereVersion()->getResponsables();
        } else {
            return null;
        }
    }

    /**
     * derniereVersion - Alias de getVersionDerniere()
     *                   TODO - A supprimer !
     *
     * @return Version
     */
    public function derniereVersion()
    {
        return $this->getVersionDerniere();
    }

    /****************
     * Retourne true si $individu collabore à au moins une version du projet
     ******************************************/
    public function isCollaborateur(Individu $individu)
    {
        foreach ($this->getVersion() as $version) {
            if (true === $version->isCollaborateur($individu)) {
                return true;
            }
        }

        return false;
    }

    // //////////////////////////////////////////////////

    /* Supprimé car non utilisé
        //public function getCollaborateurs( $versions = [] )
        //{
            //if( $versions === [] ) $versions = getRepository(Version::class)->findVersions( $this );

            //$collaborateurs = [];
            //foreach( $versions as $version )
                //foreach( $version->getCollaborateurs() as $collaborateur )
                    //$collaborateurs[ $collaborateur->getIdIndividu() ] = $collaborateur;

            //return $collaborateurs;
        //}
    */
    // ///////////////////////////////////////////////////

    public function getEtat(): ?int
    {
        return $this->getEtatProjet();
    }

    // public function getLibelleType()
    // {
    // $type = $this->getTypeProjet();
    // if ($type <=3 and $type > 0) {
    // return Projet::LIBELLE_TYPE[$this->getTypeProjet()];
    // } else {
    // return '?';
    // }
    // }
}
