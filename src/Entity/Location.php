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
use App\DBAL\GeoLocation\Types\PostgreSQL\Base\BasePostGISType;
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
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Index(columns: ['coordinate'], flags: ['gist'])]
#[ORM\Index(columns: ['geoname_id'])]
#[ORM\Index(columns: ['name'])]
#[ORM\Index(columns: ['source'])]
#[ORM\Index(columns: ['mapping_river_ignore'])]
#[ORM\HasLifecycleCallbacks]
class Location
{
    final public const NAME_SEPARATOR = '<->';

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

    #[ORM\Column(type: BasePostGISType::GEOGRAPHY, nullable: false, options: ['geometry_type' => 'Point', 'srid' => 4326, 'comment' => 'Point,4326'])]
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

    /** @var Collection<int, Property> $properties */
    #[ORM\OneToMany(mappedBy: 'location', targetEntity: Property::class, orphanRemoval: true)]
    private Collection $properties;

    /** @var Collection<int, SearchIndex> $searchIndices */
    #[ORM\OneToMany(mappedBy: 'location', targetEntity: SearchIndex::class, orphanRemoval: true)]
    private Collection $searchIndices;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $mappingRiverIgnore = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true)]
    private ?string $mappingRiverSimilarity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 7, scale: 4, nullable: true)]
    private ?string $mappingRiverDistance = null;

    /** @var Collection<int, River> $rivers */
    #[ORM\ManyToMany(targetEntity: River::class, inversedBy: 'locations')]
    private Collection $rivers;

    /** @var string[]|null $names */
    #[Ignore]
    private ?array $names = null;

    #[Ignore]
    private ?float $closestDistance = null;

    #[Ignore]
    private ?int $relevanceScore = null;

    #[Ignore]
    private ?int $locationType = null;

    #[Ignore]
    private ?int $rankCity = null;

    #[Ignore]
    private ?int $rankDistrict = null;

    /**
     */
    public function __construct()
    {
        $this->imports = new ArrayCollection();
        $this->alternateNames = new ArrayCollection();
        $this->properties = new ArrayCollection();
        $this->searchIndices = new ArrayCollection();
        $this->rivers = new ArrayCollection();
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

        if ($elevation <= 0) {
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

        if ($dem <= 0) {
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
     * @return bool
     */
    public function isRiver(): bool
    {
        return $this->getFeatureCode()?->getCode() === FeatureCodeConstants::STM;
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
     * @return Collection<int, Property>
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    /**
     * @param Property $property
     * @return $this
     */
    public function addProperty(Property $property): static
    {
        if (!$this->properties->contains($property)) {
            $this->properties->add($property);
            $property->setLocation($this);
        }

        return $this;
    }

    /**
     * @param Property $property
     * @return $this
     */
    public function removeProperty(Property $property): static
    {
        if ($this->properties->removeElement($property)) {
            // set the owning side to null (unless already changed)
            if ($property->getLocation() === $this) {
                $property->setLocation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SearchIndex>
     */
    public function getSearchIndices(): Collection
    {
        return $this->searchIndices;
    }

    /**
     * @param SearchIndex $searchIndex
     * @return $this
     */
    public function addIndex(SearchIndex $searchIndex): static
    {
        if (!$this->searchIndices->contains($searchIndex)) {
            $this->searchIndices->add($searchIndex);
            $searchIndex->setLocation($this);
        }

        return $this;
    }

    /**
     * @param SearchIndex $searchIndex
     * @return $this
     */
    public function removeIndex(SearchIndex $searchIndex): static
    {
        if ($this->searchIndices->removeElement($searchIndex)) {
            // set the owning side to null (unless already changed)
            if ($searchIndex->getLocation() === $this) {
                $searchIndex->setLocation(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @param string|null $source
     * @return $this
     */
    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getMappingRiverIgnore(): ?bool
    {
        return $this->mappingRiverIgnore;
    }

    /**
     * @param bool|null $mappingRiverIgnore
     * @return Location
     */
    public function setMappingRiverIgnore(?bool $mappingRiverIgnore): Location
    {
        $this->mappingRiverIgnore = $mappingRiverIgnore;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMappingRiverSimilarity(): ?string
    {
        return $this->mappingRiverSimilarity;
    }

    /**
     * @param string|null $mappingRiverSimilarity
     * @return $this
     */
    public function setMappingRiverSimilarity(?string $mappingRiverSimilarity): static
    {
        $this->mappingRiverSimilarity = $mappingRiverSimilarity;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMappingRiverDistance(): ?string
    {
        return $this->mappingRiverDistance;
    }

    /**
     * @param string|null $mappingRiverDistance
     * @return $this
     */
    public function setMappingRiverDistance(?string $mappingRiverDistance): static
    {
        $this->mappingRiverDistance = $mappingRiverDistance;

        return $this;
    }

    /**
     * @return Collection<int, River>
     */
    public function getRivers(): Collection
    {
        return $this->rivers;
    }

    /**
     * @param River $river
     * @return $this
     */
    public function addRiver(River $river): static
    {
        if (!$this->rivers->contains($river)) {
            $this->rivers->add($river);
        }

        return $this;
    }

    /**
     * @param River $river
     * @return $this
     */
    public function removeRiver(River $river): static
    {
        $this->rivers->removeElement($river);

        return $this;
    }



    /**
     * @return string[]|null
     */
    #[Ignore]
    public function getNames(): ?array
    {
        return $this->names;
    }

    /**
     * @return string|null
     */
    #[Ignore]
    public function getNamesAsString(): string|null
    {
        if (is_null($this->names)) {
            return null;
        }

        return implode(', ', $this->names);
    }

    /**
     * @param string[] $names
     * @return self
     */
    #[Ignore]
    public function setNames(array $names): self
    {
        $uniqueNames = [];

        foreach ($names as $name) {
            $name = trim($name);

            if (empty($name)) {
                continue;
            }

            /* Remove " (fluss)" from location or alternate_name string. */
            $name = preg_replace('~ \(fluss\)~i', '', $name);

            $uniqueNames[$name] = null;
        }

        $names = array_keys($uniqueNames);

        sort($names);

        $this->names = $names;

        return $this;
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
     * Returns the distance in kilometers.
     *
     * @return float|null
     */
    #[Ignore]
    public function getClosestDistance(): ?float
    {
        return $this->closestDistance;
    }

    /**
     * Sets the distance in kilometers.
     *
     * @param float $closestDistance
     * @return self
     */
    #[Ignore]
    public function setClosestDistance(float $closestDistance): self
    {
        $this->closestDistance = $closestDistance;

        return $this;
    }

    /**
     * Returns the relevance.
     *
     * @return int|null
     */
    #[Ignore]
    public function getRelevanceScore(): ?int
    {
        return $this->relevanceScore;
    }

    /**
     * Sets the relevance.
     *
     * @param int $relevanceScore
     * @return self
     */
    #[Ignore]
    public function setRelevanceScore(int $relevanceScore): self
    {
        $this->relevanceScore = $relevanceScore;

        return $this;
    }

    /**
     * Gets the location type if given from self::setLocationType.
     *
     * @return int|null
     */
    #[Ignore]
    public function getLocationType(): ?int
    {
        return $this->locationType;
    }

    /**
     * Sets the location type.
     *
     * See \App\Constants\Query\QueryAdmin::ADMIN
     *
     * - 10: ADM2
     * - 11: ADM3
     * - 12: ADM4
     * - 13: ADM5
     * - 20: CITY
     * - 21: DISTRICT
     * - 90: UNKNOWN
     *
     * @param int|null $locationType
     * @return $this
     */
    #[Ignore]
    public function setLocationType(?int $locationType): static
    {
        $this->locationType = $locationType;

        return $this;
    }

    /**
     * @return int|null
     */
    #[Ignore]
    public function getRankCity(): ?int
    {
        return $this->rankCity;
    }

    /**
     * @param int|null $rankCity
     * @return self
     */
    #[Ignore]
    public function setRankCity(?int $rankCity): self
    {
        $this->rankCity = $rankCity;

        return $this;
    }

    /**
     * @return int|null
     */
    #[Ignore]
    public function getRankDistrict(): ?int
    {
        return $this->rankDistrict;
    }

    /**
     * @param int|null $rankDistrict
     * @return self
     */
    #[Ignore]
    public function setRankDistrict(?int $rankDistrict): self
    {
        $this->rankDistrict = $rankDistrict;

        return $this;
    }

    /**
     * Returns the first valid elevation value from dem or elevation.
     *
     * @return int|null
     */
    #[Ignore]
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
     * @param string|string[] $search
     * @param Coordinate|null $coordinate
     * @return int
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    #[Ignore]
    public function calculateRelevance(string|array $search, Coordinate|null $coordinate = null): int
    {
        if (is_array($search)) {
            $search = implode(' ', $search);
        }

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

        /* Population */
        $population = $this->getPopulationCompiled();
        if (!is_null($population) && $population > 0 && $this->getFeatureClass()?->getClass() === FeatureClassConstants::P) {
            $relevance += (int) round($population / 10);
        }
        if (!is_null($population) && $population > 0 && $this->getFeatureClass()?->getClass() === FeatureClassConstants::A) {
            $relevance += (int) round($population / 100);
        }

        /* Elevation */
        $elevation = $this->getElevationOverall();
        if (!is_null($elevation) && $elevation > 0 && in_array($this->getFeatureCode()?->getCode(), [
            FeatureCodeConstants::HLL,
            FeatureCodeConstants::MT,
            FeatureCodeConstants::PK,
        ])) {
            $relevance += (int) round($elevation / 5);
        }

        /* Airport Passengers */
        $airportPassenger = $this->getAirportPassenger();
        if ($airportPassenger > 0 && $this->getFeatureCode()?->getCode() == FeatureCodeConstants::AIRP) {
            $relevance += (int) round($airportPassenger / 1000);
        }

        /* River length */
        $river = $this->getRiver();
        if ($river instanceof River) {
            $relevance += (int) round(floatval($river->getLength()) * 100);
        }

        /* The next calculations need a given coordinate. */
        if (is_null($coordinate)) {
            return $relevance;
        }

        $distanceMeter = $this->getClosestDistanceOrCalculate($coordinate);

        /* Remove relevance:
         * 1 km:     -10
         * 10 km:    -100
         * 100 km:   -1000
         * 20000 km: -200000
         */
        $relevance -= intval(round(floatval($distanceMeter) * 0.01, 0));

        return $relevance;
    }

    /**
     * Returns the distance in meters of this location to given coordinate.
     *
     * @param Coordinate $coordinate
     * @return float|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    #[Ignore]
    public function getClosestDistanceOrCalculate(Coordinate $coordinate): float|null
    {
        if (!is_null($this->closestDistance)) {
            return $this->closestDistance;
        }

        $river = $this->getRiver();

        /* Calculate location distance. */
        if (!$river instanceof River) {
            return $this->getCoordinateIxnode()->getDistance($coordinate);
        }

        $closestDistance = 999_999_999.;

        /** @var float|null $latitude */
        $latitude = null;
        /** @var float|null $longitude */
        $longitude = null;

        foreach ($river->getRiverParts() as $riverPart) {
            $coordinates = $riverPart->getCoordinates();

            if (is_null($coordinates)) {
                continue;
            }

            foreach ($coordinates->getPoints() as $point) {
                $distance = $coordinate->getDistance(new Coordinate($point->getLatitude(), $point->getLongitude()));

                if ($distance < $closestDistance) {
                    $closestDistance = $distance;
                    $latitude = $point->getLatitude();
                    $longitude = $point->getLongitude();
                }
            }
        }

        if (!is_null($latitude) && !is_null($longitude)) {
            $this->setCoordinate(new Point($latitude, $longitude));
        }

        $this->closestDistance = $closestDistance;

        return $closestDistance;
    }

    /**
     * @return int|null
     */
    #[Ignore]
    public function getAirportPassenger(): ?int
    {
        $properties = $this->getProperties();

        foreach ($properties as $property) {
            if ($property->getPropertyType() === 'airport' && $property->getPropertyName() === 'passengers') {
                return (int) $property->getPropertyValue();
            }
        }

        return null;
    }

    /**
     * Returns the first river.
     *
     * - River: River was found
     * - false: No river was found
     * - null: No river entity
     *
     * @return River|false|null
     */
    #[Ignore]
    public function getRiver(): River|false|null
    {
        if (!$this->isRiver()) {
            return null;
        }

        $rivers = $this->getRivers();

        if (count($rivers) <= 0) {
            return false;
        }

        return $rivers[0];
    }
}
