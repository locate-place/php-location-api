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

/**
 * Class BaseLocationService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class BaseLocationService extends BaseHelperLocationService
{
    /**
     * Returns the LocationContainer.
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
    public function getLocationContainer(LocationEntity $locationEntity): LocationContainer
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
     * Returns the full location.
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
    protected function getLocationFull(LocationEntity $locationEntity, string $isoLanguage = 'en'): Location
    {
        $location = $this->getLocation($locationEntity, $this->coordinate)
            ->setLink([
                'google' => $this->coordinate->getLinkGoogle(),
                'openstreetmap' => $this->coordinate->getLinkOpenStreetMap(),
            ])
        ;

        $this->locationContainer = $this->getLocationContainer($locationEntity);

        $locationInformation = [];

        if ($this->locationContainer->hasDistrict()) {
            $locationInformation['district-locality'] = $this->locationContainer->getDistrictName($isoLanguage);
        }

        if ($this->locationContainer->hasBorough()) {
            $locationInformation['borough-locality'] = $this->locationContainer->getBoroughName($isoLanguage);
        }

        if ($this->locationContainer->hasCity()) {
            $locationInformation['city-municipality'] = $this->locationContainer->getCityName($isoLanguage);
        }

        if ($this->locationContainer->hasState()) {
            $locationInformation['state'] = $this->locationContainer->getStateName($isoLanguage);
        }

        if ($this->locationContainer->hasCountry()) {
            $locationInformation['country'] = $this->locationContainer->getCountryName($isoLanguage);
        }

        $location
            ->setLocation($locationInformation)
        ;

        return $location;
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
