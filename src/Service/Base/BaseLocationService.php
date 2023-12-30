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
use App\Constants\Key\KeyArray;
use App\Constants\Language\Language;
use App\DBAL\GeoLocation\ValueObject\Point;
use App\Entity\Location as LocationEntity;
use App\Service\Base\Helper\BaseHelperLocationService;
use App\Service\LocationContainer;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use LogicException;

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
     * Returns the service LocationContainer (location helper class).
     *
     * @param LocationEntity $locationEntity
     * @return LocationContainer
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getServiceLocationContainer(LocationEntity $locationEntity): LocationContainer
    {
        $isDistrictVisible = $this->locationServiceConfig->isDistrictVisible($locationEntity);
        $isBoroughVisible = $this->locationServiceConfig->isBoroughVisible($locationEntity);
        $isCityVisible = $this->locationServiceConfig->isCityVisible($locationEntity);

        $district = $isDistrictVisible ? $this->locationRepository->findDistrictByLocation($locationEntity, $this->coordinate) : null;
        $borough = $isBoroughVisible? $this->locationRepository->findBoroughByLocation($locationEntity, $this->coordinate) : null;
        $city = $isCityVisible ? $this->locationRepository->findCityByLocation($district ?: $locationEntity, $this->coordinate) : null;
        $state = $this->locationRepository->findStateByLocation(($district ?: $city) ?: $locationEntity);
        $country = $this->locationRepository->findCountryByLocation($state);

        if (is_null($city) && !is_null($district)) {
            $city = $district;
            $district = null;
        }

        $locationContainer = new LocationContainer($this->locationServiceAlternateName);

        if ($isDistrictVisible && !is_null($district)) {
            $locationContainer->setDistrict($district);
        }

        if ($isBoroughVisible && !is_null($borough)) {
            $locationContainer->setBorough($borough);
        }

        if ($isCityVisible &&!is_null($city)) {
            $locationContainer->setCity($city);
        }

        if (!is_null($state)) {
            $locationContainer->setState($state);
        }

        if (!is_null($country)) {
            $locationContainer->setCountry($country);
        }

        return $locationContainer;
    }

    /**
     * Sets the service LocationContainer (location helper class).
     *
     * @param LocationEntity $locationEntity
     * @return void
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function setServiceLocationContainer(LocationEntity $locationEntity): void
    {
        $this->locationContainer = $this->getServiceLocationContainer($locationEntity);
    }

    /**
     * Returns a Location entity.
     *
     * @param LocationEntity $location
     * @param Coordinate|null $coordinate
     * @return Location
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function getLocationResourceSimple(LocationEntity $location, Coordinate|null $coordinate): Location
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

        $timezoneString = $location->getTimezone()?->getTimezone();

        if (is_null($timezoneString)) {
            throw new CaseUnsupportedException('Unable to get timezone.');
        }

        $timezone = new DateTimeZone($timezoneString);
        $dateTime = new DateTime('now', $timezone);
        $locationArray = $timezone->getLocation();

        if ($locationArray === false) {
            throw new CaseUnsupportedException('Unable to get timezone location.');
        }

        return (new Location())
            ->setGeonameId($location->getGeonameId() ?: 0)
            ->setName($location->getName() ?: '')
            ->setFeature([
                'class' => $featureClass,
                'class-name' => $featureClassName,
                'code' => $featureCode,
                'code-name' => $featureCodeName,
            ])
            ->setCoordinate($coordinateArray)
            ->setTimezone([
                'timezone' => $location->getTimezone()?->getTimezone(),
                'country' => $location->getTimezone()?->getCountry()?->getCode(),
                'current-time' => $dateTime->format('Y-m-d H:i:s'),
                'offset' => $dateTime->format('P'),
                'latitude' => $locationArray['latitude'],
                'longitude' => $locationArray['longitude'],
            ])
        ;
    }

    /**
     * Returns the full location api plattform resource.
     *
     * @param LocationEntity $locationEntity
     * @param string $isoLanguage
     * @return Location
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function getLocationResourceFull(LocationEntity $locationEntity, string $isoLanguage = Language::EN): Location
    {
        /* Adds location helper class. */
        $this->setServiceLocationContainer($locationEntity);

        /* Adds simple location api plattform resource (geoname-id, name, features and codes, coordinate, timezone, etc.). */
        $locationResource = $this->getLocationResourceSimple($locationEntity, $this->coordinate);

        /* Adds additional locations (district, borough, city, state, country, etc.). */
        $this->addLocations($locationResource, $isoLanguage);

        /* Add links (google maps, openstreetmap, etc.) */
        $this->addLinks($locationResource);

        return $locationResource;
    }

    /**
     * Adds links (Google Maps, OpenStreetMap, etc.).
     *
     * @param Location $locationResource
     * @return void
     * @throws CaseUnsupportedException
     */
    private function addLinks(Location $locationResource): void
    {
        $locationResource->addLink(KeyArray::GOOGLE, $this->coordinate->getLinkGoogle());
        $locationResource->addLink(KeyArray::OPENSTREETMAP, $this->coordinate->getLinkOpenStreetMap());
    }

    /**
     * Adds additional locations (district, borough, city, state, country, etc.).
     *
     * @param Location $locationResource
     * @param string $isoLanguage
     * @return void
     */
    private function addLocations(Location $locationResource, string $isoLanguage = Language::EN): void
    {
        $locationInformation = [];

        $this->addLocation($locationInformation, LocationContainer::TYPE_DISTRICT, $locationResource, $isoLanguage);
        $this->addLocation($locationInformation, LocationContainer::TYPE_BOROUGH, $locationResource, $isoLanguage);
        $this->addLocation($locationInformation, LocationContainer::TYPE_CITY, $locationResource, $isoLanguage);
        $this->addLocation($locationInformation, LocationContainer::TYPE_STATE, $locationResource, $isoLanguage);
        $this->addLocation($locationInformation, LocationContainer::TYPE_COUNTRY, $locationResource, $isoLanguage);

        $locationResource->setLocation($locationInformation);
    }

    /**
     * Adds a single district, borough or city (etc.) to the given location entity.
     *
     * @param array<string, mixed> $locationInformation
     * @param string $type
     * @param Location $location
     * @param string $isoLanguage
     * @return void
     */
    private function addLocation(array &$locationInformation, string $type, Location $location, string $isoLanguage = Language::EN): void
    {
        $hasElement = match ($type) {
            LocationContainer::TYPE_DISTRICT => $this->locationContainer->hasDistrict(),
            LocationContainer::TYPE_BOROUGH => $this->locationContainer->hasBorough(),
            LocationContainer::TYPE_CITY => $this->locationContainer->hasCity(),
            LocationContainer::TYPE_STATE => $this->locationContainer->hasState(),
            LocationContainer::TYPE_COUNTRY => $this->locationContainer->hasCountry(),
            default => false,
        };

        if (!$hasElement) {
            return;
        }

        $locationElement = match ($type) {
            LocationContainer::TYPE_DISTRICT => $this->locationContainer->getDistrict(),
            LocationContainer::TYPE_BOROUGH => $this->locationContainer->getBorough(),
            LocationContainer::TYPE_CITY => $this->locationContainer->getCity(),
            LocationContainer::TYPE_STATE => $this->locationContainer->getState(),
            LocationContainer::TYPE_COUNTRY => $this->locationContainer->getCountry(),
            default => null,
        };

        if (!$locationElement instanceof LocationEntity) {
            return;
        }

        $key = match ($type) {
            LocationContainer::TYPE_DISTRICT => KeyArray::DISTRICT_LOCALITY,
            LocationContainer::TYPE_BOROUGH => KeyArray::BOROUGH_LOCALITY,
            LocationContainer::TYPE_CITY => KeyArray::CITY_MUNICIPALITY,
            LocationContainer::TYPE_STATE => KeyArray::STATE,
            LocationContainer::TYPE_COUNTRY => KeyArray::COUNTRY,
            default => throw new LogicException(sprintf('Invalid type given: "%s"', $type)),
        };

        $geonameId = $locationElement->getGeonameId();
        $name = $this->locationContainer->getAlternateName($locationElement, $isoLanguage);
        $wikipediaLink = $this->locationContainer->getAlternateName($locationElement, Language::LINK);

        $locationInformation[$key] = [
            ...(is_int($geonameId) ? [KeyArray::GEONAME_ID => $geonameId] : []),
            ...(is_string($name) ? [KeyArray::NAME => $name] : []),
            ...(is_string($wikipediaLink) ? [KeyArray::WIKIPEDIA => $wikipediaLink] : []),
        ];

        if (is_string($wikipediaLink)) {
            $location->addLink([KeyArray::WIKIPEDIA, KeyArray::LOCATION, $key], $wikipediaLink);
        }
    }

    /**
     * Returns the first location by given coordinate.
     *
     * @param Coordinate $coordinate
     * @return LocationEntity|null
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    public function getLocationEntityByCoordinate(Coordinate $coordinate): LocationEntity|null
    {
        $location = $this->locationRepository->findNextLocationByCoordinate(
            coordinate: $coordinate,
            featureClasses: $this->locationServiceConfig->getLocationReferenceFeatureClass(),
            featureCodes: $this->locationServiceConfig->getLocationReferenceFeatureCodes(),
        );

        if ($location instanceof LocationEntity) {
            return $location;
        }

        $this->setError(sprintf('Unable to find location with coordinate "%s".', $coordinate->getRaw()));
        return null;
    }
}
