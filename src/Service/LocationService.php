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
use App\DBAL\GeoLocation\ValueObject\Point;
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

/**
 * Class LocationService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-29)
 * @since 0.1.0 (2023-07-29) First version.
 */
final class LocationService extends BaseLocationService
{
    private const PERFORMANCE_NAME_FIND_LOCATIONS = 'findLocationsByCoordinate';

    private const PERFORMANCE_ADD_LOCATIONS = 'addLocationResourceSimple';

    private const PERFORMANCE_SORT_LOCATIONS = 'usort';

    final public const SORT_BY_GEONAME_ID = KeyArray::GEONAME_ID;

    final public const SORT_BY_NAME = KeyArray::NAME;

    final public const SORT_BY_DISTANCE = KeyArray::DISTANCE;

    /**
     * Returns locations by given geoname ids.
     *
     * @param int[] $geonameIds
     * @param Coordinate|null $coordinate
     * @param string $isoLanguage
     * @param string $country
     * @param bool $nextPlaces
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
    public function getLocationsByByGeonameIds(
        /* Search */
        array $geonameIds,
        Coordinate $coordinate = null,

        /* Search filter */
        /* --- no filter --- */

        /* Configuration */
        string $isoLanguage = LanguageCode::EN,
        string $country = CountryCode::US,
        bool $nextPlaces = false,

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
            nextPlaces: $nextPlaces,
            coordinate: $coordinate,
        );

        $performanceLogger = PerformanceLogger::getInstance();
        $performanceGroupName = $performanceLogger->getGroupNameFromFileName(__FILE__);

        $locationEntities = [];
        $locations = [];


        /* Save filter parameter and query locations */
        $performanceLogger->logPerformance(function () use (&$locationEntities, $geonameIds) {

            /* Start task */
            $locationEntities = $this->locationRepository->findLocationsByGeonameIds($geonameIds);
            /* Finish task */

        }, self::PERFORMANCE_NAME_FIND_LOCATIONS, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));


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
                self::SORT_BY_GEONAME_ID => usort($locations, fn(Location $a, Location $b) => $a->getGeonameId() <=> $b->getGeonameId()),
                self::SORT_BY_NAME => usort($locations, fn(Location $a, Location $b) => strcmp($a->getName(), $b->getName())),
                self::SORT_BY_DISTANCE => usort($locations, fn(Location $a, Location $b) => $a->getCoordinate()->getDistance() <=> $b->getCoordinate()->getDistance()),
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
     * @param int|null $limit
     * @param array<int, string>|string|null $featureClass
     * @param array<int, string>|string|null $featureCode
     * @param string $isoLanguage
     * @param string $country
     * @param bool $nextPlaces
     * @return array<int, Location>
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getLocationsBySearch(
        /* Search */
        string $search,

        /* Search filter */
        array|string|null $featureClass = null,
        array|string|null $featureCode = null,
        int|null $limit = Limit::LIMIT_10,

        /* Configuration */
        string $isoLanguage = LanguageCode::EN,
        string $country = CountryCode::US,
        bool $nextPlaces = false
    ): array
    {
        /* Save search and env parameter */
        $this->update(
            isoLanguage: $isoLanguage,
            country: $country,
            nextPlaces: $nextPlaces
        );

        return [];
    }

    /**
     * Returns locations by given coordinates string (filter limit, distance, feature classes).
     *
     * @param Coordinate $coordinate
     * @param int|null $distanceMeter
     * @param array<int, string>|string|null $featureClass
     * @param array<int, string>|string|null $featureCode
     * @param int|null $limit
     * @param string $isoLanguage
     * @param string $country
     * @param bool $nextPlaces
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
    public function getLocationsByCoordinate(
        /* Search */
        Coordinate $coordinate,

        /* Search filter */
        int|null $distanceMeter = null,
        array|string|null $featureClass = null,
        array|string|null $featureCode = null,
        int|null $limit = Limit::LIMIT_10,

        /* Configuration */
        string $isoLanguage = LanguageCode::EN,
        string $country = CountryCode::US,
        bool $nextPlaces = false
    ): array
    {
        /* Save search and env parameter */
        $this->update(
            isoLanguage: $isoLanguage,
            country: $country,
            nextPlaces: $nextPlaces,
            coordinate: $coordinate
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

        }, self::PERFORMANCE_NAME_FIND_LOCATIONS, $performanceGroupName, $performanceLogger->getAdditionalData(self::class, __FUNCTION__, __LINE__));

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
     * @param string $isoLanguage
     * @param string $country
     * @param bool $nextPlaces
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
        string $isoLanguage = LanguageCode::EN,
        string $country = CountryCode::US,
        bool $nextPlaces = false
    ): Location
    {
        $location = $this->locationRepository->findOneBy(['geonameId' => $geonameId]);

        if (!$location instanceof LocationEntity) {
            $this->setError(sprintf('Unable to find location with geoname id %d', $geonameId));
            return $this->getEmptyLocation($geonameId);
        }

        $point = $location->getCoordinate();

        if (!$point instanceof Point) {
            $this->setError(sprintf('Unable to get coordinate from %d', $geonameId));
            return $this->getEmptyLocation($geonameId);
        }

        $coordinate = new Coordinate($point->getLatitude(), $point->getLongitude());

        /* Save search and env parameter */
        $this->update(
            isoLanguage: $isoLanguage,
            country: $country,
            nextPlaces: $nextPlaces,
            coordinate: $coordinate
        );

        return $this->getLocationResourceFull($location);
    }

    /**
     * Returns Location ressource by given coordinate string.
     *
     * @param Coordinate $coordinate
     * @param string $isoLanguage
     * @param string $country
     * @param bool $nextPlaces
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
        string $isoLanguage = LanguageCode::EN,
        string $country = CountryCode::US,
        bool $nextPlaces = false
    ): Location
    {
        /* Save search and env parameter */
        $this->update(
            isoLanguage: $isoLanguage,
            country: $country,
            nextPlaces: $nextPlaces,
            coordinate: $coordinate
        );

//        $adminConfiguration = $this->locationRepository->findNextAdminConfiguration(
//            coordinate: $this->coordinate,
//            featureClasses: FeatureClass::FEATURE_CLASS_P,
//            featureCodes: FeatureClass::FEATURE_CODES_P_ALL,
//        );

        $location = $this->getLocationEntityByCoordinate($coordinate);

        if (is_null($location)) {
            return $this->getEmptyLocation();
        }

        return $this->getLocationResourceFull($location);
    }
}
