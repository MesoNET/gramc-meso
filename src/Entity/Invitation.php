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
use Doctrine\ORM\Mapping as ORM;

/**
 * Invitation.
 */
#[ORM\Table(name: 'invitation')]
#[ORM\UniqueConstraint(name: 'clef', columns: ['clef'])]
#[ORM\UniqueConstraint(name: 'invit', columns: ['id_inviting', 'id_invited'])]
#[ORM\Entity]
#[ApiResource(
    operations: []
)]
class Invitation
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id_invitation', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $idInvitation;

    /**
     * @var string
     */
    #[ORM\Column(name: 'clef', type: 'string', length: 50, nullable: false)]
    private $clef;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'creation_stamp', type: 'datetime', nullable: false)]
    private $creationStamp;

    /**
     * @var Individu
     */
    #[ORM\JoinColumn(name: 'id_inviting', referencedColumnName: 'id_individu')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Individu')]
    private $inviting;

    /**
     * @var Individu
     */
    #[ORM\JoinColumn(name: 'id_invited', referencedColumnName: 'id_individu')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Individu')]
    private $invited;

    /**
     * Get idInvitation.
     */
    public function getIdInvitation(): ?int
    {
        return $this->idInvitation;
    }

    /**
     * Set inviting.
     */
    public function setInviting(?Individu $inviting): self
    {
        $this->inviting = $inviting;

        return $this;
    }

    /**
     * Get inviting.
     */
    public function getInviting(): ?Individu
    {
        return $this->inviting;
    }

    /**
     * Set invited.
     */
    public function setInvited(?Individu $invited): self
    {
        $this->invited = $invited;

        return $this;
    }

    /**
     * Get invited.
     */
    public function getInvited(): ?Individu
    {
        return $this->invited;
    }

    /**
     * Set clef.
     */
    public function setClef(string $clef): self
    {
        $this->clef = $clef;

        return $this;
    }

    /**
     * Get clef.
     */
    public function getClef(): ?string
    {
        return $this->clef;
    }

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
}
