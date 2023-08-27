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
use App\Constants\DB\FeatureClass;
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
use JetBrains\PhpStorm\NoReturn;

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

        $this->coordinate = new Coordinate($point->getLatitude(), $point->getLongitude());

        return $this->getLocationFull($location, $isoLanguage);
    }

    /**
     * Returns Location ressource by given coordinate string.
     *
     * @param string $coordinate
     * @param string $isoLanguage
     * @return Location
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function getLocationByCoordinate(string $coordinate, string $isoLanguage = 'en'): Location
    {

        $this->coordinate = new Coordinate($coordinate);

//        $adminConfiguration = $this->locationRepository->findNextAdminConfiguration(
//            coordinate: $this->coordinate,
//            featureClasses: FeatureClass::FEATURE_CLASS_P,
//            featureCodes: FeatureClass::FEATURE_CODES_P_ALL,
//        );

        $location = $this->locationRepository->findNextLocationByCoordinate(
            coordinate: $this->coordinate,
            featureClasses: FeatureClass::FEATURE_CLASS_P,
            featureCodes: FeatureClass::FEATURE_CODES_P_ALL,
        );

        if (!$location instanceof LocationEntity) {
            $this->setError(sprintf('Unable to find location with coordinate "%s".', $coordinate));
            return $this->getEmptyLocation();
        }

        return $this->getLocationFull($location, $isoLanguage);
    }

    /**
     * Returns locations by given coordinates string (filter limit, distance, feature classes).
     *
     * @param string $coordinate
     * @param int|null $limit
     * @param int|null $distance
     * @param array<int, string>|string|null $featureClass
     * @return array<int, Location>
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function getLocationsByCoordinate(string $coordinate, int|null $limit = Limit::LIMIT_10, int|null $distance = null, array|string|null $featureClass = null): array
    {
        $this->coordinate = new Coordinate($coordinate);

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

    /**
     * Debugs a given location entity.
     *
     * @param LocationEntity $location
     * @return never
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    #[NoReturn]
    protected function debugLocation(LocationEntity $location): never
    {
        $distanceKm = $this->coordinate->getDistance($location->getCoordinateIxnode()) / 1000;

        print PHP_EOL;
        print sprintf('Name           | Value'.PHP_EOL);
        print sprintf('---------------+-------------------------------'.PHP_EOL);
        print sprintf('Name           | %s'.PHP_EOL, $location->getName());
        print sprintf('Distance       | %.2fkm'.PHP_EOL, $distanceKm);
        print sprintf('Feature Class  | %s'.PHP_EOL, $location->getFeatureClass()?->getClass() ?: 'n/a');
        print sprintf('Feature Code   | %s'.PHP_EOL, $location->getFeatureCode()?->getCode() ?: 'n/a');
        print sprintf('Admin 1        | %s'.PHP_EOL, $location->getAdminCode()?->getAdmin1Code() ?: 'n/a');
        print sprintf('Admin 2        | %s'.PHP_EOL, $location->getAdminCode()?->getAdmin2Code() ?: 'n/a');
        print sprintf('Admin 3        | %s'.PHP_EOL, $location->getAdminCode()?->getAdmin3Code() ?: 'n/a');
        print sprintf('Admin 4        | %s'.PHP_EOL, $location->getAdminCode()?->getAdmin4Code() ?: 'n/a');
        print PHP_EOL;
        exit();
    }
}
