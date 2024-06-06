<?php

/*
 * This file is part of the locate-place/php-location-api project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Service\Base\Helper;

use App\ApiPlatform\Resource\Location;
use App\Constants\DB\FeatureClass;
use App\Constants\DB\FeatureCode;
use App\Constants\Language\CountryCode;
use App\Constants\Language\LanguageCode;
use App\Entity\Location as LocationEntity;
use App\Entity\ZipCode;
use App\Entity\ZipCodeArea;
use App\Repository\AlternateNameRepository;
use App\Repository\CountryRepository;
use App\Repository\LocationRepository;
use App\Repository\RiverPartRepository;
use App\Repository\ZipCodeAreaRepository;
use App\Repository\ZipCodeRepository;
use App\Service\Entity\LocationEntityHelper;
use App\Service\LocationContainer;
use App\Service\LocationServiceConfig;
use App\Service\LocationServiceAlternateName;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class BaseHelperLocationService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class BaseHelperLocationService
{
    protected ?string $error = null;

    protected bool $debug = false;

    protected int|null $resultCount = null;

    protected float $timeStart;

    protected int|null $limit = null;

    protected int|null $limitSave = null;

    protected bool $filterAfterQuery = false;

    protected int|null $distanceMeter = null;

    /** @var array<int, string>|string|null $featureClass */
    protected array|string|null $featureClass = null;

    /** @var array<int, string>|string|null $featureCode */
    protected array|string|null $featureCode = null;



    protected Coordinate $coordinate;

    protected Coordinate $currentPosition;

    private string $isoLanguage = LanguageCode::EN;

    private string $country = CountryCode::US;

    private bool $addLocations = false;

    private bool $addNextPlaces = false;

    private bool $addNextPlacesConfig = false;



    protected LocationContainer $locationContainer;

    /** @var LocationContainer[] $locationContainerHolder */
    protected array $locationContainerHolder = [];

    protected LocationServiceAlternateName $locationServiceAlternateName;

    protected LocationEntityHelper $locationEntityHelper;

    protected FeatureClass $featureClassService;

    protected FeatureCode $featureCodeService;

    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     * @param LocationRepository $locationRepository
     * @param AlternateNameRepository $alternateNameRepository
     * @param ZipCodeRepository $zipCodeRepository
     * @param ZipCodeAreaRepository $zipCodeAreaRepository
     * @param RiverPartRepository $riverPartRepository
     * @param CountryRepository $countryRepository
     * @param TranslatorInterface $translator
     * @param LocationServiceConfig $locationServiceConfig
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request,
        protected LocationRepository $locationRepository,
        protected AlternateNameRepository $alternateNameRepository,
        protected ZipCodeRepository $zipCodeRepository,
        protected ZipCodeAreaRepository $zipCodeAreaRepository,
        protected RiverPartRepository $riverPartRepository,
        protected CountryRepository $countryRepository,
        protected TranslatorInterface $translator,
        protected LocationServiceConfig $locationServiceConfig
    )
    {
        $this->setTimeStart(microtime(true));

        $this->locationServiceAlternateName = new LocationServiceAlternateName($alternateNameRepository);
        $this->locationEntityHelper = new LocationEntityHelper(new LocationContainer($this->locationServiceAlternateName));

        $this->featureClassService = new FeatureClass($translator);
        $this->featureCodeService = new FeatureCode($translator);
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     * @return self
     */
    public function setDebug(bool|string $debug): self
    {
        if (is_bool($debug)) {
            $this->debug = $debug;
            return $this;
        }

        $this->debug = in_array($debug, ['1', 'true'], true);

        return $this;
    }

    /**
     * @return Coordinate
     */
    public function getCoordinate(): Coordinate
    {
        return $this->coordinate;
    }

    /**
     * @param Coordinate $coordinate
     * @return self
     */
    public function setCoordinate(Coordinate $coordinate): self
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * @return Coordinate
     */
    public function getCurrentPosition(): Coordinate
    {
        return $this->currentPosition;
    }

    /**
     * @param Coordinate $currentPosition
     * @return self
     */
    public function setCurrentPosition(Coordinate $currentPosition): self
    {
        $this->currentPosition = $currentPosition;

        return $this;
    }

    /**
     * @return string
     */
    protected function getIsoLanguage(): string
    {
        return $this->isoLanguage;
    }

    /**
     * @param string $isoLanguage
     * @return self
     */
    protected function setIsoLanguage(string $isoLanguage): self
    {
        $this->isoLanguage = $isoLanguage;

        return $this;
    }

    /**
     * @return string
     */
    protected function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return self
     */
    protected function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAddLocations(): bool
    {
        return $this->addLocations;
    }

    /**
     * @param bool $addLocations
     * @return self
     */
    public function setAddLocations(bool $addLocations): self
    {
        $this->addLocations = $addLocations;

        return $this;
    }

    /**
     * @return bool
     */
    protected function isAddNextPlaces(): bool
    {
        return $this->addNextPlaces;
    }

    /**
     * @param bool $addNextPlaces
     * @return self
     */
    protected function setAddNextPlaces(bool $addNextPlaces): self
    {
        $this->addNextPlaces = $addNextPlaces;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAddNextPlacesConfig(): bool
    {
        return $this->addNextPlacesConfig;
    }

    /**
     * @param bool $addNextPlacesConfig
     *
     * @return self
     */
    public function setAddNextPlacesConfig(bool $addNextPlacesConfig): self
    {
        $this->addNextPlacesConfig = $addNextPlacesConfig;

        return $this;
    }

    /**
     * Gets an error of this resource.
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Checks if an error occurred.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return is_string($this->error);
    }

    /**
     * Sets an error of this resource.
     *
     * @param string|null $error
     * @return self
     */
    protected function setError(?string $error): self
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Gets the number of results.
     *
     * @return int
     */
    public function getResultCount(): int
    {
        if (is_null($this->resultCount)) {
            throw new LogicException('Result count must be an integer.');
        }

        return $this->resultCount;
    }

    /**
     * Checks if a count of results is available.
     *
     * @return bool
     */
    public function hasResultCount(): bool
    {
        return is_int($this->resultCount);
    }

    /**
     * Sets the number of results.
     *
     * @param int $resultCount
     * @return self
     */
    protected function setResultCount(int $resultCount): self
    {
        $this->resultCount = $resultCount;

        return $this;
    }

    /**
     * Returns an empty Location entity.
     *
     * @param int|null $geonameId
     * @return Location
     */
    protected function getEmptyLocation(int|null $geonameId = null): Location
    {
        $location = new Location();

        if (!is_null($geonameId)) {
            $location->setGeonameId($geonameId);
        }

        return $location;
    }

    /**
     * @return float
     */
    public function getTimeStart(): float
    {
        return $this->timeStart;
    }

    /**
     * @param float $timeStart
     * @return self
     */
    public function setTimeStart(float $timeStart): self
    {
        $this->timeStart = $timeStart;

        return $this;
    }

    /**
     * @return LocationEntity|null
     */
    public function getDistrictEntity(): ?LocationEntity
    {
        return $this->locationContainer->getDistrict();
    }

    /**
     * @return LocationEntity|null
     */
    public function getBoroughEntity(): ?LocationEntity
    {
        return $this->locationContainer->getBorough();
    }

    /**
     * @return LocationEntity|null
     */
    public function getCityEntity(): ?LocationEntity
    {
        return $this->locationContainer->getCity();
    }

    /**
     * @return LocationEntity|null
     */
    public function getStateEntity(): ?LocationEntity
    {
        return $this->locationContainer->getState();
    }

    /**
     * @return LocationEntity|null
     */
    public function getCountryEntity(): ?LocationEntity
    {
        return $this->locationContainer->getCountry();
    }

    /**
     * Do "Before query" tasks.
     *
     * @param int|null $limit
     * @param int|null $distanceMeter
     * @param array<int, string>|string|null $featureClass
     * @param array<int, string>|string|null $featureCode
     * @return void
     */
    protected function doBeforeQueryTasks(
        int|null $limit,
        int|null $distanceMeter,
        array|string|null $featureClass,
        array|string|null $featureCode,
    ): void
    {
        $this->filterAfterQuery = match ($this->locationEntityHelper->toString($featureCode)) {
            FeatureCode::AIRP => true,
            default => false,
        };

        $this->limitSave = $this->filterAfterQuery ? $limit : null;
        $this->limit = $this->filterAfterQuery ? null : $this->limitSave;

        $this->distanceMeter = $distanceMeter;
        $this->featureClass = $featureClass;
        $this->featureCode = $featureCode;
    }

    /**
     * Do "After query" tasks.
     *
     * @param array<int, LocationEntity> $locationEntities
     * @return void
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function doAfterQueryTasks(array &$locationEntities): void
    {
        if (!$this->filterAfterQuery) {
            return;
        }

        $this->limit = $this->limitSave;
        $this->limitSave = null;

        $locationEntities = match ($this->locationEntityHelper->toString($this->featureCode)) {
            FeatureCode::AIRP => array_filter($locationEntities, fn(LocationEntity $locationEntity) => $this->locationEntityHelper->setLocation($locationEntity)->hasAirportCodeIata()),
            default => $locationEntities,
        };

        if (count($locationEntities) > $this->limit) {
            $locationEntities = array_slice($locationEntities, 0, $this->limit);
        }
    }

    /**
     * Sort given location entities by name.
     *
     * @param LocationEntity[] $locationEntities
     * @return void
     */
    protected function sortLocationEntitiesByName(array &$locationEntities): void
    {
        usort($locationEntities, fn(LocationEntity $locationA, LocationEntity $locationB) => strcmp($locationA->getName() ?: '', $locationB->getName() ?: ''));
    }

    /**
     * Sort given location entities by geoname id.
     *
     * @param LocationEntity[] $locationEntities
     * @return void
     */
    protected function sortLocationEntitiesByGeonameId(array &$locationEntities): void
    {
        usort($locationEntities, fn(LocationEntity $locationA, LocationEntity $locationB) => $locationA->getGeonameId() <=> $locationB->getGeonameId());
    }

    /**
     * Sort given location entities by distance.
     *
     * @param LocationEntity[] $locationEntities
     * @param Coordinate|null $coordinate
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    protected function sortLocationEntitiesByDistance(array &$locationEntities, Coordinate|null $coordinate = null): void
    {
        /* No coordinate was given -> sort by name. */
        if (is_null($coordinate)) {
            $this->sortLocationEntitiesByName($locationEntities);
            return;
        }

        $distances = [];

        /* Build distances array. */
        foreach ($locationEntities as $locationEntity) {
            $distances[$locationEntity->getId()] = $locationEntity->getClosestDistanceOrCalculate($coordinate);
        }

        /* Sort by distance. */
        usort($locationEntities, fn(LocationEntity $locationA, LocationEntity $locationB) => $distances[$locationA->getId()] <=> $distances[$locationB->getId()]);
    }

    /**
     * Sort given location entities by relevance.
     *
     * @param LocationEntity[] $locationEntities
     * @param string|string[] $search
     * @param Coordinate|null $coordinate
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    protected function sortLocationEntitiesByRelevance(array &$locationEntities, string|array $search, Coordinate|null $coordinate = null): void
    {
        $relevances = [];

        /* Build distances array. */
        foreach ($locationEntities as $locationEntity) {
            $relevances[$locationEntity->getId()] = $locationEntity->calculateRelevance($search, $coordinate);
        }

        /* Sort by relevances. */
        usort($locationEntities, fn(LocationEntity $locationA, LocationEntity $locationB) => $relevances[$locationB->getId()] <=> $relevances[$locationA->getId()]);
    }

    /**
     * Limits the result.
     *
     * Page 1 (10 results per page):
     *   - 0 - 9
     * Page 2 (10 results per page):
     *   - 10 - 19
     * etc.
     *
     * @param LocationEntity[] $locationEntities
     * @param int|null $limit
     * @param int $page
     * @return void
     */
    protected function limitResult(array &$locationEntities, int|null $limit, int $page = 1): void
    {
        if (is_null($limit)) {
            return;
        }

        $locationEntities = array_slice($locationEntities, ($page - 1) * $limit, $limit);
    }

    /**
     * Sort given locations by name.
     *
     * @param Location[] $locations
     * @return void
     */
    protected function sortLocationsByName(array &$locations): void
    {
        usort($locations, fn(Location $locationA, Location $locationB) => strcmp(strtolower($locationA->getName()), strtolower($locationB->getName())));
    }

    /**
     * Sort given location entities by geoname id.
     *
     * @param Location[] $locations
     * @return void
     */
    protected function sortLocationsByGeonameId(array &$locations): void
    {
        usort($locations, fn(Location $locationA, Location $locationB) => $locationA->getGeonameId() <=> $locationB->getGeonameId());
    }

    /**
     * Sort given location entities by distance.
     *
     * @param Location[] $locations
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     * @throws JsonException
     */
    protected function sortLocationsByDistance(array &$locations): void
    {
        usort($locations, fn(Location $locationA, Location $locationB) =>
            $locationA->getCoordinate()->getDistance() <=> $locationB->getCoordinate()->getDistance()
        );
    }

    /**
     * Sort given location entities by distance from user position.
     *
     * @param Location[] $locations
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     * @throws JsonException
     */
    protected function sortLocationsByDistanceUser(array &$locations): void
    {
        usort($locations, fn(Location $locationA, Location $locationB) =>
            $locationA->getCoordinate()->getDistanceUser() <=> $locationB->getCoordinate()->getDistanceUser()
        );
    }

    /**
     * Updates the given parameter.
     *
     * @param string $isoLanguage
     * @param string $country
     * @param bool $addLocations
     * @param bool $addNextPlaces
     * @param bool $addNextPlacesConfig
     * @param Coordinate|null $coordinate
     * @param Coordinate|null $currentPosition
     * @return void
     */
    protected function update(
        string $isoLanguage,
        string $country,
        bool $addLocations,
        bool $addNextPlaces,
        bool $addNextPlacesConfig,
        Coordinate|null $coordinate = null,
        Coordinate|null $currentPosition = null,
    ): void
    {
        $this->setIsoLanguage($isoLanguage);
        $this->setCountry($country);
        $this->setAddLocations($addLocations);
        $this->setAddNextPlaces($addNextPlaces);
        $this->setAddNextPlacesConfig($addNextPlacesConfig);

//        if (!is_null($coordinate) && is_null($currentPosition)) {
//            $currentPosition = $coordinate;
//        }

        !is_null($coordinate) && $this->setCoordinate($coordinate);
        !is_null($currentPosition) && $this->setCurrentPosition($currentPosition);

        $this->featureClassService->setLocaleByLanguageAndCountry($isoLanguage, $country);
        $this->featureCodeService->setLocaleByLanguageAndCountry($isoLanguage, $country);
    }

    /**
     * Returns the ZipCode or ZipCodeArea entity by given Location entity.
     *
     * @param LocationEntity $locationEntity
     * @return ZipCode|ZipCodeArea|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws TypeInvalidException
     * @throws ClassInvalidException
     */
    protected function getZipCode(LocationEntity $locationEntity): ZipCode|ZipCodeArea|null
    {
        $countryCode = $locationEntity->getCountry()?->getCode();

        if ($countryCode === CountryCode::DE) {
            return $this->zipCodeAreaRepository->findZipCodeByCoordinate(
                coordinate: $locationEntity->getCoordinateIxnode(),
                country: $locationEntity->getCountry(),
                limit: 1
            );
        }

        return $this->zipCodeRepository->findZipCodeByCoordinate(
            coordinate: $locationEntity->getCoordinateIxnode(),
            distanceMeter: 10000,
            country: $locationEntity->getCountry(),
            limit: 1
        );
    }
}
