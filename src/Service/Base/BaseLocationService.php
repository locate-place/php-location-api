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

namespace App\Service\Base;

use App\ApiPlatform\Resource\Location;
use App\Constants\DB\FeatureClass;
use App\DBAL\GeoLocation\ValueObject\Point;
use App\Entity\Location as LocationEntity;
use App\Service\Base\Helper\BaseHelperLocationService;
use Doctrine\ORM\NonUniqueResultException;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;

/**
 * Class BaseLocationService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 */
abstract class BaseLocationService extends BaseHelperLocationService
{
    /**
     * Returns a Location entity.
     *
     * @param LocationEntity $location
     * @param Coordinate|null $coordinate
     * @return Location
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function getLocation(LocationEntity $location, Coordinate|null $coordinate): Location
    {
        $featureClass = $location->getFeatureClass()?->getClass() ?: '';
        $featureClassName = $this->translator->trans(
            $featureClass,
            domain: 'feature-code',
            locale: 'de_DE'
        );

        $featureCode = $location->getFeatureCode()?->getCode() ?: '';
        $featureCodeName = $this->translator->trans(
            sprintf('%s.%s', $featureClass, $featureCode),
            domain: 'place',
            locale: 'de_DE'
        );

        $latitude = $location->getCoordinate()?->getLatitude() ?: .0;
        $longitude = $location->getCoordinate()?->getLongitude() ?: .0;
        $srid = $location->getCoordinate()?->getSrid() ?: Point::SRID_WSG84;

        $coordinateTarget = new Coordinate($latitude, $longitude);

        $distance = is_null($coordinate) ? null : [
            'meters' => $coordinate->getDistance($coordinateTarget),
            'kilometers' => $coordinate->getDistance($coordinateTarget, Coordinate::RETURN_KILOMETERS),
        ];

        $direction = is_null($coordinate) ? null : [
            'degree' => $coordinate->getDegree($coordinateTarget),
            'direction' => $coordinate->getDirection($coordinateTarget),
        ];

        $coordinateArray = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'srid' => $srid,
        ];

        if (!is_null($distance)) {
            $coordinateArray['distance'] = $distance;
        }

        if (!is_null($direction)) {
            $coordinateArray['direction'] = $direction;
        }

        return (new Location())
            ->setGeonameId($location->getGeonameId() ?: 0)
            ->setName($location->getName() ?: '')
            ->setCountry([
                'code' => $location->getCountry()?->getCode() ?: '',
                'name' => $location->getCountry()?->getName() ?: '',
            ])
            ->setFeature([
                'class' => $featureClass,
                'class-name' => $featureClassName,
                'code' => $featureCode,
                'code-name' => $featureCodeName,
            ])
            ->setCoordinate($coordinateArray)
        ;
    }

    /**
     * Returns the full location.
     *
     * @param LocationEntity $locationEntity
     * @param Coordinate|null $coordinateSource
     * @return Location
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    protected function getLocationFull(LocationEntity $locationEntity, Coordinate|null $coordinateSource = null): Location
    {
        $latitude = $locationEntity->getCoordinate()?->getLatitude() ?: .0;
        $longitude = $locationEntity->getCoordinate()?->getLongitude() ?: .0;

        $coordinateTarget = new Coordinate($latitude, $longitude);

        $location = $this->getLocation($locationEntity, $coordinateSource)
            ->setLink([
                'google' => $coordinateTarget->getLinkGoogle(),
                'openstreetmap' => $coordinateTarget->getLinkOpenStreetMap(),
            ])
        ;

        $locationsP = $this->locationRepository->findAdminLocationsByCoordinate($coordinateTarget, null, 25);
        $locationInformation = $this->getLocationInformation($locationsP);

        if (!is_null($locationInformation)) {
            $location
                ->setLocation($locationInformation)
            ;
        }

        return $location;
    }

    /**
     * Returns some location information (district, city, state, country, etc.).
     *
     * @param array<int, LocationEntity> $locationsP
     * @return array{
     *     district-locality: string|null,
     *     city-municipality: string|null,
     *     state: string|null,
     *     country: string|null
     * }|null
     * @throws CaseUnsupportedException
     * @throws NonUniqueResultException
     * @throws TypeInvalidException
     * @throws ClassInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getLocationInformation(array $locationsP): array|null
    {
        $locationP = count($locationsP) > 0 ? $locationsP[0] : null;

        if (is_null($locationP)) {
            return null;
        }

        $featureCode = $locationP->getFeatureCode()?->getCode();

        switch (true) {
            /* Location: Add administrative information */
            case in_array($featureCode, FeatureClass::FEATURE_CODES_P_DISTRICT_PLACES):

                $district = $locationP;

                $city1 = $this->locationRepository->findCityByLocationDistrict($locationP);
                $city2 = $this->findNextAdminCity($locationsP, $district);
                $city3 = $this->findNextCityPopulation($locationsP, $district);

                $city = match (true) {
                    $city1 === null => $city2 !== null && (int) $city2->getPopulation() > 0 ? $city2 : $city3,
                    default => $city1,
                };
                break;

            /* Location: Add administrative information (Admin place) */
            case in_array($featureCode, FeatureClass::FEATURE_CODES_P_ADMIN_PLACES):
                $city = $locationP;
                $district = $this->findNextDistrict($locationsP, $city);
                break;

            default:
                throw new CaseUnsupportedException(sprintf('Unsupported FeatureCode "%s" given.', $featureCode));
        }

        /* Disable district in some cases. */
        if ($district !== null && $city !== null && $district->getName() === $city->getName()) {
            $district = null;
        }

        $state = $this->locationRepository->findStateByLocation($locationP);

        return [
            'district-locality' => $district?->getName(),
            'city-municipality' => $city?->getName(),
            'state' => $state?->getName(),
            'country' => $this->findCountry($locationP),
        ];
    }
}
