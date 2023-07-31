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
     * @return Location
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function getLocationByGeonameId(int $geonameId): Location
    {
        $location = $this->locationRepository->findOneBy(['geonameId' => $geonameId]);

        if (!$location instanceof LocationEntity) {
            $this->setError(sprintf('Unable to find location with geoname id %d', $geonameId));
            return $this->getEmptyLocation($geonameId);
        }

        return $this->getLocationFull($location);
    }

    /**
     * Returns Location ressource by given coordinate string.
     *
     * @param string $coordinate
     * @return Location
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function getLocationByCoordinate(string $coordinate): Location
    {
        $coordinateInstance = new Coordinate($coordinate);

        $location = $this->locationRepository->findNextLocationByCoordinate($coordinateInstance);

        if (!$location instanceof LocationEntity) {
            $this->setError(sprintf('Unable to find location with coordinate "%s".', $coordinate));
            return $this->getEmptyLocation();
        }

        return $this->getLocationFull($location);
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
        $coordinateInstance = new Coordinate($coordinate);

        $locationEntities = $this->locationRepository->findLocationsByCoordinate(
            $coordinateInstance,
            $distance,
            $featureClass,
            null,
            $limit
        );

        $locations = [];

        foreach ($locationEntities as $locationEntity) {
            if (!$locationEntity instanceof LocationEntity) {
                continue;
            }

            $locations[] = $this->getLocation($locationEntity, $coordinateInstance);
        }

        return $locations;
    }
}
