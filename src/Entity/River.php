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

use App\DBAL\GeoLocation\ValueObject\Point;
use App\Entity\Trait\TimestampsTrait;
use App\Repository\RiverRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Parser\ParserException;

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

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $riverCode = null;

    #[ORM\Column(length: 1024)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $length = null;

    /** @var Collection<int, RiverPart> $riverParts */
    #[ORM\OneToMany(mappedBy: 'river', targetEntity: RiverPart::class)]
    private Collection $riverParts;

    /** @var Collection<int, Location> $locations */
    #[ORM\ManyToMany(targetEntity: Location::class, mappedBy: 'rivers')]
    private Collection $locations;

    private ?float $closestDistance = null;

    private ?Point $closestCoordinate = null;

    /**
     *
     */
    public function __construct()
    {
        $this->riverParts = new ArrayCollection();
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
     * @param int $id
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

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
     * @return string[]|null
     */
    public function getNames(): array|null
    {
        if (is_null($this->name)) {
            return null;
        }

        return explode('/', $this->name);
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
            $location->addRiver($this);
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
            $location->removeRiver($this);
        }

        return $this;
    }



    /**
     * Returns the distance in kilometers.
     *
     * @return float|null
     */
    public function getClosestDistance(): ?float
    {
        return $this->closestDistance;
    }

    /**
     * Sets the distance in kilometers.
     *
     * @param float $closestDistance
     * @return River
     */
    public function setClosestDistance(float $closestDistance): River
    {
        $this->closestDistance = $closestDistance;

        return $this;
    }

    /**
     * Get the closest coordinate as Point.
     *
     * @return Point|null
     */
    public function getClosestCoordinate(): Point|null
    {
        return $this->closestCoordinate;
    }

    /**
     * Get the closest coordinate as Coordinate.
     *
     * @return Coordinate|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function getClosestCoordinateIxnode(): Coordinate|null
    {
        if (is_null($this->closestCoordinate)) {
            return null;
        }

        return new Coordinate(
            $this->closestCoordinate->getLatitude(),
            $this->closestCoordinate->getLongitude()
        );
    }

    /**
     * @param Point|null $closestCoordinate
     * @return River
     */
    public function setClosestCoordinate(?Point $closestCoordinate): River
    {
        $this->closestCoordinate = $closestCoordinate;

        return $this;
    }
}
