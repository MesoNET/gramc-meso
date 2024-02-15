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

use App\GramcServices\Etat;
use App\Interfaces\Demande;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/*
 * TODO - Utiliser l'héritage pour faire hériter Version et Rallonge d'une même classe
 *        cf. https://www.doctrine-project.org/projects/doctrine-orm/en/2.14/reference/inheritance-mapping.html
 *        Pas le temps / pas le recul alors on travaille salement
 *        Emmanuel, 27/3/23
 *
 ************************************************************/
/**
 * Rallonge.
 */
#[ORM\Table(name: 'rallonge')]
#[ORM\Index(name: 'id_version', columns: ['id_version'])]
#[ORM\Index(name: 'num_rallonge', columns: ['id_rallonge'])]
#[ORM\Index(name: 'etat_rallonge', columns: ['etat_rallonge'])]
#[ORM\Entity(repositoryClass: 'App\Repository\RallongeRepository')]
#[Assert\Expression('this.getNbHeuresAtt() > 0  or  this.getValidation() != 1', message: 'Si vous ne voulez pas attribuer des heures pour cette demande, choisissez ')]
#[Assert\Expression('this.getNbHeuresAtt() == 0  or  this.getValidation() !=  0', message: 'Si vous voulez attribuer des heures pour cette demande, choisissez ')]
#[ORM\HasLifecycleCallbacks]
class Rallonge implements Demande
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'etat_rallonge', type: 'integer', nullable: false)]
    private $etatRallonge;

    /**
     * @var string
     */
    #[ORM\Column(name: 'prj_justif_rallonge', type: 'text', length: 65535, nullable: true)]
    #[Assert\NotBlank(message: "Vous n'avez pas rempli la justification scientifique")]
    private $prjJustifRallonge;

    #[ORM\Column(name: 'id_rallonge', type: 'string', length: 15)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $idRallonge;

    /**
     * @var Version
     */
    #[ORM\JoinColumn(name: 'id_version', referencedColumnName: 'id_version')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Version', inversedBy: 'rallonge')]
    private $version;

    /**
     * @var string
     */
    #[ORM\Column(name: 'commentaire_interne', type: 'text', length: 65535, nullable: true)]
    #[Assert\NotBlank(message: "Vous n'avez pas rempli le commentaire interne", groups: ['expertise', 'president'])]
    private $commentaireInterne;

    /**
     * @var string
     */
    #[ORM\Column(name: 'commentaire_externe', type: 'text', length: 65535, nullable: true)]
    #[Assert\NotBlank(message: "Vous n'avez pas rempli le commentaire pour le responsable", groups: ['president'])]
    private $commentaireExterne;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'validation', type: 'boolean', nullable: false)]
    private $validation = true;

    /**
     * @var Individu
     */
    #[ORM\JoinColumn(name: 'id_expert', referencedColumnName: 'id_individu')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Individu')]
    private $expert;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Dar', mappedBy: 'rallonge', cascade: ['persist'])]
    private $dar;

    // //////////////////////////////////////////////////////

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->dar = new ArrayCollection();
        $this->expertise = new ArrayCollection();
    }

    // ///////////////////////////////////////////////////////////////////////////

    public function getId(): ?int
    {
        return $this->getIdRallonge();
    }

    public function __toString(): string
    {
        return $this->getIdRallonge();
    }

    // //////////////////////////////////////////////////////////////////////////

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

    /**
     * Set etatRallonge.
     */
    public function setEtatRallonge(int $etatRallonge): self
    {
        $this->etatRallonge = $etatRallonge;

        return $this;
    }

    public function setEtat(int $etatRallonge): self
    {
        return $this->setEtatRallonge($etatRallonge);
    }

    /**
     * Get etatRallonge.
     */
    public function getEtatRallonge(): ?int
    {
        return $this->etatRallonge;
    }

    public function getEtat(): ?int
    {
        return $this->getEtatRallonge();
    }

    /**
     * Set prjJustifRallonge.
     */
    public function setPrjJustifRallonge(?string $prjJustifRallonge): self
    {
        $this->prjJustifRallonge = $prjJustifRallonge;

        return $this;
    }

    /**
     * Get prjJustifRallonge.
     */
    public function getPrjJustifRallonge(): ?string
    {
        return $this->prjJustifRallonge;
    }

    /**
     * Set idRallonge.
     */
    public function setIdRallonge(string $idRallonge): self
    {
        $this->idRallonge = $idRallonge;

        return $this;
    }

    /**
     * Get idRallonge.
     */
    public function getIdRallonge(): ?string
    {
        return $this->idRallonge;
    }

    /**
     * Set version.
     */
    public function setVersion(?Version $version = null): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     */
    public function getVersion(): ?Version
    {
        return $this->version;
    }

    /**
     * Add dar.
     *
     * @return Version
     */
    public function addDar(Dar $dar): self
    {
        if (!$this->dar->contains($dar)) {
            $this->dar[] = $dar;
        }

        return $this;
    }

    /**
     * Remove dar.
     */
    public function removeDar(Dar $dar): self
    {
        $this->dar->removeElement($dar);

        return $this;
    }

    /**
     * Get dar.
     *
     * @return Collection
     */
    public function getDar()
    {
        return $this->dar;
    }

    /**
     * Set commentaireInterne.
     */
    public function setCommentaireInterne(?string $commentaireInterne): self
    {
        $this->commentaireInterne = $commentaireInterne;

        return $this;
    }

    /**
     * Get commentaireInterne.
     */
    public function getCommentaireInterne(): ?string
    {
        return $this->commentaireInterne;
    }

    /**
     * Set commentaireExterne.
     */
    public function setCommentaireExterne(?string $commentaireExterne): self
    {
        $this->commentaireExterne = $commentaireExterne;

        return $this;
    }

    /**
     * Get commentaireExterne.
     */
    public function getCommentaireExterne(): ?string
    {
        return $this->commentaireExterne;
    }

    /**
     * Set validation.
     */
    public function setValidation(bool $validation): self
    {
        $this->validation = $validation;

        return $this;
    }

    /**
     * Get validation.
     */
    public function getValidation(): bool
    {
        return $this->validation;
    }

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\Expertise', mappedBy: 'rallonge', cascade: ['persist'])]
    private $expertise;

    /**
     * Set expert.
     */
    public function setExpert(?Individu $expert = null): self
    {
        $this->expert = $expert;

        return $this;
    }

    /**
     * Get expert.
     */
    public function getExpert(): ?Individu
    {
        return $this->expert;
    }

    // cf. https://stackoverflow.com/questions/39272733/boolean-values-and-choice-symfony-type
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->validation = (bool) $this->validation; // Force using boolean value of $this->active
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->validation = (bool) $this->validation;
    }

    /***************************************************
     * Fonctions utiles pour la class Workflow
     * Autre nom pour getEtatRallonge/setEtatRallonge !
     ***************************************************/
    public function getObjectState(): ?int
    {
        return $this->getEtatRallonge();
    }

    public function setObjectState(int $state): self
    {
        $this->setEtatRallonge($state);

        return $this;
    }

    public function getResponsables(): array
    {
        $version = $this->getVersion();
        if (null != $version) {
            return $version->getResponsables();
        } else {
            return [];
        }
    }

    /***************************************************
     * Fonctions utiles pour les notifications
     ***************************************************/
    public function getOneExpert(): ?Individu
    {
        return $this->getExpert();
    }

    public function getExperts(): array
    {
        return [$this->getExpert()];
    }

    public function getExpertsThematique(): array
    {
        $version = $this->getVersion();
        if (null === $version) {
            return [];
        }

        $thematique = $version->getThematique();
        if (null === $thematique) {
            return [];
        } else {
            return $thematique->getExpert();
        }
    }

    public function getLibelleEtatRallonge(): ?string
    {
        return Etat::getLibelle($this->getEtatRallonge());
    }

    // //////////////////////////////////////////////////////////////////////
    // TODO - Mettre cette fonction dans ServiceRallonge

    public function isExpertDe(Individu $individu): bool
    {
        if (null === $individu) {
            return false;
        }

        $expert = $this->getExpert();

        if (null === $expert) {
            return false;
        } elseif ($expert->isEqualTo($individu)) {
            return true;
        } else {
            return false;
        }
    }

    public function isValidation(): ?bool
    {
        return $this->validation;
    }
}
