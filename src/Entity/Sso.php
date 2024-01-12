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

use Doctrine\ORM\Mapping as ORM;

/**
 * Sso.
 */
#[ORM\Table(name: 'sso')]
#[ORM\Index(name: 'id_individu', columns: ['id_individu'])]
#[ORM\Entity]
class Sso
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'eppn', type: 'string', length: 200)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $eppn;

    /**
     * @var Individu
     */
    #[ORM\JoinColumn(name: 'id_individu', referencedColumnName: 'id_individu')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Individu', inversedBy: 'sso')]
    private $individu;

    /**
     * Get eppn.
     */
    public function getEppn(): ?string
    {
        return $this->eppn;
    }

    public function getId(): ?string
    {
        return $this->getEppn();
    }

    /**
     * Set eppn.
     *
     * @param string
     */
    public function setEppn(string $eppn): self
    {
        $this->eppn = $eppn;

        return $this;
    }

    /**
     * Set individu.
     *
     * @return Sso
     */
    public function setIndividu(Individu $idIndividu = null)
    {
        $this->individu = $idIndividu;

        return $this;
    }

    /**
     * Get individu.
     */
    public function getIndividu(): ?Individu
    {
        return $this->individu;
    }

    public function __toString(): string
    {
        return $this->getEppn();
    }
}
