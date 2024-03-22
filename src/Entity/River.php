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
use App\Repository\RiverRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class River
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-03-21)
 * @since 0.1.0 (2024-03-21) First version.
 */
#[ORM\Entity(repositoryClass: RiverRepository::class)]
#[ORM\Index(columns: ['name'])]
#[ORM\UniqueConstraint(columns: ['river_code'])]
#[ORM\HasLifecycleCallbacks]
class River
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'river', cascade: ['persist', 'remove'])]
    private ?Location $location = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $riverCode = null;

    #[ORM\Column(length: 1024)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $length = null;

    /** @var Collection<int, RiverPart> $riverParts */
    #[ORM\OneToMany(mappedBy: 'river', targetEntity: RiverPart::class)]
    private Collection $riverParts;

    /**
     *
     */
    public function __construct()
    {
        $this->riverParts = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Location|null
     */
    public function getLocation(): ?Location
    {
        return $this->location;
    }

    /**
     * @param Location|null $location
     * @return $this
     */
    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRiverCode(): ?string
    {
        return $this->riverCode;
    }

    /**
     * @param string $riverCode
     * @return $this
     */
    public function setRiverCode(string $riverCode): static
    {
        $this->riverCode = $riverCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLength(): ?string
    {
        return $this->length;
    }

    /**
     * @param string $length
     * @return $this
     */
    public function setLength(string $length): static
    {
        $this->length = $length;

        return $this;
    }

    /**
     * @return Collection<int, RiverPart>
     */
    public function getRiverParts(): Collection
    {
        return $this->riverParts;
    }

    /**
     * @param RiverPart $riverPart
     * @return $this
     */
    public function addRiverPart(RiverPart $riverPart): static
    {
        if (!$this->riverParts->contains($riverPart)) {
            $this->riverParts->add($riverPart);
            $riverPart->setRiver($this);
        }

        return $this;
    }

    /**
     * @param RiverPart $riverPart
     * @return $this
     */
    public function removeRiverPart(RiverPart $riverPart): static
    {
        if ($this->riverParts->removeElement($riverPart)) {
            // set the owning side to null (unless already changed)
            if ($riverPart->getRiver() === $this) {
                $riverPart->setRiver(null);
            }
        }

        return $this;
    }
}
