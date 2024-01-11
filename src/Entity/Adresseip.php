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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Publication
 *
 * @ORM\Table(name="adresseip",
 *            uniqueConstraints={@ORM\UniqueConstraint(name="adresseip", columns={"adresse","id_labo"})})
 * @ORM\Entity(repositoryClass="App\Repository\AdresseipRepository")
 */
class Adresseip
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="adresse", type="text", length=45, nullable=false)
     */
    private $adresse;

    /**
     * @var \App\Entity\Laboratoire
     * ORM\Column(name="id_labo", type="integer", length=11, nullable=false)
     * @ORM\ManyToOne(targetEntity="App\Entity\Laboratoire", inversedBy="adresseip")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_labo", referencedColumnName="id_labo")
     * })
     */

    private $labo;



    public function __toString(): string
    {
        return $this->getAdresse();
    }




    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set adresse
     *
     * @param string $adresse
     * 
     * @return string
     */
    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    /**
     * Get adresse
     * 
     * @Assert\Cidr(message="Valeur non conforme - Essayer 1.2.3.4/32", version="4", netmaskMin=16, netmaskMax=32,netmaskRangeViolationMessage="Le masque doit être un entier compris entre {{ min }} et {{ max }}")
     * @Assert\NotBlank(message="remerde")
     * 
     * @return string
     */
    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    /**
     * Set labo
     *
     * @param \App\Entity\Laboratoire $labo
     *
     * @return adresseip
     */
    public function setLabo(?\App\Entity\Laboratoire $labo): self
    {
        $this->labo = $labo;

        return $this;
    }

    /**
     * Get labo
     *
     * @return \App\Entity\Laboratoire
     */
    public function getLabo(): \App\Entity\Laboratoire
    {
        return $this->labo;
    }
}
