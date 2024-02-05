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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Expertise.
 *
 * Argumentaire des valiteurs pour la validation d'un projet
 * NOTE - Cette classe peut servir à valider une VERSION de projet aussi bien qu'une RALLONGE de version (=extension)
 *        Le champ $version ou $rallonge sera différent de null
 *        Le champ $expert renvoie sur le valideur (de class $individu, peut être nul si personne n'a encore modifié l'expertise)
 */
#[ORM\Table(name: 'expertise')]
#[ORM\Index(name: 'version_expertise_fk', columns: ['id_version'])]
#[ORM\Index(name: 'expert_expertise_fk', columns: ['id_expert'])]
#[ORM\Index(name: 'id_version', columns: ['id_version'])]
#[ORM\Index(name: 'id_expert', columns: ['id_expert'])]
#[ORM\UniqueConstraint(name: 'id_version_2', columns: ['id_version', 'id_expert'])]
#[ORM\Entity(repositoryClass: 'App\Repository\ExpertiseRepository')]
#[ApiResource(
    operations: []
]
class Expertise
{
    /**
     * @var bool
     *
     * true = L'expert a répondu positivement et a attribué des heures (éventuellement 0 heure si le projet est validé mais la machine surchargée)
     * false= L'expert a répondu négativement (et l'attribution est obligatoirement 0)
     */
    #[ORM\Column(name: 'validation', type: 'integer', nullable: false)]
    private $validation = 1;

    /**
     * @var string
     *
     * Expertise qui sera connue du comité d'attribution uniquement
     */
    #[ORM\Column(name: 'commentaire_interne', type: 'text', length: 65535, nullable: true)]
    #[Assert\NotBlank(message: "Vous n'avez pas rempli le commentaire pour le comité")]
    private $commentaireInterne = '';

    /**
     * @var string
     *
     * Expertise qui sera connue du porteur de projet
     */
    #[ORM\Column(name: 'commentaire_externe', type: 'text', length: 65535, nullable: true)]
    #[Assert\NotBlank(message: "Vous n'avez pas rempli le commentaire pour le responsable")]
    private $commentaireExterne = '';

    /**
     * @var bool
     *
     * false = Nous sommes en phase d'édition, l'expertise n'a pas encore été envoyée
     * true  = Expertise envoyée, pas de modification possible
     */
    #[ORM\Column(name: 'definitif', type: 'boolean', nullable: false)]
    private $definitif = false;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var Version
     */
    #[ORM\JoinColumn(name: 'id_version', referencedColumnName: 'id_version')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Version', inversedBy: 'expertise')]
    private $version;

    /**
     * @var Rallonge
     */
    #[ORM\JoinColumn(name: 'id_rallonge', referencedColumnName: 'id_rallonge')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Rallonge', inversedBy: 'expertise')]
    private $rallonge;

    /**
     * @var Individu
     */
    #[ORM\JoinColumn(name: 'id_expert', referencedColumnName: 'id_individu')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Individu', inversedBy: 'expertise')]
    private $expert;

    public function __toString()
    {
        return 'Expertise '.$this->getId()." par l'expert ".$this->getExpert();
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
     * Set definitif.
     */
    public function setDefinitif(bool $definitif): self
    {
        $this->definitif = $definitif;

        return $this;
    }

    /**
     * Get definitif.
     */
    public function getDefinitif(): bool
    {
        return $this->definitif;
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set version.
     */
    public function setVersion(Version $idVersion = null): self
    {
        $this->version = $idVersion;

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
     * Set rallonge.
     */
    public function setRallonge(Rallonge $idRallonge = null): self
    {
        $this->rallonge = $idRallonge;

        return $this;
    }

    /**
     * Get rallonge.
     */
    public function getRallonge(): ?Rallonge
    {
        return $this->rallonge;
    }

    /**
     * Set expert.
     */
    public function setExpert(Individu $expert = null): self
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

    public function isDefinitif(): ?bool
    {
        return $this->definitif;
    }
}
