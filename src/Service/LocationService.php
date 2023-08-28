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
use App\DBAL\GeoLocation\ValueObject\Point;
use App\Entity\Location as LocationEntity;
use App\Service\Base\BaseLocationService;
use Doctrine\ORM\NonUniqueResultException;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;

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

        return $this->getLocationFull($location, $isoLanguage);
    }

    /**
     * Returns Location ressource by given coordinate string.
     *
     * @param Coordinate $coordinate
     * @param string $isoLanguage
     * @return Location
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function getLocationByCoordinate(Coordinate $coordinate, string $isoLanguage = 'en'): Location
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

        return $this->getLocationFull($location, $isoLanguage);
    }

    /**
     * Returns locations by given coordinates string (filter limit, distance, feature classes).
     *
     * @param Coordinate $coordinate
     * @param int|null $limit
     * @param int|null $distance
     * @param array<int, string>|string|null $featureClass
     * @return array<int, Location>
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function getLocationsByCoordinate(Coordinate $coordinate, int|null $limit = Limit::LIMIT_10, int|null $distance = null, array|string|null $featureClass = null): array
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

            $locations[] = $this->getLocation($locationEntity, $this->coordinate);
        }

        return $locations;
    }
}
