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
 * Journal.
 */
#[ORM\Table(name: 'journal')]
#[ORM\Entity(repositoryClass: 'App\Repository\JournalRepository')]
class Journal
{
    /**
     * @var int
     *
     * @    ORM\Column(name="id_individu", type="integer", nullable=true)
     */
    //    private $idIndividu;
    /**
     * @var Individu
     */
    #[ORM\JoinColumn(name: 'individu', referencedColumnName: 'id_individu', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Individu', inversedBy: 'journal', cascade: ['persist'])]
    private $individu;

    /**
     * @var string
     */
    #[ORM\Column(name: 'gramc_sess_id', type: 'string', length: 40, nullable: true)]
    private $gramcSessId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'type', type: 'string', length: 15, nullable: false)]
    private $type = 'RIEN';

    /**
     * @var string
     */
    #[ORM\Column(name: 'message', type: 'string', length: 300, nullable: false)]
    private $message = '';

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'stamp', type: 'datetime', nullable: false)]
    private $stamp = 'CURRENT_TIMESTAMP';

    /**
     * @var string
     */
    #[ORM\Column(name: 'ip', type: 'string', length: 40, nullable: false)]
    private $ip;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'niveau', type: 'integer')]
    private $niveau;

    // ///////////////////////////////////////////////////

    /**
     * Set idIndividu.
     */
    // public function setIdIndividu(?int $idIndividu): self
    // {
    // $this->idIndividu = $idIndividu;

    // return $this;
    // }

    /**
     * Get idIndividu.
     *
     * @return int
     */
    /*    public function getIdIndividu(): ?int
        {
            return $this->idIndividu;
        }*/

    /**
     * Set gramcSessId.
     */
    public function setGramcSessId(?string $gramcSessId): self
    {
        $this->gramcSessId = $gramcSessId;

        return $this;
    }

    /**
     * Get gramcSessId.
     */
    public function getGramcSessId(): ?string
    {
        return $this->gramcSessId;
    }

    /**
     * Set type.
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set message.
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set stamp.
     */
    public function setStamp(\DateTime $stamp): self
    {
        $this->stamp = $stamp;

        return $this;
    }

    /**
     * Get stamp.
     */
    public function getStamp(): \DateTime
    {
        return $this->stamp;
    }

    /**
     * Set ip.
     */
    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip.
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set individu.
     */
    public function setIndividu(?Individu $individu = null): self
    {
        $this->individu = $individu;

        return $this;
    }

    /**
     * Get individu.
     */
    public function getIndividu(): ?Individu
    {
        return $this->individu;
    }

    /**
     * Set niveau.
     */
    public function setNiveau(int $niveau): self
    {
        $this->niveau = $niveau;

        return $this;
    }

    /**
     * Get niveau.
     */
    public function getNiveau(): ?int
    {
        return $this->niveau;
    }

    // ///////////////////////////////////////////////////////////////

    public const EMERGENCY = 10;
    public const ALERT = 20;
    public const CRITICAL = 30;
    public const ERROR = 40;
    public const WARNING = 50;
    public const NOTICE = 60;
    public const INFO = 70;
    public const DEBUG = 80;

    public const   LIBELLE =
            [
                self::EMERGENCY => 'EMERGENCY',
                self::ALERT => 'ALERT',
                self::CRITICAL => 'CRITICAL',
                self::ERROR => 'ERROR',
                self::WARNING => 'WARNING',
                self::NOTICE => 'NOTICE',
                self::INFO => 'INFO',
                self::DEBUG => 'DEBUG',
            ];
}
