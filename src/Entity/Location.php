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

use App\Constants\DB\FeatureClass as FeatureClassConstants;
use App\Constants\DB\FeatureCode as FeatureCodeConstants;
use App\DBAL\GeoLocation\Types\PostgreSQL\PostGISType;
use App\DBAL\GeoLocation\ValueObject\Point;
use App\Entity\Trait\TimestampsTrait;
use App\Repository\LocationRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Parser\ParserException;
use LogicException;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * Class Location
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-27)
 * @since 0.1.0 (2023-06-27) First version.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Index(columns: ['coordinate'], flags: ['gist'])]
#[ORM\Index(columns: ['geoname_id'])]
#[ORM\Index(columns: ['name'])]
#[ORM\HasLifecycleCallbacks]
class Location
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $geonameId = null;

    #[ORM\Column(length: 1024)]
    private ?string $name = null;

    #[ORM\Column(length: 1024)]
    private ?string $asciiName = null;

    #[ORM\Column(type: PostGISType::GEOGRAPHY, nullable: false, options: ['geometry_type' => 'POINT', 'srid' => 4326])]
    private ?Point $coordinate = null;

    #[ORM\Column(length: 200)]
    private ?string $cc2 = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $population = null;

    #[ORM\Column(nullable: true)]
    private ?int $elevation = null;

    #[ORM\Column(nullable: true)]
    private ?int $dem = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $modificationDate = null;

    #[ORM\ManyToOne(inversedBy: 'locations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FeatureClass $featureClass = null;

    #[ORM\ManyToOne(inversedBy: 'locations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FeatureCode $featureCode = null;

    #[ORM\ManyToOne(inversedBy: 'locations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Country $country = null;

    #[ORM\ManyToOne(inversedBy: 'locations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Timezone $timezone = null;

    #[ORM\ManyToOne(inversedBy: 'locations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AdminCode $adminCode = null;

    /** @var Collection<int, Import> $imports */
    #[ORM\ManyToMany(targetEntity: Import::class, inversedBy: 'locations')]
    private Collection $imports;

    /** @var Collection<int, AlternateName> $alternateNames */
    #[ORM\OneToMany(mappedBy: 'location', targetEntity: AlternateName::class, orphanRemoval: true)]
    private Collection $alternateNames;

    public function __construct()
    {
        $this->imports = new ArrayCollection();
        $this->alternateNames = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getGeonameId(): ?int
    {
        return $this->geonameId;
    }

    /**
     * @param int $geonameId
     * @return $this
     */
    public function setGeonameId(int $geonameId): static
    {
        $this->geonameId = $geonameId;

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
    public function getAsciiName(): ?string
    {
        return $this->asciiName;
    }

    /**
     * @param string $asciiName
     * @return $this
     */
    public function setAsciiName(string $asciiName): static
    {
        $this->asciiName = $asciiName;

        return $this;
    }

    /**
     * @return Point|null
     */
    public function getCoordinate(): ?Point
    {
        return $this->coordinate;
    }

    /**
     * @param Point $coordinate
     * @return $this
     */
    public function setCoordinate(Point $coordinate): static
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCc2(): ?string
    {
        return $this->cc2;
    }

    /**
     * @param string $cc2
     * @return $this
     */
    public function setCc2(string $cc2): static
    {
        $this->cc2 = $cc2;

        return $this;
    }



    /**
     * @return string|null
     */
    public function getPopulation(): ?string
    {
        return $this->population;
    }

    /**
     * @return int|null
     */
    public function getPopulationCompiled(): ?int
    {
        $population = $this->getPopulation();

        if (is_null($population)) {
            return null;
        }

        $population = (int) $population;

        if ($population <= 0) {
            return null;
        }

        return $population;
    }

    /**
     * @param string|null $population
     * @return $this
     */
    public function setPopulation(?string $population): static
    {
        $this->population = $population;

        return $this;
    }



    /**
     * @return int|null
     */
    public function getElevation(): ?int
    {
        return $this->elevation;
    }

    /**
     * @return int|null
     */
    public function getElevationCompiled(): ?int
    {
        $elevation = $this->getElevation();

        if (is_null($elevation)) {
            return null;
        }

        if ($elevation < 0) {
            return null;
        }

        return $elevation;
    }

    /**
     * @param int|null $elevation
     * @return $this
     */
    public function setElevation(?int $elevation): static
    {
        $this->elevation = $elevation;

        return $this;
    }



    /**
     * Returns the elevation in meters (digital elevation model).
     *
     * See: https://de.wikipedia.org/wiki/Digitales_H%C3%B6henmodell
     * See: https://en.wikipedia.org/wiki/Digital_elevation_model
     *
     * @return int|null
     */
    public function getDem(): ?int
    {
        return $this->dem;
    }

    /**
     * Returns the elevation in meters (digital elevation model).
     *
     * @return int|null
     */
    public function getDemCompiled(): ?int
    {
        $dem = $this->getDem();

        if (is_null($dem)) {
            return null;
        }

        if ($dem < 0) {
            return null;
        }

        return $dem;
    }

    /**
     * @param int|null $dem
     * @return $this
     */
    public function setDem(?int $dem): static
    {
        $this->dem = $dem;

        return $this;
    }



    /**
     * @return DateTimeInterface|null
     */
    public function getModificationDate(): ?DateTimeInterface
    {
        return $this->modificationDate;
    }

    /**
     * @param DateTimeInterface $modificationDate
     * @return $this
     */
    public function setModificationDate(DateTimeInterface $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * @return FeatureClass|null
     */
    public function getFeatureClass(): ?FeatureClass
    {
        return $this->featureClass;
    }

    /**
     * @param FeatureClass|null $featureClass
     * @return $this
     */
    public function setFeatureClass(?FeatureClass $featureClass): static
    {
        $this->featureClass = $featureClass;

        return $this;
    }

    /**
     * @return FeatureCode|null
     */
    public function getFeatureCode(): ?FeatureCode
    {
        return $this->featureCode;
    }

    /**
     * @param FeatureCode|null $featureCode
     * @return $this
     */
    public function setFeatureCode(?FeatureCode $featureCode): static
    {
        $this->featureCode = $featureCode;

        return $this;
    }

    /**
     * @return Country|null
     */
    public function getCountry(): ?Country
    {
        return $this->country;
    }

    /**
     * @param Country|null $country
     * @return $this
     */
    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Timezone|null
     */
    public function getTimezone(): ?Timezone
    {
        return $this->timezone;
    }

    /**
     * @param Timezone|null $timezone
     * @return $this
     */
    public function setTimezone(?Timezone $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getAdminCode(): ?AdminCode
    {
        return $this->adminCode;
    }

    public function setAdminCode(?AdminCode $adminCode): static
    {
        $this->adminCode = $adminCode;

        return $this;
    }

    /**
     * @return Collection<int, Import>
     */
    public function getImports(): Collection
    {
        return $this->imports;
    }

    public function addImport(Import $import): static
    {
        if (!$this->imports->contains($import)) {
            $this->imports->add($import);
        }

        return $this;
    }

    public function removeImport(Import $import): static
    {
        $this->imports->removeElement($import);

        return $this;
    }

    /**
     * Get the distance to a second location.
     *
     * @param Location $locationTarget
     * @return float
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    #[Ignore]
    public function getDistance(Location $locationTarget): float
    {
        $pointSource = $this->getCoordinate();
        $pointTarget = $locationTarget->getCoordinate();

        if (!$pointSource instanceof Point) {
            throw new CaseUnsupportedException(sprintf('Location "%s" does not have a coordinate.', $this->getName()));
        }

        if (!$pointTarget instanceof Point) {
            throw new CaseUnsupportedException(sprintf('Location "%s" does not have a coordinate.', $locationTarget->getName()));
        }

        $coordinateSource = new Coordinate($pointSource->getLatitude(), $pointSource->getLongitude());
        $coordinateTarget = new Coordinate($pointTarget->getLatitude(), $pointTarget->getLongitude());

        return $coordinateSource->getDistance($coordinateTarget);
    }

    /**
     * Converts the coordinate Point to Coordinate.
     *
     * @return Coordinate
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    #[Ignore]
    public function getCoordinateIxnode(): Coordinate
    {
        $pointSource = $this->getCoordinate();

        if (!$pointSource instanceof Point) {
            throw new CaseUnsupportedException(sprintf('Location "%s" does not have a coordinate.', $this->getName()));
        }

        return new Coordinate($pointSource->getLatitude(), $pointSource->getLongitude());
    }

    /**
     * Returns the position of the location.
     *
     * @return string
     * @throws CaseUnsupportedException
     */
    #[Ignore]
    public function getPosition(): string
    {
        $pointSource = $this->getCoordinate();

        if (!$pointSource instanceof Point) {
            throw new CaseUnsupportedException(sprintf('Location "%s" does not have a coordinate.', $this->getName()));
        }

        return sprintf('%s, %s', $pointSource->getLatitude(), $pointSource->getLongitude());
    }

    /**
     * @return Collection<int, AlternateName>
     */
    public function getAlternateNames(): Collection
    {
        return $this->alternateNames;
    }

    /**
     * @param AlternateName $alternateName
     * @return $this
     */
    public function addAlternateName(AlternateName $alternateName): static
    {
        if (!$this->alternateNames->contains($alternateName)) {
            $this->alternateNames->add($alternateName);
            $alternateName->setLocation($this);
        }

        return $this;
    }

    /**
     * @param AlternateName $alternateName
     * @return $this
     */
    public function removeAlternateName(AlternateName $alternateName): static
    {
        if ($this->alternateNames->removeElement($alternateName)) {
            // set the owning side to null (unless already changed)
            if ($alternateName->getLocation() === $this) {
                $alternateName->setLocation(null);
            }
        }

        return $this;
    }



    /**
     * Returns the first valid elevation value from dem or elevation.
     *
     * @return int|null
     */
    public function getElevationOverall(): ?int
    {
        $elevation = $this->getElevationCompiled();
        $dem = $this->getDemCompiled();

        return match (true) {
            !is_null($dem) => $dem,
            !is_null($elevation) => $elevation,
            default => null,
        };
    }


    /**
     * Returns the relevance from given search and coordinate.
     *
     * @param string $search
     * @param Coordinate|null $coordinate
     * @return int
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function getRelevance(string $search, Coordinate|null $coordinate = null): int
    {
        $relevance = 200000; /* 20.000 km (half-earth circulation), to avoid relevance's < 0 */

        $name = $this->getName();

        if (is_null($name)) {
            throw new LogicException(sprintf('Location "%d" does not have a name.', $this->getId()));
        }

        /* The given place is equal to search name. */
        if (strtolower($name) === strtolower($search)) {
            $relevance += 10000;
        }

        /* The given place starts with search name. */
        if (str_starts_with(strtolower($name), strtolower($search))) {
            $relevance += 7500;
        }

        /* The search name is a word within the given place */
        if (preg_match(sprintf('~(^| )(%s)( |$)~i', $search), $name)) {
            $relevance += 7500;
        }

        $featureClass = $this->getFeatureClass()?->getClass();
        $featureCode = $this->getFeatureCode()?->getCode();

        /* Admin Place */
        if ($featureClass === FeatureClassConstants::A) {
            $relevance += match ($featureCode) {
                FeatureCodeConstants::ADM1, FeatureCodeConstants::ADM1H => 5000,
                FeatureCodeConstants::ADM2, FeatureCodeConstants::ADM2H => 4500,
                FeatureCodeConstants::ADM3, FeatureCodeConstants::ADM3H => 4000,
                FeatureCodeConstants::ADM4, FeatureCodeConstants::ADM4H => 3500,
                FeatureCodeConstants::ADM5, FeatureCodeConstants::ADM5H => 3000,
                default => 2500,
            };
        }

        /* If this is not a hotel: +2000 */
        if ($featureCode !== FeatureCodeConstants::HTL) {
            $relevance += 2000;
        }

        if (is_null($coordinate)) {
            return $relevance;
        }

        $distanceMeter = $this->getCoordinateIxnode()->getDistance($coordinate);

        /* Remove relevance:
         * 1 km:     -10
         * 10 km:    -100
         * 100 km:   -1000
         * 20000 km: -200000
         */
        $relevance -= intval(round(floatval($distanceMeter) * 0.01, 0));

        return $relevance;
    }
}
