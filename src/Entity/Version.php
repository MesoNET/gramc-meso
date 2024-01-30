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
use App\GramcServices\Etat;
use App\Interfaces\Demande;
use App\Utils\Functions;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/*
 * TODO - Utiliser l'héritage pour faire hériter Version et Rallonge d'une même classe
 *        cf. https://www.doctrine-project.org/projects/doctrine-orm/en/2.14/reference/inheritance-mapping.html
 *        Pas le temps / pas le recul alors on travaille salement
 *        Emmanuel, 27/3/23
 *
 ************************************************************/
/**
 * Version.
 */
#[ORM\Table(name: 'version')]
#[ORM\Index(name: 'etat_version', columns: ['etat_version'])]
#[ORM\Index(name: 'id_projet', columns: ['id_projet'])]
#[ORM\Index(name: 'prj_id_thematique', columns: ['prj_id_thematique'])]
#[ORM\Entity(repositoryClass: 'App\Repository\VersionRepository')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class Version implements Demande
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'etat_version', type: 'integer', nullable: true)]
    private $etatVersion = Etat::EDITION_DEMANDE;

    /**
     * @var int
     */
    #[ORM\Column(name: 'type_version', type: 'integer', nullable: true, options: ['comment' => 'type du projet associé (le type du projet peut changer)'])]
    private $typeVersion;

    /**
     * @var string
     */
    #[ORM\Column(name: 'prj_l_labo', type: 'string', length: 300, nullable: true)]
    private $prjLLabo = '';

    /**
     * @var string
     */
    #[ORM\Column(name: 'prj_titre', type: 'string', length: 500, nullable: true)]
    private $prjTitre = '';

    /**
     * @var string
     */
    #[ORM\Column(name: 'prj_financement', type: 'string', length: 100, nullable: true)]
    private $prjFinancement = '';

    /**
     * @var string
     */
    #[ORM\Column(name: 'prj_genci_machines', type: 'string', length: 60, nullable: true)]
    private $prjGenciMachines = '';

    /**
     * @var string
     */
    #[ORM\Column(name: 'prj_genci_centre', type: 'string', length: 60, nullable: true)]
    private $prjGenciCentre = '';

    /**
     * @var string
     */
    #[ORM\Column(name: 'prj_genci_heures', type: 'string', length: 30, nullable: true)]
    private $prjGenciHeures = '';

    /**
     * @var string
     */
    #[ORM\Column(name: 'prj_expose', type: 'text', nullable: true)]
    private $prjExpose = '';

    /**
     * @var string
     */
    #[ORM\Column(name: 'prj_justif_renouv', type: 'text', nullable: true)]
    private $prjJustifRenouv;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'prj_fiche_val', type: 'boolean', nullable: true)]
    private $prjFicheVal = false;

    /**
     * @var string
     */
    #[ORM\Column(name: 'prj_genci_dari', type: 'string', length: 15, nullable: true)]
    private $prjGenciDari = '';

    /**
     * @var string
     */
    #[ORM\Column(name: 'code_nom', type: 'string', length: 150, nullable: true)]
    private $codeNom = '';

    /**
     * @var string
     */
    #[ORM\Column(name: 'code_licence', type: 'text', length: 65535, nullable: true)]
    private $codeLicence = '';

    /**
     * @var string
     */
    #[ORM\Column(name: 'libelle_thematique', type: 'string', length: 200, nullable: true)]
    private $libelleThematique = '';

    /**
     * @var Individu
     *               A chaque fois que la version est modifiée la personne connectée est ici
     */
    #[ORM\JoinColumn(name: 'maj_ind', referencedColumnName: 'id_individu', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Individu')]
    private $majInd;

    /**
     * @var \DateTime
     *                A chaque modification on met à jour cette date
     */
    #[ORM\Column(name: 'maj_stamp', type: 'datetime', nullable: true)]
    private $majStamp;

    /**
     * @var \DateTime
     *                Date de démarrage de la version (passage en état ACTIF)
     */
    #[ORM\Column(name: 'start_date', type: 'datetime', nullable: true)]
    private $startDate;

    /**
     * @var \DateTime
     *                Date de fin de la version (passage en état TERMINE)
     */
    #[ORM\Column(name: 'end_date', type: 'datetime', nullable: true)]
    private $endDate;

    /**
     * @var \DateTime
     *                Date limite, la version n'ira pas au-delà
     */
    #[ORM\Column(name: 'limit_date', type: 'datetime', nullable: true)]
    private $limitDate;

    /**
     * @var int
     */
    #[ORM\Column(name: 'prj_fiche_len', type: 'integer', nullable: true)]
    private $prjFicheLen = 0;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'cgu', type: 'boolean', nullable: true)]
    private $CGU = false;

    /**
     * @var string
     */
    #[ORM\Column(name: 'id_version', type: 'string', length: 13)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $idVersion;

    /**
     * @var Thematique
     */
    #[ORM\JoinColumn(name: 'prj_id_thematique', referencedColumnName: 'id_thematique')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Thematique', inversedBy: 'version')]
    private $prjThematique;

    /**
     * @var string
     */
    #[ORM\Column(name: 'nb_version', type: 'string', length: 5, options: ['comment' => 'Numéro de version (01,02,03,...)'])]
    private $nbVersion;

    /**
     * @var Projet
     */
    #[ORM\JoinColumn(name: 'id_projet', referencedColumnName: 'id_projet', nullable: true)]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Projet', cascade: ['persist'], inversedBy: 'version')]
    private $projet;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\CollaborateurVersion', mappedBy: 'version', cascade: ['persist'])]
    private $collaborateurVersion;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Rallonge', mappedBy: 'version', cascade: ['persist'])]
    private $rallonge;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Expertise', mappedBy: 'version', cascade: ['persist'])]
    private $expertise;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\FormationVersion', mappedBy: 'version', cascade: ['persist'])]
    private $formationVersion;

    /**
     * @var Version
     */
    #[ORM\OneToOne(targetEntity: '\App\Entity\Projet', mappedBy: 'versionDerniere', cascade: ['persist'])]
    private $versionDerniere;

    /**
     * @var Version
     */
    #[ORM\OneToOne(targetEntity: '\App\Entity\Projet', mappedBy: 'versionActive', cascade: ['persist'])]
    private $versionActive;

    #[ORM\OneToMany(mappedBy: 'version', targetEntity: Dac::class)]
    private Collection $dac;

    // /////////////////////////////////////////////////////////

    public function __toString(): string
    {
        return (string) $this->getIdVersion();
    }

    // ///////////////////////////////////////////////////////////////

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->collaborateurVersion = new ArrayCollection();
        $this->rallonge = new ArrayCollection();
        $this->dac = new ArrayCollection();
        $this->expertise = new ArrayCollection();
        $this->formationVersion = new ArrayCollection();
        $this->etatVersion = Etat::EDITION_DEMANDE;
    }

    /**
     * Set etatVersion.
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
     * Get etatVersion.
     */
    public function getEtatVersion(): ?int
    {
        return $this->etatVersion;
    }

    /**
     * Set typeVersion.
     */
    public function setTypeVersion(?int $typeVersion): self
    {
        $this->typeVersion = $typeVersion;

        return $this;
    }

    /**
     * Get typeVersion.
     */
    public function getTypeVersion(): ?int
    {
        return $this->typeVersion;
    }

    /**
     * Set prjLLabo.
     */
    public function setPrjLLabo(?string $prjLLabo): self
    {
        $this->prjLLabo = $prjLLabo;

        return $this;
    }

    /**
     * Get prjLLabo.
     */
    public function getPrjLLabo(): ?string
    {
        return $this->prjLLabo;
    }

    /**
     * Set prjTitre.
     */
    public function setPrjTitre(?string $prjTitre): self
    {
        $this->prjTitre = $prjTitre;

        return $this;
    }

    /**
     * Get prjTitre.
     */
    public function getPrjTitre(): ?string
    {
        return $this->prjTitre;
    }

    /**
     * Set prjFinancement.
     */
    public function setPrjFinancement(?string $prjFinancement): self
    {
        $this->prjFinancement = $prjFinancement;

        return $this;
    }

    /**
     * Get prjFinancement.
     */
    public function getPrjFinancement(): ?string
    {
        return $this->prjFinancement;
    }

    /**
     * Set prjGenciMachines.
     */
    public function setPrjGenciMachines(?string $prjGenciMachines): self
    {
        $this->prjGenciMachines = $prjGenciMachines;

        return $this;
    }

    /**
     * Get prjGenciMachines.
     */
    public function getPrjGenciMachines(): ?string
    {
        return $this->prjGenciMachines;
    }

    /**
     * Set prjGenciCentre.
     */
    public function setPrjGenciCentre(?string $prjGenciCentre): self
    {
        $this->prjGenciCentre = $prjGenciCentre;

        return $this;
    }

    /**
     * Get prjGenciCentre.
     */
    public function getPrjGenciCentre(): ?string
    {
        return $this->prjGenciCentre;
    }

    /**
     * Set prjGenciDari.
     */
    public function setPrjGenciDari(?string $prjGenciDari): self
    {
        $this->prjGenciDari = $prjGenciDari;

        return $this;
    }

    /**
     * Get prjGenciDari.
     */
    public function getPrjGenciDari(): ?string
    {
        return $this->prjGenciDari;
    }

    /**
     * Set prjGenciHeures.
     */
    public function setPrjGenciHeures(?string $prjGenciHeures): self
    {
        $this->prjGenciHeures = $prjGenciHeures;

        return $this;
    }

    /**
     * Get prjGenciHeures.
     */
    public function getPrjGenciHeures(): ?string
    {
        return $this->prjGenciHeures;
    }

    /**
     * Set prjExpose.
     */
    public function setPrjExpose(?string $prjExpose): self
    {
        $this->prjExpose = $prjExpose;

        return $this;
    }

    /**
     * Get prjExpose.
     */
    public function getPrjExpose(): ?string
    {
        return $this->prjExpose;
    }

    /**
     * Set prjJustifRenouv.
     */
    public function setPrjJustifRenouv(?string $prjJustifRenouv): self
    {
        $this->prjJustifRenouv = $prjJustifRenouv;

        return $this;
    }

    /**
     * Get prjJustifRenouv.
     */
    public function getPrjJustifRenouv(): ?string
    {
        return $this->prjJustifRenouv;
    }

    /**
     * Set prjFicheVal.
     *
     * @param bool $prjFicheVal
     */
    public function setPrjFicheVal(?string $prjFicheVal): self
    {
        $this->prjFicheVal = $prjFicheVal;

        return $this;
    }

    /**
     * Get prjFicheVal.
     *
     * @return bool
     */
    public function getPrjFicheVal(): ?string
    {
        return $this->prjFicheVal;
    }

    /**
     * Set codeNom.
     */
    public function setCodeNom(?string $codeNom): self
    {
        $this->codeNom = $codeNom;

        return $this;
    }

    /**
     * Get codeNom.
     */
    public function getCodeNom(): ?string
    {
        return $this->codeNom;
    }

    /**
     * Set codeLicence.
     */
    public function setCodeLicence(?string $codeLicence): self
    {
        $this->codeLicence = $codeLicence;

        return $this;
    }

    /**
     * Get codeLicence.
     */
    public function getCodeLicence(): ?string
    {
        return $this->codeLicence;
    }

    /**
     * Set libelleThematique.
     */
    public function setLibelleThematique(?string $libelleThematique): self
    {
        $this->libelleThematique = $libelleThematique;

        return $this;
    }

    /**
     * Get libelleThematique.
     */
    public function getLibelleThematique(): ?string
    {
        return $this->libelleThematique;
    }

    /**
     * Set majInd.
     *
     * @param Individu
     */
    public function setMajInd(?Individu $majInd): self
    {
        $this->majInd = $majInd;

        return $this;
    }

    /**
     * Get majInd.
     */
    public function getMajInd(): ?Individu
    {
        return $this->majInd;
    }

    /**
     * Set majStamp.
     */
    public function setMajStamp(?\DateTime $majStamp): self
    {
        $this->majStamp = $majStamp;

        return $this;
    }

    /**
     * Get majStamp.
     */
    public function getMajStamp(): ?\DateTime
    {
        return $this->majStamp;
    }

    /**
     * Set startDate.
     */
    public function setStartDate(?\DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate.
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    /**
     * Set endDate.
     */
    public function setEndDate(?\DateTime $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate.
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    /**
     * Set limitDate.
     *
     * @return Version
     */
    public function setLimitDate(?\DateTime $limitDate)
    {
        $this->limitDate = $limitDate;

        return $this;
    }

    /**
     * Get limiteDate.
     */
    public function getLimitDate(): ?\DateTime
    {
        return $this->limitDate;
    }

    /**
     * Set fctStamp.
     */
    public function setFctStamp(?\DateTime $fctStamp): self
    {
        $this->fctStamp = $fctStamp;

        return $this;
    }

    /**
     * Get fctStamp.
     */
    public function getFctStamp(): ?\DateTime
    {
        return $this->fctStamp;
    }

    /**
     * Set prjFicheLen.
     */
    public function setPrjFicheLen(?int $prjFicheLen): self
    {
        $this->prjFicheLen = $prjFicheLen;

        return $this;
    }

    /**
     * Get prjFicheLen.
     */
    public function getPrjFicheLen(): ?int
    {
        return $this->prjFicheLen;
    }

    /**
     * Set idVersion.
     */
    public function setIdVersion(string $idVersion): self
    {
        $this->idVersion = $idVersion;

        return $this;
    }

    /**
     * Get idVersion.
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
    // public function getAutreIdVersion()
    // {
    // $id = $this->getIdVersion();
    // $id[2] = ($id[2]==='A') ? 'B' : 'A';
    // return $id;
    // }

    /**
     * Set CGU.
     */
    public function setCGU(?bool $CGU): self
    {
        $this->CGU = $CGU;

        return $this;
    }

    /**
     * Get CGU.
     */
    public function getCGU(): ?bool
    {
        return $this->CGU;
    }

    /**
     * Set prjThematique.
     */
    public function setPrjThematique(Thematique $prjThematique = null): self
    {
        $this->prjThematique = $prjThematique;

        return $this;
    }

    /**
     * Get prjThematique.
     */
    public function getPrjThematique(): ?Thematique
    {
        return $this->prjThematique;
    }

    /**
     * Set nbVersion.
     */
    public function setNbVersion(int $nbVersion): self
    {
        $this->nbVersion = $nbVersion;

        return $this;
    }

    /**
     * Get nbVersion.
     *
     * @return string
     */
    public function getNbVersion(): ?int
    {
        return $this->nbVersion;
    }

    /**
     * Set projet.
     */
    public function setProjet(Projet $projet = null): self
    {
        $this->projet = $projet;

        // On recopie le type de projet
        $this->setTypeVersion($projet->getTypeProjet());

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
     * Add rallonge.
     */
    public function addRallonge(Rallonge $rallonge): self
    {
        if (!$this->rallonge->contains($rallonge)) {
            $this->rallonge[] = $rallonge;
        }

        return $this;
    }

    /**
     * Remove rallonge.
     */
    public function removeRallonge(Rallonge $rallonge): self
    {
        $this->rallonge->removeElement($rallonge);

        return $this;
    }

    /**
     * Get rallonge.
     *
     * @return Collection
     */
    public function getRallonge()
    {
        return $this->rallonge;
    }
    // Expertise

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

    // Formation

    /**
     * Add formationVersion.
     */
    public function addFormationVersion(FormationVersion $formationVersion): self
    {
        if (!$this->formationVersion->contains($formationVersion)) {
            $this->formationVersion[] = $formationVersion;
        }

        return $this;
    }

    /**
     * Remove formationVersion.
     */
    public function removeFormationVersion(FormationVersion $formationVersion): self
    {
        if ($this->formationVersion->contains($formationVersion)) {
            $this->formationVersion->removeElement($formationVersion);

            return $this;
        }
    }

    /**
     * Get formationVersion.
     *
     * @return Collection
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

    // /////////////////////////////////////////////////////////////////////////////////

    /* pour bilan session depuis la table CollaborateurVersion
     *
     * getResponsable
     *
     * @return \App\Entity\Individu
     */
    public function getResponsable(): ?Individu
    {
        foreach ($this->getCollaborateurVersion() as $item) {
            if (true === $item->getResponsable()) {
                return $item->getCollaborateur();
            }
        }

        return null;
    }

    public function getResponsables(): array
    {
        $responsables = [];
        foreach ($this->getCollaborateurVersion() as $item) {
            if (true === $item->getResponsable()) {
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
    public function getCollaborateurs(bool $moi_aussi = true, bool $seulement_eligibles = false, Individu $moi = null): array
    {
        $collaborateurs = [];
        foreach ($this->getCollaborateurVersion() as $item) {
            $collaborateur = $item->getCollaborateur();
            if (null === $collaborateur) {
                // $sj->errorMessage("Version:getCollaborateur : collaborateur null pour CollaborateurVersion ". $item->getId() );
                continue;
            }
            if (false === $moi_aussi && $collaborateur->isEqualTo($moi)) {
                continue;
            }
            if (false === $seulement_eligibles || ($collaborateur->isPermanent() && $collaborateur->isFromLaboRegional())) {
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
    public function getLabo(): Laboratoire
    {
        return $this->getPrjLLabo();
    }

    public function getExpert(): ?Individu
    {
        $expertise = $this->getOneExpertise();
        if (null === $expertise) {
            return null;
        } else {
            return $expertise->getExpert();
        }
    }

    // pour notifications ou affichage
    public function getExperts(): array
    {
        $experts = [];
        foreach ($this->getExpertise() as $item) {
            $experts[] = $item->getExpert();
        }

        return $experts;
    }

    public function hasExpert(): bool
    {
        $expertise = $this->getOneExpertise();
        if (null === $expertise) {
            return false;
        }

        $expert = $expertise->getExpert();
        if (null != $expert) {
            return true;
        } else {
            return false;
        }
    }

    // pour notifications
    public function getExpertsThematique(): ?Individu
    {
        $thematique = $this->getPrjThematique();
        if (null === $thematique) {
            return null;
        } else {
            return $thematique->getExpert();
        }
    }

    // //////////////////////////////////////////////////////////////////

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
            return substr($titre, 0, 20).'...';
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

        if (Etat::ACTIF === $etat) {
            return 'ACTIF';
        } elseif (Etat::ACTIF_R === $etat) {
            return 'A RENOUVELER';
        } elseif (Etat::NOUVELLE_VERSION_DEMANDEE === $etat) {
            return 'PRESQUE TERMINE';
        } elseif (Etat::ANNULE === $etat) {
            return 'ANNULE';
        } elseif (Etat::EDITION_DEMANDE === $etat) {
            return 'EDITION';
        } elseif (Etat::EDITION_EXPERTISE === $etat) {
            return 'VALIDATION';
        } elseif (Etat::TERMINE === $etat) {
            return 'TERMINE';
        } elseif (Etat::REFUSE === $etat) {
            return 'REFUSE';
        }

        return 'INCONNU';
    }

    //
    // Individu est-il collaborateur ? Responsable ? Expert ?
    //

    public function isCollaborateur(?Individu $individu): bool
    {
        if (null === $individu) {
            return false;
        }

        foreach ($this->getCollaborateurVersion() as $item) {
            if (null === $item->getCollaborateur())
            // $sj->errorMessage('Version:isCollaborateur collaborateur null pour CollaborateurVersion ' . $item);
            ;
            elseif ($item->getCollaborateur()->isEqualTo($individu)) {
                return true;
            }
        }

        return false;
    }

    public function isResponsable(?Individu $individu): bool
    {
        if (null === $individu) {
            return false;
        }

        foreach ($this->getCollaborateurVersion() as $item) {
            if (null === $item->getCollaborateur())
            // $sj->errorMessage('Version:isCollaborateur collaborateur null pour CollaborateurVersion ' . $item);
            ;
            elseif ($item->getCollaborateur()->isEqualTo($individu) && true === $item->getResponsable()) {
                return true;
            }
        }

        return false;
    }

    public function isExpertDe(?Individu $individu): bool
    {
        if (null === $individu) {
            return false;
        }

        foreach ($this->getExpertise() as $expertise) {
            $expert = $expertise->getExpert();

            if (null === $expert)
            // $sj->errorMessage("Version:isExpert Expert null dans l'expertise " . $item);
            ;
            elseif ($expert->isEqualTo($individu)) {
                return true;
            }
        }

        return false;
    }

    public function isExpertThematique(?Individu $individu)
    {
        if (null === $individu) {
            return false;
        }

        // //$sj->debugMessage(__METHOD__ . " thematique : " . Functions::show($thematique) );

        $thematique = $this->getPrjThematique();
        if (null != $thematique) {
            foreach ($thematique->getExpert() as $expert) {
                if ($expert->isEqualTo($individu)) {
                    return true;
                }
            }
        }

        return false;
    }

    // //////////////////////////////////

    // public function versionPrecedente()
    // {
    // // Contrairement au nom ne renvoie pas la version précédente, mais l'avant-dernière !!!
    // // La fonction versionPrecedente1() renvoie pour de vrai la version précédente
    // // TODO - Supprimer cette fonction, ou la renommer
    // $versions   =  $this->getProjet()->getVersion();
    // if (count($versions) <= 1) {
    // return null;
    // }

    // $versions   =   $versions->toArray();
    // usort(
    // $versions,
    // function (Version $b, Version $a) {
    // return strcmp($a->getIdVersion(), $b->getIdVersion());
    // }
    // );

    // //$sj->debugMessage( __METHOD__ .':'. __LINE__ . " version ID 0 1 = " . $versions[0]." " . $versions[1] );
    // return $versions[1];
    // }

    public function versionPrecedente(): ?self
    {
        $versions = $this->getProjet()->getVersion()->toArray();
        // On trie les versions dans l'ordre croissant
        usort(
            $versions,
            function (Version $a, Version $b) {
                return strcmp($a->getIdVersion(), $b->getIdVersion());
            }
        );
        $k = array_search($this->getIdVersion(), $versions);
        if (false === $k || 0 === $k) {
            return null;
        } else {
            return $versions[$k - 1];
        }
    }

    // ////////////////////////////////////////////

    /*
     * TODO - Serait mieux dans ServiceVersions
     *        Session 22A -> Renvoie la dernière année où il y a eu une version
     *                       (normalement 2021, mais peut-être une année antérieure)
     *
     *
     *************************************/
    // public function anneeRapport()
    // {
    // $anneeRapport = 0;
    // $myAnnee    =  substr($this->getIdVersion(), 0, 2);
    // foreach ($this->getProjet()->getVersion() as $version) {
    // $annee = substr($version->getIdVersion(), 0, 2);
    // if ($annee < $myAnnee) {
    // $anneeRapport = max($annee, $anneeRapport);
    // }
    // }

    // if ($anneeRapport < 10 && $anneeRapport > 0) {
    // return '200' . $anneeRapport ;
    // } elseif ($anneeRapport >= 10) {
    // return '20' . $anneeRapport ;
    // } else {
    // return '0';
    // }
    // }

    // /////////////////////////////////////////////

    /*********
    * Renvoie l'expertise 0 si elle existe, null sinon
    ***************/
    public function getOneExpertise()
    {
        $expertises = $this->getExpertise()->toArray();
        if (null != $expertises) {
            // $expertise  =   current( $expertises );
            $expertise = $expertises[0];

            // Functions::debugMessage(__METHOD__ . " expertise = " . Functions::show( $expertise )
            //    . " expertises = " . Functions::show( $expertises ));
            return $expertise;
        } else {
            // Functions::noticeMessage(__METHOD__ . " version " . $this . " n'a pas d'expertise !");
            return null;
        }
    }

    // //////////////////////////////////////////////////

    // public function isProjetTest()
    // {
    // $projet =   $this->getProjet();
    // if ($projet === null) {
    // //$sj->errorMessage(__METHOD__ . ":" . __LINE__ . " version " . $this . " n'est pas associée à un projet !");
    // return false;
    // } else {
    // return $projet->isProjetTest();
    // }
    // }

    // ///////////////////////////////////////////////////

    public function isEdited()
    {
        $etat = $this->getEtatVersion();

        return Etat::EDITION_DEMANDE === $etat || Etat::EDITION_TEST === $etat;
    }

    // ////////////////////////////////////////////

    public function getAcroEtablissement()
    {
        $responsable = $this->getResponsable();
        if (null === $responsable) {
            return '';
        }

        $etablissement = $responsable->getEtab();
        if (null === $etablissement) {
            return '';
        }

        return $etablissement->__toString();
    }

    // ////////////////////////////////////////////

    public function getAcroThematique()
    {
        $thematique = $this->getPrjThematique();
        if (null === $thematique) {
            return 'sans thématique';
        } else {
            return $thematique->__toString();
        }
    }

    // ///////////////////////////////////////////////////
    public function getEtat(): ?int
    {
        return $this->getEtatVersion();
    }

    public function getId(): ?string
    {
        return $this->getIdVersion();
    }

    public function isPrjFicheVal(): ?bool
    {
        return $this->prjFicheVal;
    }

    public function isCGU(): ?bool
    {
        return $this->CGU;
    }

    public function getVersionDerniere(): ?Projet
    {
        return $this->versionDerniere;
    }

    public function setVersionDerniere(?Projet $versionDerniere): static
    {
        // unset the owning side of the relation if necessary
        if (null === $versionDerniere && null !== $this->versionDerniere) {
            $this->versionDerniere->setVersionDerniere(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $versionDerniere && $versionDerniere->getVersionDerniere() !== $this) {
            $versionDerniere->setVersionDerniere($this);
        }

        $this->versionDerniere = $versionDerniere;

        return $this;
    }

    public function getVersionActive(): ?Projet
    {
        return $this->versionActive;
    }

    public function setVersionActive(?Projet $versionActive): static
    {
        // unset the owning side of the relation if necessary
        if (null === $versionActive && null !== $this->versionActive) {
            $this->versionActive->setVersionActive(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $versionActive && $versionActive->getVersionActive() !== $this) {
            $versionActive->setVersionActive($this);
        }

        $this->versionActive = $versionActive;

        return $this;
    }

    /**
     * @return Collection<int, Dac>
     */
    public function getDac(): Collection
    {
        return $this->dac;
    }

    public function addDac(Dac $dac): static
    {
        if (!$this->dac->contains($dac)) {
            $this->dac->add($dac);
            $dac->setVersion($this);
        }

        return $this;
    }

    public function removeDac(Dac $dac): static
    {
        if ($this->dac->removeElement($dac)) {
            // set the owning side to null (unless already changed)
            if ($dac->getVersion() === $this) {
                $dac->setVersion(null);
            }
        }

        return $this;
    }
}
