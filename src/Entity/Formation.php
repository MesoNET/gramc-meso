<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Formation.
 *
 * Ecrans Formation, permet de déclarer le nombre de personnes intéressées par les formations à venir
 */
#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ApiResource(
    operations: []
)]
class Formation
{
    public function __construct()
    {
        $this->startDate = new \DateTime();
        $this->endDate = new \DateTime();
        $this->formationVersion = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: '\App\Entity\FormationVersion', mappedBy: 'formation', cascade: ['persist'])]
    private $formationVersion;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $numeroForm;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    private $acroForm;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private $nomForm;

    /**
     * @var \DateTime
     *                Date à partir de laquelle on propose la formation
     */
    #[ORM\Column(name: 'start_date', type: 'datetime', nullable: false)]
    private $startDate;

    /**
     * @var \DateTime
     *                Date à partir de laquelle on ne propose PLUS la formation
     */
    #[ORM\Column(name: 'end_date', type: 'datetime', nullable: false)]
    private $endDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Add formationVersion.
     */
    public function addFormationVersion(FormationVersion $formationVersion): self
    {
        if (!$this->formationVersion->contains($formationVersion)) {
            $this->formationVersion[] = $formationVersion;
        }

        return $this;
    }

    /**
     * Remove formationVersion.
     *
     * @param FormationVersion $formationVersion
     */
    public function removeFormationVersion(Rallonge $formationVersion): self
    {
        $this->rallonge->removeElement($formationVersion);

        return $this;
    }

    /**
     * Get formationVersion.
     *
     * @return Collection
     */
    public function getFormationVersion()
    {
        return $this->formationVersion;
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

    public function setStartDate(\DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }
}
