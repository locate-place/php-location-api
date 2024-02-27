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
use App\Repository\SourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Source
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-26)
 * @since 0.1.0 (2024-02-26) First version.
 */
#[ORM\Entity(repositoryClass: SourceRepository::class)]
#[ORM\Index(columns: ['source_type'])]
#[ORM\Index(columns: ['source_link'])]
#[ORM\HasLifecycleCallbacks]
class Source
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 63)]
    private ?string $sourceType = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $sourceLink = null;

    /** @var Collection<int, Property> $properties */
    #[ORM\OneToMany(mappedBy: 'source', targetEntity: Property::class, orphanRemoval: true)]
    private Collection $properties;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->properties = new ArrayCollection();
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
    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    /**
     * @param string $sourceType
     * @return $this
     */
    public function setSourceType(string $sourceType): static
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSourceLink(): ?string
    {
        return $this->sourceLink;
    }

    /**
     * @param string|null $sourceLink
     * @return $this
     */
    public function setSourceLink(?string $sourceLink): static
    {
        $this->sourceLink = $sourceLink;

        return $this;
    }

    /**
     * @return Collection<int, Property>
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    public function addProperty(Property $property): static
    {
        if (!$this->properties->contains($property)) {
            $this->properties->add($property);
            $property->setSource($this);
        }

        return $this;
    }

    public function removeProperty(Property $property): static
    {
        if ($this->properties->removeElement($property)) {
            // set the owning side to null (unless already changed)
            if ($property->getSource() === $this) {
                $property->setSource(null);
            }
        }

        return $this;
    }
}
