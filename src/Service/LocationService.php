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
use App\Constants\Language\LanguageCode;
use App\DBAL\GeoLocation\ValueObject\Point;
use App\Entity\Location as LocationEntity;
use App\Service\Base\BaseLocationService;
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
    /**
     * Returns Location ressource by given geoname id.
     *
     * @param int $geonameId
     * @param string $isoLanguage
     * @return Location
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     */
    public function getLocationByGeonameId(int $geonameId, string $isoLanguage = 'en'): Location
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

        $this->setCoordinate(new Coordinate($point->getLatitude(), $point->getLongitude()));

        return $this->getLocationResourceFull($location, $isoLanguage);
    }

    /**
     * Returns Location ressource by given coordinate string.
     *
     * @param Coordinate $coordinate
     * @param string $isoLanguage
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
     */
    public function getLocationByCoordinate(
        Coordinate $coordinate,
        string $isoLanguage = LanguageCode::EN): Location
    {
        $this->setCoordinate($coordinate);

//        $adminConfiguration = $this->locationRepository->findNextAdminConfiguration(
//            coordinate: $this->coordinate,
//            featureClasses: FeatureClass::FEATURE_CLASS_P,
//            featureCodes: FeatureClass::FEATURE_CODES_P_ALL,
//        );

        $location = $this->getLocationEntityByCoordinate($coordinate);

        if (is_null($location)) {
            return $this->getEmptyLocation();
        }

        return $this->getLocationResourceFull($location, $isoLanguage);
    }

    /**
     * Returns locations by given coordinates string (filter limit, distance, feature classes).
     *
     * @param Coordinate $coordinate
     * @param int|null $limit
     * @param int|null $distance
     * @param array<int, string>|string|null $featureClass
     * @param string $isoLanguage
     * @return array<int, Location>
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function getLocationsByCoordinate(
        Coordinate $coordinate,
        int|null $limit = Limit::LIMIT_10,
        int|null $distance = null,
        array|string|null $featureClass = null,
        string $isoLanguage = LanguageCode::EN
    ): array
    {
        $this->setCoordinate($coordinate);

        $locationEntities = $this->locationRepository->findLocationsByCoordinate(
            coordinate: $this->coordinate,
            distanceMeter: $distance,
            featureClasses: $featureClass,
            limit: $limit
        );

        $locations = [];

        foreach ($locationEntities as $locationEntity) {
            if (!$locationEntity instanceof LocationEntity) {
                continue;
            }

            $locations[] = $this->getLocationResourceSimple($locationEntity, $isoLanguage);
        }

        return $locations;
    }
}
