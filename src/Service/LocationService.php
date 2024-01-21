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

namespace App\Service;

use App\ApiPlatform\Resource\Location;
use App\Constants\DB\Limit;
use App\Constants\Key\KeyArray;
use App\Constants\Language\CountryCode;
use App\Constants\Language\LanguageCode;
use App\Entity\Location as LocationEntity;
use App\Service\Base\BaseLocationService;
use App\Utils\Performance\PerformanceLogger;
use Doctrine\ORM\NonUniqueResultException;
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

/**
 * Class LocationService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-29)
 * @since 0.1.0 (2023-07-29) First version.
 */
final class LocationService extends BaseLocationService
{
    private const PERFORMANCE_NAME_FIND_LOCATIONS_BY_COORDINATE = 'findLocationsByCoordinate';

    private const PERFORMANCE_NAME_FIND_LOCATIONS_BY_NAME = 'findLocationsByName';

    private const PERFORMANCE_ADD_LOCATIONS = 'addLocationResourceSimple';

    private const PERFORMANCE_SORT_LOCATIONS = 'usort';

    private const PERFORMANCE_ARRAY_SLICE_LOCATIONS = 'array_slice';

    private const PERFORMANCE_ARRAY_FILTER_LOCATIONS = 'array_filter';

    private const PERFORMANCE_NAME_GET_LOCATION_RESOURCE_FULL = 'getLocationResourceFull';

    private const PERFORMANCE_NAME_LOCATION_ENTITY_BY_COORDINATE = 'getLocationEntityByCoordinate';

    private const PERFORMANCE_NAME_FIND_ONE_BY = 'findOneBy';

    final public const SORT_BY_DISTANCE = KeyArray::DISTANCE;

    final public const SORT_BY_DISTANCE_USER = KeyArray::DISTANCE_USER;

    final public const SORT_BY_GEONAME_ID = KeyArray::GEONAME_ID;

    final public const SORT_BY_NAME = KeyArray::NAME;

    final public const SORT_BY_RELEVANCE = KeyArray::RELEVANCE;

    final public const SORT_BY_RELEVANCE_USER = KeyArray::RELEVANCE_USER;

    /**
     * Returns locations by given geoname ids.
     *
     * @param int[] $geonameIds
     * @param Coordinate|null $currentPosition
     * @param string $isoLanguage
     * @param string $country
     * @param bool $addLocations
     * @param bool $nextPlaces
     * @param bool $addNextPlacesConfig
     * @param string $sortBy
     * @param array<int, string> $namesFull
     * @return array<int, Location>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function getLocationsByGeonameIds(
        /* Search */
        array $geonameIds,

        /* Search filter */
        /* --- no filter --- */

        /* Configuration */
        Coordinate $currentPosition = null,
        string $isoLanguage = LanguageCode::EN,
        string $country = CountryCode::US,
        bool $addLocations = false,
        bool $nextPlaces = false,
        bool $addNextPlacesConfig = false,

        /* Sort configuration */
        string $sortBy = self::SORT_BY_GEONAME_ID,

        /* Other configuration */
        array $namesFull = [],
    ): array
    {
        /* Save search and env parameter */
        $this->update(
            isoLanguage: $isoLanguage,
            country: $country,
            addLocations: $addLocations,
            addNextPlaces: $nextPlaces,
            addNextPlacesConfig: $addNextPlacesConfig,
            currentPosition: $currentPosition,
        );

        $performanceLogger = PerformanceLogger::getInstance();
        $performanceGroupName = $performanceLogger->getGroupNameFromFileName(__FILE__);

        $locationEntities = [];
        $locations = [];


        /* Find locations by given geoname ids */
        $performanceLogger->logPerformance(function () use (&$locationEntities, $geonameIds) {

            /* Start task */
            $locationEntities = $this->locationRepository->findLocationsByGeonameIds($geonameIds);
            /* Finish task */

        }, self::PERFORMANCE_NAME_FIND_LOCATIONS_BY_COORDINATE, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        /* Collect locations and add additional information */
        $performanceLogger->logPerformance(function () use ($locationEntities, &$locations, $namesFull) {

            /* Start task */
            foreach ($locationEntities as $locationEntity) {
                if (!$locationEntity instanceof LocationEntity) {
                    continue;
                }

                $geonameId = $locationEntity->getGeonameId();

                $locations[] = $this->getLocationResourceSimple(
                    locationEntity: $locationEntity,
                    nameFull: !is_null($geonameId) && array_key_exists($geonameId, $namesFull) ?
                        $namesFull[$geonameId] :
                        null
                );
            }
            /* Finish task */

        }, self::PERFORMANCE_ADD_LOCATIONS, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        /* Sort array according to given $sortBy */
        $performanceLogger->logPerformance(function () use (&$locations, $sortBy) {

            /* Start task */
            match ($sortBy) {
                self::SORT_BY_GEONAME_ID => $this->sortLocationsByGeonameId($locations),
                self::SORT_BY_NAME => $this->sortLocationsByName($locations),
                self::SORT_BY_DISTANCE => $this->sortLocationsByDistance($locations),
                self::SORT_BY_DISTANCE_USER => $this->sortLocationsByDistanceUser($locations),
                default => null,
            };
            /* Finish task */

        }, self::PERFORMANCE_SORT_LOCATIONS, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        return $locations;
    }

    /**
     * Returns locations by given search string (filter limit, feature classes).
     *
     * @param string $search
     * @param Coordinate|null $currentPosition
     * @param array<int, string>|string|null $featureClass
     * @param array<int, string>|string|null $featureCode
     * @param int|null $limit
     * @param string $isoLanguage
     * @param string $country
     * @param bool $addLocations
     * @param bool $addNextPlaces
     * @param bool $addNextPlacesConfig
     * @param string $sortBy
     * @return array<int, Location>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function getLocationsBySearch(
        /* Search */
        string $search,

        /* Search filter */
        array|string|null $featureClass = null,
        array|string|null $featureCode = null,
        int|null $limit = Limit::LIMIT_10,

        /* Configuration */
        Coordinate|null $currentPosition = null,
        string $isoLanguage = LanguageCode::EN,
        string $country = CountryCode::US,
        bool $addLocations = false,
        bool $addNextPlaces = false,
        bool $addNextPlacesConfig = false,

        /* Sort configuration */
        string $sortBy = self::SORT_BY_GEONAME_ID
    ): array
    {
        /* Save search and env parameter */
        $this->update(
            isoLanguage: $isoLanguage,
            country: $country,
            addLocations: $addLocations,
            addNextPlaces: $addNextPlaces,
            addNextPlacesConfig: $addNextPlacesConfig,
            currentPosition: $currentPosition
        );

        $performanceLogger = PerformanceLogger::getInstance();
        $performanceGroupName = $performanceLogger->getGroupNameFromFileName(__FILE__);

        /* Save filter parameter and query locations */
        $locationEntities = [];
        $performanceLogger->logPerformance(function () use (&$locationEntities, $search) {

            /* Start task */
            $locationEntities = $this->locationRepository->findBySearch($search);
            /* Finish task */

        }, self::PERFORMANCE_NAME_FIND_LOCATIONS_BY_NAME, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        /* Filter by empty feature codes */
        $performanceLogger->logPerformance(function () use (&$locationEntities) {

            /* Start task */
            $locationEntities = array_filter(
                $locationEntities,
                fn(LocationEntity $locationEntity) => !in_array($locationEntity->getFeatureCode()?->getCode(), ['', null])
            );
            /* Finish task */

        }, self::PERFORMANCE_ARRAY_FILTER_LOCATIONS, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        /* Sort array according to given $sortBy */
        $performanceLogger->logPerformance(function () use (&$locationEntities, $search, $currentPosition, $sortBy) {

            /* Start task */
            match ($sortBy) {
                self::SORT_BY_DISTANCE,
                self::SORT_BY_DISTANCE_USER => $this->sortLocationEntitiesByDistance($locationEntities, $currentPosition),

                self::SORT_BY_RELEVANCE,
                self::SORT_BY_RELEVANCE_USER => $this->sortLocationEntitiesByRelevance($locationEntities, $search, $currentPosition),

                self::SORT_BY_GEONAME_ID => $this->sortLocationEntitiesByGeonameId($locationEntities),
                self::SORT_BY_NAME => $this->sortLocationEntitiesByName($locationEntities),

                default => null,
            };
            /* Finish task */

        }, self::PERFORMANCE_SORT_LOCATIONS, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        /* Limit the result */
        $performanceLogger->logPerformance(function () use (&$locationEntities, $limit) {

            /* Start task */
            $locationEntities = array_slice($locationEntities, 0, $limit);
            /* Finish task */

        }, self::PERFORMANCE_ARRAY_SLICE_LOCATIONS, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        /* Collect locations and add additional information */
        $locations = [];
        $performanceLogger->logPerformance(function () use ($locationEntities, &$locations) {

            /* Start task */
            foreach ($locationEntities as $locationEntity) {
                if (!$locationEntity instanceof LocationEntity) {
                    continue;
                }
                $locations[] = $this->getLocationResourceSimple(locationEntity: $locationEntity);
            }
            /* Finish task */

        }, self::PERFORMANCE_ADD_LOCATIONS, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        return $locations;
    }

    /**
     * Returns locations by given coordinates string (filter limit, distance, feature classes).
     *
     * @param Coordinate $coordinate
     * @param int|null $distanceMeter
     * @param array<int, string>|string|null $featureClass
     * @param array<int, string>|string|null $featureCode
     * @param int|null $limit
     * @param Coordinate|null $currentPosition
     * @param string $isoLanguage
     * @param string $country
     * @param bool $addLocations
     * @param bool $addNextPlaces
     * @param bool $addNextPlacesConfig
     * @param string $sortBy
     * @return array<int, Location>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function getLocationsByCoordinate(
        /* Search */
        Coordinate $coordinate,

        /* Search filter */
        int|null $distanceMeter = null,
        array|string|null $featureClass = null,
        array|string|null $featureCode = null,
        int|null $limit = Limit::LIMIT_10,

        /* Configuration */
        Coordinate $currentPosition = null,
        string $isoLanguage = LanguageCode::EN,
        string $country = CountryCode::US,
        bool $addLocations = false,
        bool $addNextPlaces = false,
        bool $addNextPlacesConfig = false,

        /* Sort configuration */
        string $sortBy = self::SORT_BY_GEONAME_ID
    ): array
    {
        /* Save search and env parameter */
        $this->update(
            isoLanguage: $isoLanguage,
            country: $country,
            addLocations: $addLocations,
            addNextPlaces: $addNextPlaces,
            addNextPlacesConfig: $addNextPlacesConfig,
            coordinate: $coordinate,
            currentPosition: $currentPosition
        );

        $performanceLogger = PerformanceLogger::getInstance();
        $performanceGroupName = $performanceLogger->getGroupNameFromFileName(__FILE__);

        $locationEntities = [];
        $locations = [];

        /* Save filter parameter and query locations */
        $performanceLogger->logPerformance(function () use (&$locationEntities, $limit, $distanceMeter, $featureClass, $featureCode, $coordinate) {

            /* Start task */
            $this->doBeforeQueryTasks($limit, $distanceMeter, $featureClass, $featureCode);
            $locationEntities = $this->locationRepository->findLocationsByCoordinate(
                coordinate: $coordinate,
                distanceMeter: $this->distanceMeter,
                featureClasses: $this->featureClass,
                featureCodes: $this->featureCode,
                limit: $this->limit
            );
            $this->doAfterQueryTasks($locationEntities);
            /* Finish task */

        }, self::PERFORMANCE_NAME_FIND_LOCATIONS_BY_COORDINATE, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        /* Sort array according to given $sortBy */
        $search = '';
        $performanceLogger->logPerformance(function () use (&$locationEntities, $search, $coordinate, $currentPosition, $sortBy) {

            /* Start task */
            match ($sortBy) {
                self::SORT_BY_DISTANCE => $this->sortLocationEntitiesByDistance($locationEntities, $coordinate),
                self::SORT_BY_DISTANCE_USER => $this->sortLocationEntitiesByDistance($locationEntities, $currentPosition),
                self::SORT_BY_GEONAME_ID => $this->sortLocationEntitiesByGeonameId($locationEntities),
                self::SORT_BY_NAME => $this->sortLocationEntitiesByName($locationEntities),
                self::SORT_BY_RELEVANCE => $this->sortLocationEntitiesByRelevance($locationEntities, $search, $coordinate),
                default => null,
            };
            /* Finish task */

        }, self::PERFORMANCE_SORT_LOCATIONS, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        /* Collect locations and add additional information */
        $performanceLogger->logPerformance(function () use ($locationEntities, &$locations) {

            /* Start task */
            foreach ($locationEntities as $locationEntity) {
                if (!$locationEntity instanceof LocationEntity) {
                    continue;
                }
                $locations[] = $this->getLocationResourceSimple(locationEntity: $locationEntity);
            }
            /* Finish task */

        }, self::PERFORMANCE_ADD_LOCATIONS, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));

        return $locations;
    }

    /**
     * Returns Location ressource by given geoname id.
     *
     * @param int $geonameId
     * @param Coordinate|null $currentPosition
     * @param string $isoLanguage
     * @param string $country
     * @param bool $addLocations
     * @param bool $addNextPlaces
     * @param bool $addNextPlacesConfig
     * @return Location
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function getLocationByGeonameId(
        /* Search */
        int $geonameId,

        /* Search filter */
        /* --- no filter --- */

        /* Configuration */
        Coordinate $currentPosition = null,
        string $isoLanguage = LanguageCode::EN,
        string $country = CountryCode::US,
        bool $addLocations = false,
        bool $addNextPlaces = false,
        bool $addNextPlacesConfig = false
    ): Location
    {
        $performanceLogger = PerformanceLogger::getInstance();
        $performanceGroupName = $performanceLogger->getGroupNameFromFileName(__FILE__);

        /* Save search and env parameter */
        $this->update(
            isoLanguage: $isoLanguage,
            country: $country,
            addLocations: $addLocations,
            addNextPlaces: $addNextPlaces,
            addNextPlacesConfig: $addNextPlacesConfig,
            currentPosition: $currentPosition
        );

        /* Execute $this->locationRepository->findOneBy() and log performance. */
        $location = null;
        $performanceLogger->logPerformance(function () use (&$location, $geonameId) {

            /* Start task */
            $location = $this->locationRepository->findOneBy(['geonameId' => $geonameId]);
            /* Finish task */

        }, self::PERFORMANCE_NAME_FIND_ONE_BY, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        if (!$location instanceof LocationEntity) {
            $this->setError(sprintf('Unable to find location with geoname id %d', $geonameId));
            return $this->getEmptyLocation($geonameId);
        }



        /* Execute $this->getLocationResourceFull() and log performance. */
        $locationEntity = null;
        $performanceLogger->logPerformance(function () use (&$locationEntity, $location) {

            /* Start task */
            $locationEntity = $this->getLocationResourceFull($location);
            /* Finish task */

        }, self::PERFORMANCE_NAME_GET_LOCATION_RESOURCE_FULL, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        if (is_null($locationEntity)) {
            throw new LogicException(sprintf('Unable to find location entity with geoname id %d', $geonameId));
        }

        return $locationEntity;
    }

    /**
     * Returns Location ressource by given coordinate string.
     *
     * @param Coordinate $coordinate
     * @param Coordinate|null $currentPosition
     * @param string $isoLanguage
     * @param string $country
     * @param bool $addLocations
     * @param bool $addNextPlaces
     * @param bool $addNextPlacesConfig
     * @return Location
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function getLocationByCoordinate(
        /* Search */
        Coordinate $coordinate,

        /* Search filter */
        /* --- no filter --- */

        /* Configuration */
        Coordinate $currentPosition = null,
        string $isoLanguage = LanguageCode::EN,
        string $country = CountryCode::US,
        bool $addLocations = false,
        bool $addNextPlaces = false,
        bool $addNextPlacesConfig = false,
    ): Location
    {
        /* Save search and env parameter */
        $this->update(
            isoLanguage: $isoLanguage,
            country: $country,
            addLocations: $addLocations,
            addNextPlaces: $addNextPlaces,
            addNextPlacesConfig: $addNextPlacesConfig,
            coordinate: $coordinate,
            currentPosition: $currentPosition
        );

//        $adminConfiguration = $this->locationRepository->findNextAdminConfiguration(
//            coordinate: $this->coordinate,
//            featureClasses: FeatureClass::FEATURE_CLASS_P,
//            featureCodes: FeatureClass::FEATURE_CODES_P_ALL,
//        );

        $performanceLogger = PerformanceLogger::getInstance();
        $performanceGroupName = $performanceLogger->getGroupNameFromFileName(__FILE__);



        /* Execute $this->getLocationResourceFull() and log performance. */
        $location = null;
        $performanceLogger->logPerformance(function () use (&$location, $coordinate) {

            /* Start task */
            $location = $this->getLocationEntityByCoordinate($coordinate);
            /* Finish task */

        }, self::PERFORMANCE_NAME_LOCATION_ENTITY_BY_COORDINATE, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        /* No location found. */
        if (is_null($location)) {
            return $this->getEmptyLocation();
        }



        /* Execute $this->getLocationResourceFull() and log performance. */
        $locationEntity = null;
        $performanceLogger->logPerformance(function () use (&$locationEntity, $location) {

            /* Start task */
            $locationEntity = $this->getLocationResourceFull($location);
            /* Finish task */

        }, self::PERFORMANCE_NAME_GET_LOCATION_RESOURCE_FULL, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));



        if (is_null($locationEntity)) {
            throw new LogicException(sprintf('Unable to find location entity with coordinate "%s".', $coordinate->getStringDMS()));
        }

        return $locationEntity;
    }
}
