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

/**
 * Pubkey
 *
 * Table(name="pubkey", indexes={@ORM\Index(name="nom", columns={"nom","id_individu"})})
 * @ORM\Table(name="pubkey", indexes={@ORM\Index(columns={"nom","id_individu"})})
 * ORM\Table(name="pubkey")
 * @ORM\Entity(repositoryClass="App\Repository\PubkeyRepository")
 */
class Pubkey
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
     * @ORM\Column(name="nom", type="string", length=20)
     *
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="key", type="string", length=200)
     *
     */
    private $key;

    /**
     * @var \App\Entity\Individu
     *
     * ORM\ManyToOne(targetEntity="App\Entity\Individu")
     * @ORM\ManyToOne(targetEntity=Individu::class, inversedBy="id")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_individu", referencedColumnName="id_individu")
     * })
     */
    private Individu $individu;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Get nom
     *
     * @return string
     */
    public function getNom(): string
    {
        return $this->nom;
    }

    /**
     * Set nom
     *
     * @param string
     * @return Pubkey
     */
    public function setNom(string $nom)
    {
        $this->nom = $nom;
        return $this;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set key
     *
     * @param string
     * @return Pubkey
     */
    public function setKey(string $key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Set individu
     *
     * @param \App\Entity\Individu $idIndividu
     *
     * @return Sso
     */
    public function setIndividu(\App\Entity\Individu $individu = null)
    {
        $this->individu = $individu;

        return $this;
    }

    /**
     * Get individu
     *
     * @return \App\Entity\Individu
     */
    public function getIndividu()
    {
        return $this->individu;
    }

    public function __toString()
    {
        return $this->getnom() . '[' . $this->getIndividu() . ']';
    }
}
