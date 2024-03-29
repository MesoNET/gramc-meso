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
use App\GramcServices\Etat;
use App\Utils\Functions;
use App\Interfaces\Demande;

/*
 * TODO - Utiliser l'héritage pour faire hériter Version et Rallonge d'une même classe
 *        cf. https://www.doctrine-project.org/projects/doctrine-orm/en/2.14/reference/inheritance-mapping.html
 *        Pas le temps / pas le recul alors on travaille salement
 *        Emmanuel, 27/3/23
 *
 ************************************************************/

/**
 * Version
 *
 * @ORM\Table(name="version", indexes={@ORM\Index(name="etat_version", columns={"etat_version"}), @ORM\Index(name="id_projet", columns={"id_projet"}), @ORM\Index(name="prj_id_thematique", columns={"prj_id_thematique"})})
 * @ORM\Entity(repositoryClass="App\Repository\VersionRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Version implements Demande
{
    /**
     * @var integer
     *
     * @ORM\Column(name="etat_version", type="integer", nullable=true)
     */
    private $etatVersion = Etat::EDITION_DEMANDE;

    /**
     * @var integer
     *
     * @ORM\Column(name="type_version", type="integer", nullable=true, options={"comment":"type du projet associé (le type du projet peut changer)"})
     */
    private $typeVersion;

    /**
     * @var string
     *
     * @ORM\Column(name="prj_l_labo", type="string", length=300, nullable=true)
     */
    private $prjLLabo = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_titre", type="string", length=500, nullable=true)
     */
    private $prjTitre = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_financement", type="string", length=100, nullable=true)
     */
    private $prjFinancement = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_genci_machines", type="string", length=60, nullable=true)
     */
    private $prjGenciMachines = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_genci_centre", type="string", length=60, nullable=true)
     */
    private $prjGenciCentre = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_genci_heures", type="string", length=30, nullable=true)
     */
    private $prjGenciHeures = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_expose", type="text", nullable=true)
     */
    private $prjExpose = '';

    /**
     * @var string
     *
     * @ORM\Column(name="prj_justif_renouv", type="text", nullable=true)
     */
    private $prjJustifRenouv;

    /**
     * @var boolean
     *
     * @ORM\Column(name="prj_fiche_val", type="boolean", nullable=true)
     */
    private $prjFicheVal = false;

    /**
     * @var string
     *
     * @ORM\Column(name="prj_genci_dari",  type="string", length=15, nullable=true)
     */
    private $prjGenciDari = '';

    /**
     * @var string
     *
     * @ORM\Column(name="code_nom", type="string", length=150, nullable=true)
     */
    private $codeNom = '';

    /**
     * @var string
     *
     * @ORM\Column(name="code_licence", type="text", length=65535, nullable=true)
     */
    private $codeLicence = '';

    /**
     * @var string
     *
     * @ORM\Column(name="libelle_thematique", type="string", length=200, nullable=true)
     */
    private $libelleThematique ='';

    /**
     * @var \App\Entity\Individu
     * A chaque fois que la version est modifiée la personne connectée est ici
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Individu")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="maj_ind", referencedColumnName="id_individu",onDelete="SET NULL")
     * })
     */
    private $majInd;

    /**
     * @var \DateTime
     * A chaque modification on met à jour cette date
     *
     * @ORM\Column(name="maj_stamp", type="datetime", nullable=true)
     */
    private $majStamp;

    /**
     * @var \DateTime
     * Date de démarrage de la version (passage en état ACTIF)
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     */
    private $startDate;

    /**
     * @var \DateTime
     * Date de fin de la version (passage en état TERMINE)
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * @var \DateTime
     * Date limite, la version n'ira pas au-delà
     *
     * @ORM\Column(name="limit_date", type="datetime", nullable=true)
     */
    private $limitDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="prj_fiche_len", type="integer", nullable=true)
     */
    private $prjFicheLen = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="cgu", type="boolean", nullable=true)
     */
    private $CGU = false;

    /**
     * @var string
     *
     * @ORM\Column(name="id_version", type="string", length=13)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idVersion;

    /**
     * @var \App\Entity\Thematique
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Thematique", inversedBy="version")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="prj_id_thematique", referencedColumnName="id_thematique")
     * })
     */
    private $prjThematique;

    /**
     * @var string
     *
     * @ORM\Column(name="nb_version", type="string", length=5, options={"comment":"Numéro de version (01,02,03,...)"})
     * 
     */
    private $nbVersion;

    /**
     * @var \App\Entity\Projet
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Projet", cascade={"persist"},inversedBy="version")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_projet", referencedColumnName="id_projet", nullable=true )
     * })
     */
    private $projet;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\CollaborateurVersion", mappedBy="version", cascade={"persist"})
     */
    private $collaborateurVersion;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Rallonge", mappedBy="version", cascade={"persist"})
     */
    private $rallonge;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Dac", mappedBy="version", cascade={"persist"})
     */
    private $dac;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Expertise", mappedBy="version", cascade={"persist"} )
     */
    private $expertise;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\FormationVersion", mappedBy="version", cascade={"persist"} )
     */
    private $formationVersion;

    /**
     * @var \App\Entity\Version
     *
     * @ORM\OneToOne(targetEntity="\App\Entity\Projet", mappedBy="versionDerniere", cascade={"persist"} )
     */
    private $versionDerniere;

    /**
     * @var \App\Entity\Version
     *
     * @ORM\OneToOne(targetEntity="\App\Entity\Projet", mappedBy="versionActive", cascade={"persist"} )
     */
    private $versionActive;

    ///////////////////////////////////////////////////////////

    public function __toString(): string
    {
        return (string)$this->getIdVersion();
    }

    /////////////////////////////////////////////////////////////////


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->collaborateurVersion = new \Doctrine\Common\Collections\ArrayCollection();
        $this->rallonge             = new \Doctrine\Common\Collections\ArrayCollection();
        $this->dac                  = new \Doctrine\Common\Collections\ArrayCollection();
        $this->expertise            = new \Doctrine\Common\Collections\ArrayCollection();
        $this->formationVersion     = new \Doctrine\Common\Collections\ArrayCollection();
        $this->etatVersion          = Etat::EDITION_DEMANDE;
    }

    /**
     * Set etatVersion
     *
     * @param integer $etatVersion
     *
     * @return Version
     */
    public function setEtatVersion(int $etatVersion): self
    {
        $this->etatVersion = $etatVersion;

        return $this;
    }
    public function setEtat(?int $etatVersion): self
    {
        return $this->setEtatVersion($etatVersion);
    }

    /**
     * Get etatVersion
     *
     * @return integer
     */
    public function getEtatVersion(): ?int
    {
        return $this->etatVersion;
    }

    /**
     * Set typeVersion
     *
     * @param integer $typeVersion
     *
     * @return Version
     */
    public function setTypeVersion(?int $typeVersion): self
    {
        $this->typeVersion = $typeVersion;

        return $this;
    }

    /**
     * Get typeVersion
     *
     * @return integer
     */
    public function getTypeVersion(): ?int
    {
        return $this->typeVersion;
    }

    /**
     * Set prjLLabo
     *
     * @param string $prjLLabo
     *
     * @return Version
     */
    public function setPrjLLabo(?string $prjLLabo): self
    {
        $this->prjLLabo = $prjLLabo;

        return $this;
    }

    /**
     * Get prjLLabo
     *
     * @return string
     */
    public function getPrjLLabo(): ?string
    {
        return $this->prjLLabo;
    }

    /**
     * Set prjTitre
     *
     * @param string $prjTitre
     *
     * @return Version
     */
    public function setPrjTitre(?string $prjTitre): self
    {
        $this->prjTitre = $prjTitre;

        return $this;
    }

    /**
     * Get prjTitre
     *
     * @return string
     */
    public function getPrjTitre(): ?string
    {
        return $this->prjTitre;
    }

    /**
     * Set prjFinancement
     *
     * @param string $prjFinancement
     *
     * @return Version
     */
    public function setPrjFinancement(?string $prjFinancement): self
    {
        $this->prjFinancement = $prjFinancement;

        return $this;
    }

    /**
     * Get prjFinancement
     *
     * @return string
     */
    public function getPrjFinancement(): ?string
    {
        return $this->prjFinancement;
    }

    /**
     * Set prjGenciMachines
     *
     * @param string $prjGenciMachines
     *
     * @return Version
     */
    public function setPrjGenciMachines(?string $prjGenciMachines): self
    {
        $this->prjGenciMachines = $prjGenciMachines;

        return $this;
    }

    /**
     * Get prjGenciMachines
     *
     * @return string
     */
    public function getPrjGenciMachines(): ?string
    {
        return $this->prjGenciMachines;
    }

    /**
     * Set prjGenciCentre
     *
     * @param string $prjGenciCentre
     *
     * @return Version
     */
    public function setPrjGenciCentre(?string $prjGenciCentre): self
    {
        $this->prjGenciCentre = $prjGenciCentre;

        return $this;
    }

    /**
     * Get prjGenciCentre
     *
     * @return string
     */
    public function getPrjGenciCentre(): ?string
    {
        return $this->prjGenciCentre;
    }

    /**
     * Set prjGenciDari
     *
     * @param string $prjGenciDari
     *
     * @return Version
     */
    public function setPrjGenciDari(?string $prjGenciDari): self
    {
        $this->prjGenciDari = $prjGenciDari;

        return $this;
    }

    /**
     * Get prjGenciDari
     *
     * @return string
     */
    public function getPrjGenciDari(): ?string
    {
        return $this->prjGenciDari;
    }

    /**
     * Set prjGenciHeures
     *
     * @param string $prjGenciHeures
     *
     * @return Version
     */
    public function setPrjGenciHeures(?string $prjGenciHeures): self
    {
        $this->prjGenciHeures = $prjGenciHeures;

        return $this;
    }

    /**
     * Get prjGenciHeures
     *
     * @return string
     */
    public function getPrjGenciHeures(): ?string
    {
        return $this->prjGenciHeures;
    }

    /**
     * Set prjExpose
     *
     * @param string $prjExpose
     *
     * @return Version
     */
    public function setPrjExpose(?string $prjExpose): self
    {
        $this->prjExpose = $prjExpose;

        return $this;
    }

    /**
     * Get prjExpose
     *
     * @return string
     */
    public function getPrjExpose(): ?string
    {
        return $this->prjExpose;
    }

    /**
     * Set prjJustifRenouv
     *
     * @param string $prjJustifRenouv
     *
     * @return Version
     */
    public function setPrjJustifRenouv(?string $prjJustifRenouv): self
    {
        $this->prjJustifRenouv = $prjJustifRenouv;

        return $this;
    }

    /**
     * Get prjJustifRenouv
     *
     * @return string
     */
    public function getPrjJustifRenouv():?string
    {
        return $this->prjJustifRenouv;
    }

    /**
     * Set prjFicheVal
     *
     * @param boolean $prjFicheVal
     *
     * @return Version
     */
    public function setPrjFicheVal(?string $prjFicheVal): self
    {
        $this->prjFicheVal = $prjFicheVal;

        return $this;
    }

    /**
     * Get prjFicheVal
     *
     * @return boolean
     */
    public function getPrjFicheVal(): ?string
    {
        return $this->prjFicheVal;
    }

    /**
     * Set codeNom
     *
     * @param string $codeNom
     *
     * @return Version
     */
    public function setCodeNom(?string $codeNom): self
    {
        $this->codeNom = $codeNom;

        return $this;
    }

    /**
     * Get codeNom
     *
     * @return string
     */
    public function getCodeNom(): ?string
    {
        return $this->codeNom;
    }

    /**
     * Set codeLicence
     *
     * @param string $codeLicence
     *
     * @return Version
     */
    public function setCodeLicence(?string $codeLicence): self
    {
        $this->codeLicence = $codeLicence;

        return $this;
    }

    /**
     * Get codeLicence
     *
     * @return string
     */
    public function getCodeLicence(): ?string
    {
        return $this->codeLicence;
    }

    /**
     * Set libelleThematique
     *
     * @param string $libelleThematique
     *
     * @return Version
     */
    public function setLibelleThematique(?string $libelleThematique): self
    {
        $this->libelleThematique = $libelleThematique;

        return $this;
    }

    /**
     * Get libelleThematique
     *
     * @return string
     */
    public function getLibelleThematique(): ?string
    {
        return $this->libelleThematique;
    }

    /**
     * Set majInd
     *
     * @param App\Entity\Individu
     *
     * @return Version
     */
    public function setMajInd( ? \App\Entity\Individu $majInd): self
    {
        $this->majInd = $majInd;

        return $this;
    }

    /**
     * Get majInd
     *
     * @return App\Entity\Individu
     */
    public function getMajInd(): ? \App\Entity\Individu
    {
        return $this->majInd;
    }

    /**
     * Set majStamp
     *
     * @param \DateTime $majStamp
     *
     * @return Version
     */
    public function setMajStamp(?\DateTime $majStamp): self
    {
        $this->majStamp = $majStamp;

        return $this;
    }

    /**
     * Get majStamp
     *
     * @return \DateTime
     */
    public function getMajStamp(): ?\DateTime
    {
        return $this->majStamp;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return Version
     */
    public function setStartDate(?\DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return Version
     */
    public function setEndDate(?\DateTime $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    /**
     * Set limitDate
     *
     * @param \DateTime $limitDate
     *
     * @return Version
     */
    public function setLimitDate(?\DateTime $limitDate)
    {
        $this->limitDate = $limitDate;

        return $this;
    }

    /**
     * Get limiteDate
     *
     * @return \DateTime
     */
    public function getLimitDate(): ?\DateTime
    {
        return $this->limitDate;
    }

    /**
     * Set fctStamp
     *
     * @param \DateTime $fctStamp
     *
     * @return Version
     */
    public function setFctStamp(?\DateTime $fctStamp): self
    {
        $this->fctStamp = $fctStamp;

        return $this;
    }

    /**
     * Get fctStamp
     *
     * @return \DateTime
     */
    public function getFctStamp(): ?\DateTime
    {
        return $this->fctStamp;
    }

    /**
     * Set prjFicheLen
     *
     * @param integer $prjFicheLen
     *
     * @return Version
     */
    public function setPrjFicheLen(?int $prjFicheLen): self
    {
        $this->prjFicheLen = $prjFicheLen;

        return $this;
    }

    /**
     * Get prjFicheLen
     *
     * @return integer
     */
    public function getPrjFicheLen(): ?int
    {
        return $this->prjFicheLen;
    }

    /**
     * Set idVersion
     *
     * @param string $idVersion
     *
     * @return Version
     */
    public function setIdVersion(string $idVersion): self
    {
        $this->idVersion = $idVersion;

        return $this;
    }

    /**
     * Get idVersion
     *
     * @return string
     */
    public function getIdVersion(): ?string
    {
        return $this->idVersion;
    }

    /****
     * Get AutreIdVersion
     *
     * 	19AP01234 => 19BP01234
     *  19BP01234 => 19AP01234
     *
     * @return string
     *
     */
    //public function getAutreIdVersion()
    //{
        //$id = $this->getIdVersion();
        //$id[2] = ($id[2]==='A') ? 'B' : 'A';
        //return $id;
    //}

    /**
     * Set CGU
     *
     * @param boolean $CGU
     *
     * @return Version
     */
    public function setCGU(? bool $CGU): self
    {
        $this->CGU = $CGU;

        return $this;
    }

    /**
     * Get CGU
     *
     * @return boolean
     */
    public function getCGU(): ?bool
    {
        return $this->CGU;
    }

    /**
     * Set prjThematique
     *
     * @param \App\Entity\Thematique $prjThematique
     *
     * @return Version
     */
    public function setPrjThematique(?\App\Entity\Thematique $prjThematique = null): self
    {
        $this->prjThematique = $prjThematique;

        return $this;
    }

    /**
     * Get prjThematique
     *
     * @return \App\Entity\Thematique
     */
    public function getPrjThematique(): ?\App\Entity\Thematique
    {
        return $this->prjThematique;
    }

    /**
     * Set nbVersion
     *
     * @param string $idVersion
     *
     * @return Version
     */
    public function setNbVersion(int $nbVersion): self
    {
        $this->nbVersion = $nbVersion;

        return $this;
    }

    /**
     * Get nbVersion
     *
     * @return string
     */
    public function getNbVersion(): ?int
    {
        return $this->nbVersion;
    }

    /**
     * Set projet
     *
     * @param \App\Entity\Projet $projet
     *
     * @return Version
     */
    public function setProjet(? \App\Entity\Projet $projet = null): self
    {
        $this->projet = $projet;

        // On recopie le type de projet
        $this->setTypeVersion($projet->getTypeProjet());

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
     * Add collaborateurVersion
     *
     * @param \App\Entity\CollaborateurVersion $collaborateurVersion
     *
     * @return Version
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
     * Add rallonge
     *
     * @param \App\Entity\Rallonge $rallonge
     *
     * @return Version
     */
    public function addRallonge(\App\Entity\Rallonge $rallonge): self
    {
        if (! $this->rallonge->contains($rallonge))
        {
            $this->rallonge[] = $rallonge;
        }

        return $this;
    }

    /**
     * Remove rallonge
     *
     * @param \App\Entity\Rallonge $rallonge
     */
    public function removeRallonge(\App\Entity\Rallonge $rallonge): self
    {
        $this->rallonge->removeElement($rallonge);
        return $this;
    }

    /**
     * Get rallonge
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRallonge()
    {
        return $this->rallonge;
    }

    /**
     * Add dac
     *
     * @param \App\Entity\Dac $dac
     *
     * @return Version
     */
    public function addDac(\App\Entity\Dac $dac): self
    {
        if (! $this->dac->contains($dac))
        {
            $this->dac[] = $dac;
        }
        return $this;
    }

    /**
     * Remove dac
     *
     * @param \App\Entity\Dac $dac
     */
    public function removeDac(\App\Entity\Dac $dac): self
    {
        $this->dac->removeElement($dac);
        return $this;
    }

    /**
     * Get dac
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDac()
    {
        return $this->dac;
    }

    // Expertise

    /**
     * Add expertise
     *
     * @param \App\Entity\Expertise $expertise
     *
     * @return Version
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

    // Formation

    /**
     * Add formationVersion
     *
     * @param \App\Entity\Formation $formation
     *
     * @return Version
     */
    public function addFormationVersion(\App\Entity\FormationVersion $formationVersion): self
    {
        if (! $this->formationVersion->contains($formationVersion))
        {
            $this->formationVersion[] = $formationVersion;
        }

        return $this;
    }

    /**
     * Remove formationVersion
     *
     * @param \App\Entity\FormationVersion $formationVersion
     */
    public function removeFormationVersion(\App\Entity\FormationVersion $formationVersion): self
    {
        if ($this->formationVersion->contains($formationVersion))
        {
            $this->formationVersion->removeElement($formationVersion);
            return $this;
        }
    }

    /**
     * Get formationVersion
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFormationVersion()
    {
        return $this->formationVersion;
    }

    /***************************************************
     * Fonctions utiles pour la class Workflow
     * Autre nom pour getEtatVersion/setEtatVersion !
     ***************************************************/
    public function getObjectState(): ?int
    {
        return $this->getEtatVersion();
    }

    public function setObjectState(?int $state): self
    {
        $this->setEtatVersion($state);
        return $this;
    }

    ///////////////////////////////////////////////////////////////////////////////////

    /* pour bilan session depuis la table CollaborateurVersion
     *
     * getResponsable
     *
     * @return \App\Entity\Individu
     */
    public function getResponsable(): ?\App\Entity\Individu
    {
        foreach ($this->getCollaborateurVersion() as $item) {
            if ($item->getResponsable() === true) {
                return $item->getCollaborateur();
            }
        }
        return null;
    }

    public function getResponsables(): array
    {
        $responsables   = [];
        foreach ($this->getCollaborateurVersion() as $item) {
            if ($item->getResponsable() === true) {
                $responsables[] = $item->getCollaborateur();
            }
        }
        return $responsables;
    }

    /*****************************************************
     * Renvoie les collaborateurs de la version
     *
     * $moi_aussi           === true : je peux être dans la liste éventuellement
     * $seulement_eligibles === true : Individu permanent et d'un labo régional à la fois
     * $moi                 === Individu connecté, qui est $moi (utile seulement si $moi_aussi est false)
     *
     ************************************************************/
    public function getCollaborateurs(bool $moi_aussi=true, bool $seulement_eligibles=false, ?Individu $moi=null): array
    {
        $collaborateurs = [];
        foreach ($this->getCollaborateurVersion() as $item) {
            $collaborateur   =  $item->getCollaborateur();
            if ($collaborateur === null) {
                //$sj->errorMessage("Version:getCollaborateur : collaborateur null pour CollaborateurVersion ". $item->getId() );
                continue;
            }
            if ($moi_aussi === false && $collaborateur->isEqualTo($moi)) {
                continue;
            }
            if ($seulement_eligibles === false || ($collaborateur->isPermanent() && $collaborateur->isFromLaboRegional())) {
                $collaborateurs[] = $collaborateur;
            }
        }
        return $collaborateurs;
    }

    /*
     *
     * getLabo
     *
     * @return \App\Entity\Laboratoire
     */
     // TODO - Wrapper vers getPrjLLabo, ne sert à rien !
    public function getLabo(): ?\App\Entity\Laboratoire
    {
        return $this->getPrjLLabo();
    }

    public function getExpert(): ?\App\Entity\Individu
    {
        $expertise =  $this->getOneExpertise();
        if ($expertise === null)
        {
            return null;
        }
        else
        {
            return $expertise->getExpert();
        }
    }

    // pour notifications ou affichage
    public function getExperts(): array
    {
        $experts = [];
        foreach ($this->getExpertise() as $item) {
            $experts[] = $item ->getExpert();
        }
        return $experts;
    }

    public function hasExpert(): bool
    {
        $expertise =  $this->getOneExpertise();
        if ($expertise === null)
        {
            return false;
        }

        $expert = $expertise->getExpert();
        if ($expert != null)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    // pour notifications
    public function getExpertsThematique() : ?\App\Entity\Individu
    {
        $thematique = $this->getPrjThematique();
        if ($thematique === null)
        {
            return null;
        }
        else
        {
            return $thematique->getExpert();
        }
    }
   
    ////////////////////////////////////////////////////////////////////

    public function getLibelleEtat()
    {
        return Etat::getLibelle($this->getEtatVersion());
    }
    public function getTitreCourt()
    {
        $titre = $this->getPrjTitre();

        if (strlen($titre) <= 20) {
            return $titre;
        } else {
            return substr($titre, 0, 20) . "...";
        }
    }

    public function getAcroLaboratoire()
    {
        return preg_replace('/^\s*([^\s]+)\s+(.*)$/', '${1}', $this->getPrjLLabo());
    }

    // MetaEtat d'une version (et du projet associé)
    // Ne sert que pour l'affichage des états de version
    public function getMetaEtat(): ?string
    {
        $etat = $this->getEtatVersion();

        if ($etat === Etat::ACTIF)
        {
            return 'ACTIF';
        }
        elseif ($etat === Etat::ACTIF_R)
        {
            return 'A RENOUVELER';
        }
        elseif ($etat === Etat::NOUVELLE_VERSION_DEMANDEE)
        {
            return 'PRESQUE TERMINE';
        }
        elseif ($etat === Etat::ANNULE)
        {
            return 'ANNULE';
        }
        elseif ($etat === Etat::EDITION_DEMANDE)
        {
            return 'EDITION';
        }
        elseif ($etat === Etat::EDITION_EXPERTISE)
        {
            return 'VALIDATION';
        }
        elseif ($etat === Etat::TERMINE)
        {
            return 'TERMINE';
        }
        elseif ($etat === Etat::REFUSE)
        {
            return 'REFUSE';
        }
        return 'INCONNU';
    }

    //
    // Individu est-il collaborateur ? Responsable ? Expert ?
    //

    public function isCollaborateur(?Individu $individu): bool
    {
        if ($individu === null)
        {
            return false;
        }

        foreach ($this->getCollaborateurVersion() as $item) {
            if ($item->getCollaborateur() === null)
                //$sj->errorMessage('Version:isCollaborateur collaborateur null pour CollaborateurVersion ' . $item);
                ; elseif ($item->getCollaborateur()->isEqualTo($individu)) {
                    return true;
                }
        }

        return false;
    }

    public function isResponsable(?Individu $individu): bool
    {
        if ($individu === null)
        {
            return false;
        }

        foreach ($this->getCollaborateurVersion() as $item)
        {
            if ($item->getCollaborateur() === null)
                //$sj->errorMessage('Version:isCollaborateur collaborateur null pour CollaborateurVersion ' . $item);
                ; elseif ($item->getCollaborateur()->isEqualTo($individu) && $item->getResponsable() === true)
                {
                    return true;
                }
        }

        return false;
    }

    public function isExpertDe(?Individu $individu): bool
    {
        if ($individu === null)
        {
            return false;
        }

        foreach ($this->getExpertise() as $expertise)
        {
            $expert = $expertise->getExpert();

            if ($expert === null)
                //$sj->errorMessage("Version:isExpert Expert null dans l'expertise " . $item);
                ; elseif ($expert->isEqualTo($individu))
                {
                    return true;
                }
        }
        return false;
    }

    public function isExpertThematique(?Individu $individu)
    {
        if ($individu === null) {
            return false;
        }

        ////$sj->debugMessage(__METHOD__ . " thematique : " . Functions::show($thematique) );

        $thematique = $this->getPrjThematique();
        if ($thematique != null) {
            foreach ($thematique->getExpert() as $expert) {
                if ($expert->isEqualTo($individu)) {
                    return true;
                }
            }
        }
        return false;
    }

    ////////////////////////////////////

    //public function versionPrecedente()
    //{
        //// Contrairement au nom ne renvoie pas la version précédente, mais l'avant-dernière !!!
        //// La fonction versionPrecedente1() renvoie pour de vrai la version précédente
        //// TODO - Supprimer cette fonction, ou la renommer
        //$versions   =  $this->getProjet()->getVersion();
        //if (count($versions) <= 1) {
            //return null;
        //}

        //$versions   =   $versions->toArray();
        //usort(
            //$versions,
            //function (Version $b, Version $a) {
                //return strcmp($a->getIdVersion(), $b->getIdVersion());
            //}
        //);

        ////$sj->debugMessage( __METHOD__ .':'. __LINE__ . " version ID 0 1 = " . $versions[0]." " . $versions[1] );
        //return $versions[1];
    //}

    public function versionPrecedente(): ?self
    {
        $versions   =  $this->getProjet()->getVersion() -> toArray();
        // On trie les versions dans l'ordre croissant
        usort(
            $versions,
            function (Version $a, Version $b) {
                return strcmp($a->getIdVersion(), $b->getIdVersion());
            }
        );
        $k = array_search($this->getIdVersion(), $versions);
        if ($k===false || $k===0)
        {
            return null;
        }
        else
        {
            return $versions[$k-1];
        }
    }


    //////////////////////////////////////////////

    /*
     * TODO - Serait mieux dans ServiceVersions
     *        Session 22A -> Renvoie la dernière année où il y a eu une version
     *                       (normalement 2021, mais peut-être une année antérieure)
     *
     *
     *************************************/
    //public function anneeRapport()
    //{
        //$anneeRapport = 0;
        //$myAnnee    =  substr($this->getIdVersion(), 0, 2);
        //foreach ($this->getProjet()->getVersion() as $version) {
            //$annee = substr($version->getIdVersion(), 0, 2);
            //if ($annee < $myAnnee) {
                //$anneeRapport = max($annee, $anneeRapport);
            //}
        //}

        //if ($anneeRapport < 10 && $anneeRapport > 0) {
            //return '200' . $anneeRapport ;
        //} elseif ($anneeRapport >= 10) {
            //return '20' . $anneeRapport ;
        //} else {
            //return '0';
        //}
    //}

    ///////////////////////////////////////////////

    /*********
    * Renvoie l'expertise 0 si elle existe, null sinon
    ***************/
    public function getOneExpertise()
    {
        $expertises =   $this->getExpertise()->toArray();
        if ($expertises !=  null) {
            //$expertise  =   current( $expertises );
            $expertise = $expertises[0];

            //Functions::debugMessage(__METHOD__ . " expertise = " . Functions::show( $expertise )
            //    . " expertises = " . Functions::show( $expertises ));
            return $expertise;
        } else {
            //Functions::noticeMessage(__METHOD__ . " version " . $this . " n'a pas d'expertise !");
            return null;
        }
    }

    ////////////////////////////////////////////////////

    //public function isProjetTest()
    //{
        //$projet =   $this->getProjet();
        //if ($projet === null) {
            ////$sj->errorMessage(__METHOD__ . ":" . __LINE__ . " version " . $this . " n'est pas associée à un projet !");
            //return false;
        //} else {
            //return $projet->isProjetTest();
        //}
    //}

    /////////////////////////////////////////////////////

    public function isEdited()
    {
        $etat = $this->getEtatVersion();
        return $etat === Etat::EDITION_DEMANDE || $etat === Etat::EDITION_TEST;
    }

    //////////////////////////////////////////////

    public function getAcroEtablissement()
    {
        $responsable = $this->getResponsable();
        if ($responsable === null)
        {
            return "";
        }

        $etablissement = $responsable->getEtab();
        if ($etablissement === null)
        {
            return "";
        }

        return $etablissement->__toString();
    }

    //////////////////////////////////////////////

    public function getAcroThematique()
    {
        $thematique = $this->getPrjThematique();
        if ($thematique === null)
        {
            return "sans thématique";
        }
        else
        {
            return $thematique->__toString();
        }
    }

    /////////////////////////////////////////////////////
    public function getEtat(): ?int
    {
        return $this->getEtatVersion();
    }
    public function getId(): ?string
    {
        return $this->getIdVersion();
    }
}
