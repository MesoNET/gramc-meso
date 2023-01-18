<?php

namespace App\Entity;

use App\GramcServices\GramcDate;
use App\Repository\FormationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FormationRepository::class)
 */
class Formation
{
    public function __construct()
    {
        $this->startDate = new \DateTime();
        $this->endDate = new \DateTime();
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $numeroForm;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $acroForm;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $nomForm;

    /**
     * @var \DateTime
     * Date à partir de laquelle on propose la formation
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    private $startDate;

    /**
     * @var \DateTime
     * Date à partir de laquelle on ne propose PLUS la formation
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=false)
     */
    private $endDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroForm(): ?int
    {
        return $this->numeroForm;
    }

    public function setNumeroForm(?int $numeroForm): self
    {
        $this->numeroForm = $numeroForm;

        return $this;
    }

    public function getAcroForm(): ?string
    {
        return $this->acroForm;
    }

    public function setAcroForm(?string $acroForm): self
    {
        $this->acroForm = $acroForm;

        return $this;
    }

    public function getNomForm(): ?string
    {
        return $this->nomForm;
    }

    public function setNomForm(?string $nomForm): self
    {
        $this->nomForm = $nomForm;

        return $this;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\Datetime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\Datetime $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }
}
