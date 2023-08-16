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
use DateTime;
use DateTimeZone;
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
     * @throws \Exception
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
        $location = $this->getLocation($locationEntity, $coordinateSource)
            ->setLink([
                'google' => $this->coordinate->getLinkGoogle(),
                'openstreetmap' => $this->coordinate->getLinkOpenStreetMap(),
            ])
        ;

        $this->district = $this->locationRepository->findDistrictByLocation($locationEntity);
        $this->city = $this->locationRepository->findCityByLocation($this->district ?: $locationEntity);
        $this->state = $this->locationRepository->findStateByLocation(($this->district ?: $this->city) ?: $locationEntity);
        $this->country = $this->locationRepository->findCountryByLocation($this->state);

        if (is_null($this->city) && !is_null($this->district)) {
            $this->city = $this->district;
            $this->district = null;
        }

        $locationInformation = [
            'district-locality' => $this->district?->getName(),
            'city-municipality' => $this->city?->getName(),
            'state' => $this->state?->getName(),
            'country' => $this->country?->getName(),
        ];

        $this->printDebug($locationEntity, $this->district, $this->city, $this->state, $this->country);

        $location
            ->setLocation($locationInformation)
        ;

        return $location;
    }

    /**
     * Prints some debug information.
     *
     * @param LocationEntity $locationSource
     * @param LocationEntity|null $district
     * @param LocationEntity|null $city
     * @param LocationEntity|null $state
     * @param LocationEntity|null $country
     * @return void
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    protected function printDebug(LocationEntity $locationSource, LocationEntity|null $district, LocationEntity|null $city, LocationEntity|null $state, LocationEntity|null $country): void
    {
        if (!$this->isDebug()) {
            return;
        }

        $this->printPlace($locationSource, $district, $city, $state, $country);
        $this->printFeatureClass($locationSource, FeatureClass::FEATURE_CLASS_P);
        $this->printFeatureClass($locationSource, FeatureClass::FEATURE_CLASS_A);

        $timeExecution = microtime(true) - $this->timeStart;
        if ($this->isDebug()) {
            $this->output->writeln('');
            $this->output->writeln(sprintf('Execution time: %dms', $timeExecution * 1000));
            $this->output->writeln('');
        }
    }

    /**
     * Prints the place information.
     *
     * @param LocationEntity $locationSource
     * @param LocationEntity|null $district
     * @param LocationEntity|null $city
     * @param LocationEntity|null $state
     * @param LocationEntity|null $country
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    protected function printPlace(LocationEntity $locationSource, LocationEntity|null $district, LocationEntity|null $city, LocationEntity|null $state, LocationEntity|null $country): void
    {
        if (!$this->isDebug()) {
            return;
        }

        $this->output->writeln('');
        $this->printCaption();
        $this->printLocation($locationSource, 'district');

        $this->output->writeln('');
        $this->printCaption();
        $this->printLocation($district, 'district');
        $this->printLocation($city, 'city');
        $this->printLocation($state, 'state');
        $this->printLocation($country, 'country');
    }

    /**
     * Prints some debugging information.
     *
     * @param LocationEntity $locationSource
     * @param string $featureClass
     * @return void
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    protected function printFeatureClass(LocationEntity $locationSource, string $featureClass): void
    {
        if (!$this->isDebug()) {
            return;
        }

        $featureCodes = match ($featureClass) {
            FeatureClass::FEATURE_CLASS_A => FeatureClass::FEATURE_CODES_A_ALL,
            FeatureClass::FEATURE_CLASS_P => FeatureClass::FEATURE_CODES_P_ALL,
            default => throw new CaseUnsupportedException(sprintf('Feature class "%s" is not supported.', $featureClass)),
        };

        $locations = [];
        foreach ($featureCodes as $featureCode) {
            $featureCodeLocations = $this->locationRepository->findLocationsByCoordinate(
                coordinate: $this->coordinate,
                featureClasses: $featureClass,
                featureCodes: $featureCode,
                country: $locationSource->getCountry(),
                adminCodes: $this->locationRepository->getAdminCodesGeneral($locationSource),
                limit: $this->debugLimit,
            );

            foreach ($featureCodeLocations as $featureCodeLocation) {
                $locations[] = [
                    'location' => $featureCodeLocation,
                    'distance' => $this->coordinate->getDistance($featureCodeLocation->getCoordinateIxnode())
                ];
            }
        }

        /* Sort by distance */
        usort($locations, fn($item1, $item2) => $item1['distance'] <=> $item2['distance']);

        $this->output->writeln('');
        $this->printCaption();
        foreach ($locations as $location) {
            $location = $location['location'];

            $this->printLocation($location);
        }
    }

    /**
     * Prints the caption.
     *
     * @return void
     */
    protected function printCaption(): void
    {
        $message = sprintf(
            self::DEBUG_CAPTION,
            'Geoname',
            'FCo',
            'Distance',
            'CD',
            'Inhabitents',
            'Admin 1',
            'Admin 2',
            'Admin 3',
            'Admin 4',
            'Location',
            'Name'
        );

        $this->output->writeln($message);
        $this->output->writeln(str_repeat('-', strlen($message)));
    }

    /**
     * Prints a location to screen.
     *
     * @param LocationEntity|null $location
     * @param string|null $description
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function printLocation(LocationEntity|null $location, string|null $description = null): void
    {
        $geoNameId = $location?->getGeonameId() ?: 'n/a';
        $distanceKm = $location ? $this->coordinate->getDistance($location->getCoordinateIxnode()) / 1000 : 0;
        $direction = $location ? $this->coordinate->getDirection($location->getCoordinateIxnode()) : 0;
        $featureCode = $location?->getFeatureCode()?->getCode() ?: 'n/a';
        $distance = number_format($distanceKm, 3, ',', '.').' km';
        $inhabitants = number_format((int) $location?->getPopulation() ?: 0, 0, ',', '.');
        $adminCode1 = $location?->getAdminCode()?->getAdmin1Code() ?: 'n/a';
        $adminCode2 = $location?->getAdminCode()?->getAdmin2Code() ?: 'n/a';
        $adminCode3 = $location?->getAdminCode()?->getAdmin3Code() ?: 'n/a';
        $adminCode4 = $location?->getAdminCode()?->getAdmin4Code() ?: 'n/a';
        $position = $location?->getPosition() ?: 'n/a';
        $name = match (true) {
            !is_null($description) => sprintf('%s (%s)', $location?->getName() ?: 'n/a', $description),
            default => $location?->getName() ?: 'n/a',
        };

        $this->output->writeln(sprintf(
            self::DEBUG_CONTENT,
            $geoNameId,
            $featureCode,
            $distance,
            $direction,
            $inhabitants,
            $adminCode1,
            $adminCode2,
            $adminCode3,
            $adminCode4,
            $position,
            $name
        ));
    }
}
