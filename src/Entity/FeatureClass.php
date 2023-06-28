<?php

/*
 * This file is part of the twelvepics-com/php-location-api project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\TimestampsTrait;
use App\Repository\FeatureClassRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class FeatureClass
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-27)
 * @since 0.1.0 (2023-06-27) First version.
 */
#[ORM\Entity(repositoryClass: FeatureClassRepository::class)]
#[UniqueEntity('class')]
#[ORM\UniqueConstraint(columns: ['class'])]
#[ORM\Index(columns: ['class'])]
#[ORM\HasLifecycleCallbacks]
class FeatureClass
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 1, unique: true)]
    private ?string $class = null;

    /** @var Collection<int, FeatureCode> $featureCodes */
    #[ORM\OneToMany(mappedBy: 'class', targetEntity: FeatureCode::class, orphanRemoval: true)]
    private Collection $featureCodes;

    /** @var Collection<int, Location> $locations */
    #[ORM\OneToMany(mappedBy: 'featureClass', targetEntity: Location::class, orphanRemoval: true)]
    private Collection $locations;

    /**
     *
     */
    public function __construct()
    {
        $this->featureCodes = new ArrayCollection();
        $this->locations = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * @param string $class
     * @return $this
     */
    public function setClass(string $class): static
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return Collection<int, FeatureCode>
     */
    public function getFeatureCodes(): Collection
    {
        return $this->featureCodes;
    }

    /**
     * @param FeatureCode $featureCode
     * @return $this
     */
    public function addFeatureCode(FeatureCode $featureCode): static
    {
        if (!$this->featureCodes->contains($featureCode)) {
            $this->featureCodes->add($featureCode);
            $featureCode->setClass($this);
        }

        return $this;
    }

    /**
     * @param FeatureCode $featureCode
     * @return $this
     */
    public function removeFeatureCode(FeatureCode $featureCode): static
    {
        if ($this->featureCodes->removeElement($featureCode)) {
            /* Set the owning side to null (unless already changed). */
            if ($featureCode->getClass() === $this) {
                $featureCode->setClass(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Location>
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    /**
     * @param Location $location
     * @return $this
     */
    public function addLocation(Location $location): static
    {
        if (!$this->locations->contains($location)) {
            $this->locations->add($location);
            $location->setFeatureClass($this);
        }

        return $this;
    }

    /**
     * @param Location $location
     * @return $this
     */
    public function removeLocation(Location $location): static
    {
        if ($this->locations->removeElement($location)) {
            /* Set the owning side to null (unless already changed). */
            if ($location->getFeatureClass() === $this) {
                $location->setFeatureClass(null);
            }
        }

        return $this;
    }
}
