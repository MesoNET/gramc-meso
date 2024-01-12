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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Publication.
 */
#[ORM\Table(name: 'publication')]
#[ORM\Entity(repositoryClass: 'App\Repository\PublicationRepository')]
class Publication
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'refbib', type: 'text', length: 65535, nullable: false)]
    private $refbib;

    /**
     * @var string
     */
    #[ORM\Column(name: 'doi', type: 'string', length: 100, nullable: true)]
    private $doi;

    /**
     * @var string
     */
    #[ORM\Column(name: 'open_url', type: 'string', length: 300, nullable: true)]
    private $openUrl;

    /**
     * @var int
     */
    #[ORM\Column(name: 'annee', type: 'integer', nullable: false)]
    private $annee;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id_publi', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $idPubli;

    /**
     * @var Collection
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Projet', mappedBy: 'publi')]
    private $projet;

    // //////////////////////////////////////////////////////////////////////

    public function __toString(): string
    {
        return $this->getRefbib();
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->getIdPubli();
    }

    // //////////////////////////////////////////////////////////////////////
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->projet = new ArrayCollection();
    }

    /**
     * Set refbib.
     */
    public function setRefbib(string $refbib): self
    {
        $this->refbib = $refbib;

        return $this;
    }

    /**
     * Get refbib.
     */
    public function getRefbib(): ?string
    {
        return $this->refbib;
    }

    /**
     * Set doi.
     */
    public function setDoi(?string $doi): self
    {
        $this->doi = $doi;

        return $this;
    }

    /**
     * Get doi.
     */
    public function getDoi(): ?string
    {
        return $this->doi;
    }

    /**
     * Set openUrl.
     */
    public function setOpenUrl(?string $openUrl): self
    {
        $this->openUrl = $openUrl;

        return $this;
    }

    /**
     * Get openUrl.
     */
    public function getOpenUrl(): ?string
    {
        return $this->openUrl;
    }

    /**
     * Set annee.
     */
    public function setAnnee(int $annee): self
    {
        $this->annee = $annee;

        return $this;
    }

    /**
     * Get annee.
     */
    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    /**
     * Get idPubli.
     *
     * NOTE - Peut retourner null avant insertion dans la B.D. - cf. PublicationController::
     * gererAction
     */
    public function getIdPubli(): ?int
    {
        return $this->idPubli;
    }

    /**
     * Add projet.
     */
    public function addProjet(Projet $projet): self
    {
        if (!$this->projet->contains($projet)) {
            $this->projet[] = $projet;
        }

        return $this;
    }

    /**
     * Remove projet.
     */
    public function removeProjet(Projet $projet): self
    {
        $this->projet->removeElement($projet);

        return $this;
    }

    /**
     * Get projet.
     *
     * @return Collection
     */
    public function getProjet()
    {
        return $this->projet;
    }

    // /////////////////////////////////////////////////////////

    /**
     * Get doi, cleaned.
     *************************************/
    public function getDoiCleaned(): ?string
    {
        $doi = $this->getDoi();
        $prf = 'https://doi.org/';

        if (!empty($doi)) {
            if (str_starts_with($doi, $prf)) {
                $doi = substr($doi, strlen($prf));
            }
        }

        return $doi;
    }
}
