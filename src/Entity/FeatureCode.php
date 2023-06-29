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
use App\Repository\FeatureCodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class FeatureCode
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-27)
 * @since 0.1.0 (2023-06-27) First version.
 */
#[ORM\Entity(repositoryClass: FeatureCodeRepository::class)]
#[UniqueEntity(
    fields: ['class', 'code'],
    message: 'The code combination is already used with this class.',
    errorPath: 'class'
)]
#[ORM\UniqueConstraint(columns: ['class_id', 'code'])]
#[ORM\Index(columns: ['class_id', 'code'])]
#[ORM\HasLifecycleCallbacks]
class FeatureCode
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'featureCodes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FeatureClass $class = null;

    #[ORM\Column(length: 10)]
    private ?string $code = null;

    /** @var Collection<int, Location> $locations */
    #[ORM\OneToMany(mappedBy: 'featureCode', targetEntity: Location::class, orphanRemoval: true)]
    private Collection $locations;

    /**
     *
     */
    public function __construct()
    {
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
     * @return FeatureClass|null
     */
    public function getClass(): ?FeatureClass
    {
        return $this->class;
    }

    /**
     * @param FeatureClass|null $class
     * @return $this
     */
    public function setClass(?FeatureClass $class): static
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode(string $code): static
    {
        $this->code = $code;

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
            $location->setFeatureCode($this);
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
            if ($location->getFeatureCode() === $this) {
                $location->setFeatureCode(null);
            }
        }

        return $this;
    }
}
