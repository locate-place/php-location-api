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

    /**
     * Returns locations by given coordinates string (filter limit, distance, feature classes).
     *
     * @param Coordinate $coordinate
     * @param int|null $limit
     * @param int|null $distanceMeter
     * @param array<int, string>|string|null $featureClass
     * @param array<int, string>|string|null $featureCode
     * @param string $isoLanguage
     * @param string $country
     * @param bool $nextPlaces
     * @return array<int, Location>
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
        int|null $limit = Limit::LIMIT_10,
        int|null $distanceMeter = null,
        array|string|null $featureClass = null,
        array|string|null $featureCode = null,

        /* Configuration */
        string $isoLanguage = LanguageCode::EN,
        string $country = CountryCode::US,
        bool $nextPlaces = false
    ): array
    {
        /* Save search and env parameter */
        $this->update($coordinate, $isoLanguage, $country, $nextPlaces);

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
                $locations[] = $this->getLocationResourceSimple($locationEntity);
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
        $this->update($coordinate, $isoLanguage, $country, $nextPlaces);

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

        /* Configuration */
        string $isoLanguage = LanguageCode::EN,
        string $country = CountryCode::US,
        bool $nextPlaces = false
    ): Location
    {
        /* Save search and env parameter */
        $this->update($coordinate, $isoLanguage, $country, $nextPlaces);

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
