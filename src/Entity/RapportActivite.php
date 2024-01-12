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
 * RapportActivite.
 */
#[ORM\Table(name: 'rapportActivite')]
#[ORM\Index(name: 'id_projet', columns: ['id_projet'])]
#[ORM\UniqueConstraint(name: 'id_projet_2', columns: ['id_projet', 'annee'])]
#[ORM\Entity]
class RapportActivite
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'annee', type: 'integer', nullable: false)]
    private $annee;

    /**
     * @var string
     */
    #[ORM\Column(name: 'nom_fichier', type: 'string', length: 100, nullable: true)]
    private $nomFichier;

    /**
     * @var int
     */
    #[ORM\Column(name: 'taille', type: 'integer', nullable: false)]
    private $taille;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var Projet
     */
    #[ORM\JoinColumn(name: 'id_projet', referencedColumnName: 'id_projet')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Projet', inversedBy: 'rapportActivite')]
    private $projet;

    // /////////////////////////////////////////////////////////////////////////////

    public function __construct($projet, $annee)
    {
        $this->setProjet($projet);
        $this->setAnnee($annee);
    }

    /**
     * Set annee.
     *
     * @param int $annee
     *
     * @return RapportActivite
     */
    public function setAnnee($annee)
    {
        $this->annee = $annee;

        return $this;
    }

    /**
     * Get annee.
     *
     * @return int
     */
    public function getAnnee()
    {
        return $this->annee;
    }

    /**
     * Set nomFichier.
     *
     * @param string $nomFichier
     *
     * @return RapportActivite
     */
    public function setNomFichier($nomFichier)
    {
        $this->nomFichier = $nomFichier;

        return $this;
    }

    /**
     * Get nomFichier.
     *
     * @return string
     */
    public function getNomFichier()
    {
        return $this->nomFichier;
    }

    /**
     * Set taille.
     *
     * @param int $taille
     *
     * @return RapportActivite
     */
    public function setTaille($taille)
    {
        $this->taille = $taille;

        return $this;
    }

    /**
     * Get taille.
     *
     * @return int
     */
    public function getTaille()
    {
        return $this->taille;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set projet.
     *
     * @return RapportActivite
     */
    public function setProjet(Projet $projet = null)
    {
        $this->projet = $projet;

        return $this;
    }

    /**
     * Get projet.
     *
     * @return Projet
     */
    public function getProjet()
    {
        return $this->projet;
    }
}
