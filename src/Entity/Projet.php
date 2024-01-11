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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use App\GramcServices\Etat;
use App\Utils\Functions;
use App\Entity\Version;
use App\Entity\Expertise;
use App\Entity\CollaborateurVersion;
use App\Utils\GramcDate;

use App\Form\ChoiceList\ExpertChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Projet
 */
#[ORM\Table(name: 'projet')]
#[ORM\Index(name: 'etat_projet', columns: ['etat_projet'])]
#[ORM\Entity(repositoryClass: 'App\Repository\ProjetRepository')]
class Projet
{
    public const PROJET_SESS = 1;   // Projet créé lors d'une session d'attribution
    public const PROJET_TEST = 2;   // Projet test, créé au fil de l'eau, non renouvelable
    public const PROJET_FIL  = 3;   // Projet créé au fil de l'eau, renouvelable lors des sessions
    public const PROJET_DYN  = 4;   // Projet dynamique (au sens du Dari), créé au fil de l'eau,
                                    // il dure 1 an et est indépendant des sessions    

    public const LIBELLE_TYPE=
    [
        self::PROJET_SESS => 'S',
        self::PROJET_TEST =>  'T',
        self::PROJET_FIL =>  'F',
        self::PROJET_DYN =>  'D',
    ];

    /**
     * Constructor
     */
    public function __construct($type)
    {
        $this->publi = new \Doctrine\Common\Collections\ArrayCollection();
        $this->version = new \Doctrine\Common\Collections\ArrayCollection();
        $this->rapportActivite = new \Doctrine\Common\Collections\ArrayCollection();
        $this->user = new \Doctrine\Common\Collections\ArrayCollection();
        $this->etatProjet = Etat::EDITION_DEMANDE;
        $this->typeProjet = $type;
    }

    /**
     * @var integer
     */
    #[ORM\Column(name: 'etat_projet', type: 'integer', nullable: false)]
    private $etatProjet;


    /**
     * @var integer
     */
    #[ORM\Column(name: 'type_projet', type: 'integer', nullable: false)]
    private $typeProjet;

    /**
     * @var string
     */
    #[ORM\Column(name: 'id_projet', type: 'string', length: 10)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $idProjet;

    /**
     * @var \App\Entity\Version
     *
     *
     */
    #[ORM\JoinColumn(name: 'id_veract', referencedColumnName: 'id_version', onDelete: 'SET NULL', nullable: true)]
    #[ORM\OneToOne(targetEntity: 'App\Entity\Version', inversedBy: 'versionActive')]
    private $versionActive;

    /**
     * @var \App\Entity\Version
     */
    #[ORM\JoinColumn(name: 'id_verder', referencedColumnName: 'id_version', onDelete: 'SET NULL', nullable: true)]
    #[ORM\OneToOne(targetEntity: 'App\Entity\Version', inversedBy: 'versionDerniere')]
    private $versionDerniere;

    /**
     * @var \DateTime
     * Date limite, la version n'ira pas au-delà
     */
    #[ORM\Column(name: 'limit_date', type: 'datetime', nullable: true)]
    private $limitDate;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\JoinTable(name: 'publicationProjet')]
    #[ORM\JoinColumn(name: 'id_projet', referencedColumnName: 'id_projet')]
    #[ORM\InverseJoinColumn(name: 'id_publi', referencedColumnName: 'id_publi')]
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Publication', inversedBy: 'projet')]
    private $publi;

    ////////////////////////////////////////////////////////////////////////////////
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Version', mappedBy: 'projet')]
    private $version;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\User', mappedBy: 'projet', cascade: ['persist'])]
    private $user;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\RapportActivite', mappedBy: 'projet')]
    private $rapportActivite;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'tetat_projet', type: 'integer', nullable: true)]
    private $tetatProjet;

    public function getId(): ?string
    {
        return $this->getIdProjet();
    }
    public function __toString(): string
    {
        return $this->getIdProjet();
    }

    /**
     * Set etatProjet
     *
     * @param integer $etatProjet
     *
     * @return Projet
     */
    public function setEtatProjet(int $etatProjet): self
    {
        $this->etatProjet = $etatProjet;

        return $this;
    }

    /**
     * Get etatProjet
     *
     * @return integer
     */
    public function getEtatProjet(): ?int
    {
        return $this->etatProjet;
    }

    /**
     * Set typeProjet
     *
     * @param integer $typeProjet
     *
     * @return Projet
     */
    public function setTypeProjet(int $typeProjet): self
    {
        $this->typeProjet = $typeProjet;

        return $this;
    }

    /**
     * Get typeProjet
     *
     * @return integer
     */
    public function getTypeProjet(): ?int
    {
        return $this->typeProjet;
    }

    /**
     * Set idProjet
     *
     * @param string $idProjet
     *
     * @return Projet
     */
    public function setIdProjet(string $idProjet): self
    {
        $this->idProjet = $idProjet;

        return $this;
    }

    /**
     * Get idProjet
     *
     * @return string
     */
    public function getIdProjet(): ?string
    {
        return $this->idProjet;
    }

    /**
     * Set versionActive
     *
     * @param \App\Entity\Version $version
     *
     * @return Projet
     */
    public function setVersionActive(?\App\Entity\Version $version = null): self
    {
        $this->versionActive = $version;

        return $this;
    }

    /**
     * Get versionActive
     *
     * @return \App\Entity\Version
     */
    public function getVersionActive(): ?\App\Entity\Version
    {
        return $this->versionActive;
    }

    /**
     * Set versionDerniere
     *
     * @param \App\Entity\Version $version
     *
     * @return Projet
     */
    public function setVersionDerniere(?\App\Entity\Version $version = null): self
    {
        $this->versionDerniere = $version;

        return $this;
    }

    /**
     * Get versionDerniere
     *
     * @return \App\Entity\Version
     */
    public function getVersionDerniere(): ?\App\Entity\Version
    {
        return $this->versionDerniere;
    }

    /**
     * Set limitDate
     *
     * @param \DateTime $limitDate
     *
     * @return Version
     */
    public function setLimitDate(?\DateTime $limitDate): self
    {
        $this->limitDate = $limitDate;

        return $this;
    }

    /**
     * Get limitDate
     *
     * @return \DateTime
     */
    public function getLimitDate(): ?\DateTime
    {
        return $this->limitDate;
    }

    /**
     * Set tetatProjet
     *
     * @param integer $tetatProjet
     *
     * @return Projet
     */
    public function setTetatProjet(int $tetatProjet): self
    {
        $this->tetatProjet = $tetatProjet;

        return $this;
    }

    /**
     * Get tetatProjet
     *
     * @return integer
     */
    public function getTetatProjet(): ?int
    {
        return $this->tetatProjet;
    }

    /**
     * Add publi
     *
     * @param \App\Entity\Publication $publi
     *
     * @return Projet
     */
    public function addPubli(\App\Entity\Publication $publi): self
    {
        if (! $this->publi->contains($publi)) {
            $this->publi[] = $publi;
        }

        return $this;
    }

    /**
     * Remove publi
     *
     * @param \App\Entity\Publication $publi
     */
    public function removePubli(\App\Entity\Publication $publi): self
    {
        $this->publi->removeElement($publi);
        return $this;
    }

    /**
     * Get publi
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPubli()
    {
        return $this->publi;
    }

    /**
     * Add version
     *
     * @param \App\Entity\Version $version
     *
     * @return Projet
     */
    public function addVersion(\App\Entity\Version $version): self
    {
        if (! $this->version->contains($version))
        {
            $this->version[] = $version;
        }

        return $this;
    }

    /**
     * Remove version
     *
     * @param \App\Entity\Version $version
     */
    public function removeVersion(\App\Entity\Version $version): self
    {
        $this->version->removeElement($version);
        return $this;
    }

    /**
     * Get version
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Add user
     *
     * @param \App\Entity\User $user
     *
     * @return Projet
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
     * @return Projet
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
     * Add rapportActivite
     *
     * @param \App\Entity\RapportActivite $rapportActivite
     *
     * @return Projet
     */
    public function addRapportActivite(\App\Entity\RapportActivite $rapportActivite): self
    {
        if (! $this->rapportActivite->contains($rapportActivite))
        {
            $this->rapportActivite[] = $rapportActivite;
        }

        return $this;
    }

    /**
     * Remove rapportActivite
     *
     * @param \App\Entity\RapportActivite $rapportActivite
     */
    public function removeRapportActivite(\App\Entity\RapportActivite $rapportActivite): self
    {
        $this->rapportActivite->removeElement($rapportActivite);
        return $this;
    }

    /**
     * Get rapportActivite
     *
     * @return \Doctrine\Common\Collections\Collection
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

    ////////////////////////////////////////

    // pour twig - TODO - A METTRE DANS ServiceProjets !

    public function getLibelleEtat()
    {
        return Etat::getLibelle($this->getEtatProjet());
    }

    public function getTitre()
    {
        if ($this->derniereVersion() != null) {
            return $this->derniereVersion()->getPrjTitre();
        } else {
            return null;
        }
    }

    public function getThematique()
    {
        if ($this->derniereVersion() != null) {
            return $this->derniereVersion()->getPrjThematique();
        } else {
            return null;
        }
    }

    public function getLaboratoire()
    {
        if ($this->derniereVersion() != null) {
            return $this->derniereVersion()->getPrjLLabo();
        } else {
            return null;
        }
    }

    public function derniereSession()
    {
        if ($this->derniereVersion() != null) {
            return $this->derniereVersion()->getSession();
        } else {
            return null;
        }
    }

    public function getResponsable()
    {
        if ($this->derniereVersion() != null) {
            return $this->derniereVersion()->getResponsable();
        } else {
            return null;
        }
    }

    // hum hum pas fameux
    public function getResponsables()
    {
        if ($this->derniereVersion() != null) {
            return $this->derniereVersion()->getResponsables();
        } else {
            return null;
        }
    }

    /*
     * Renvoie true si le projet est un projet test, false sinon
     * TODO - A VIRER !
     */
    public function isProjetTest()
    {
        $type = $this->getTypeProjet();
        if ($this->getTypeProjet() === Projet::PROJET_TEST) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * derniereVersion - Alias de getVersionDerniere()
    *                   TODO - A supprimer !
    *
    * @return \App\Entity\Version
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
            if ($version->isCollaborateur($individu) === true) {
                return true;
            }
        }
        return false;
    }

    ////////////////////////////////////////////////////

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
    /////////////////////////////////////////////////////


    public function getEtat(): ?int
    {
        return $this->getEtatProjet();
    }

    //public function getLibelleType()
    //{
        //$type = $this->getTypeProjet();
        //if ($type <=3 and $type > 0) {
            //return Projet::LIBELLE_TYPE[$this->getTypeProjet()];
        //} else {
            //return '?';
        //}
    //}
}
