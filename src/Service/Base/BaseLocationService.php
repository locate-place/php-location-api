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
use App\Entity\AlternateName;
use App\Entity\Location as LocationEntity;
use App\Service\Base\Helper\BaseHelperLocationService;
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

        $this->isDistrictVisible = $this->locationCountryService->isDistrictVisible($locationEntity);
        $this->isBoroughVisible = $this->locationCountryService->isBoroughVisible($locationEntity);
        $this->isCityVisible = $this->locationCountryService->isCityVisible($locationEntity);

        $this->district = $this->isDistrictVisible ? $this->locationRepository->findDistrictByLocation($locationEntity, $this->coordinate) : null;
        $this->borough = $this->isBoroughVisible? $this->locationRepository->findBoroughByLocation($locationEntity, $this->coordinate) : null;
        $this->city = $this->isCityVisible ? $this->locationRepository->findCityByLocation($this->district ?: $locationEntity, $this->coordinate) : null;
        $this->state = $this->isStateVisible ? $this->locationRepository->findStateByLocation(($this->district ?: $this->city) ?: $locationEntity) : null;
        $this->country = $this->isCountryVisible ? $this->locationRepository->findCountryByLocation($this->state) : null;

        if (is_null($this->city) && !is_null($this->district)) {
            $this->city = $this->district;
            $this->district = null;
        }

        $locationInformation = [];

        if ($this->isDistrictVisible && !is_null($this->district)) {
            $locationInformation['district-locality'] = $this->getNameByIsoLanguage($this->district, $isoLanguage);
        }

        if ($this->isBoroughVisible && !is_null($this->borough)) {
            $locationInformation['borough-locality'] = $this->getNameByIsoLanguage($this->borough, $isoLanguage);
        }

        if ($this->isCityVisible && !is_null($this->city)) {
            $locationInformation['city-municipality'] = $this->getNameByIsoLanguage($this->city, $isoLanguage);
        }

        if ($this->isStateVisible && !is_null($this->state)) {
            $locationInformation['state'] = $this->getNameByIsoLanguage($this->state, $isoLanguage);
        }

        if ($this->isCountryVisible && !is_null($this->country)) {
            $locationInformation['country'] = $this->getNameByIsoLanguage($this->country, $isoLanguage);
        }

        $this->printDebug($locationEntity, $isoLanguage);

        $location
            ->setLocation($locationInformation)
        ;

        return $location;
    }

    /**
     * Returns the alternate name by given iso language.
     *
     * @param LocationEntity|null $location
     * @param string $isoLanguage
     * @return string
     */
    private function getNameByIsoLanguage(?LocationEntity $location, string $isoLanguage): string
    {
        if (is_null($location)) {
            return 'n/a';
        }

        if ($isoLanguage === 'en') {
            return (string) $location->getName();
        }

        $alternateName = $this->alternateNameRepository->findOneByIsoLanguage($location, $isoLanguage);

        if ($alternateName instanceof AlternateName) {
            $name = $alternateName->getAlternateName();

            if (!is_null($name)) {
                return $name;
            }
        }

        return (string) $location->getName();
    }

    /**
     * Prints some debug information.
     *
     * @param LocationEntity $locationSource
     * @param string $isoLanguage
     * @return void
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    protected function printDebug(LocationEntity $locationSource, string $isoLanguage): void
    {
        if (!$this->isDebug()) {
            return;
        }

        $this->printPlace($locationSource, $isoLanguage);
        $this->printFeatureClass($locationSource, FeatureClass::FEATURE_CLASS_P, $isoLanguage);
        $this->printFeatureClass($locationSource, FeatureClass::FEATURE_CLASS_A, $isoLanguage);

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
     * @param string $isoLanguage
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    protected function printPlace(LocationEntity $locationSource, string $isoLanguage): void
    {
        if (!$this->isDebug()) {
            return;
        }

        $this->output->writeln('');
        $this->printCaption();
        $this->printLocation($locationSource, 'district', $isoLanguage);

        $this->output->writeln('');
        $this->printCaption();

        if ($this->isDistrictVisible) {
            $this->printLocation($this->district, 'district', $isoLanguage);
        }

        if ($this->isBoroughVisible) {
            $this->printLocation($this->borough, 'borough', $isoLanguage);
        }

        if ($this->isCityVisible) {
            $this->printLocation($this->city, 'city', $isoLanguage);
        }

        if ($this->isStateVisible) {
            $this->printLocation($this->state,'state', $isoLanguage);
        }

        if ($this->isCountryVisible) {
            $this->printLocation($this->country, 'country', $isoLanguage);
        }
    }

    /**
     * Prints some debugging information.
     *
     * @param LocationEntity $locationSource
     * @param string $featureClass
     * @param string $isoLanguage
     * @return void
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    protected function printFeatureClass(LocationEntity $locationSource, string $featureClass, string $isoLanguage): void
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
                adminCodes: $this->locationCountryService->getAdminCodesGeneral($locationSource),
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

            $this->printLocation($location, null, $isoLanguage);
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
     * @param string $isoLanguage
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function printLocation(LocationEntity|null $location, string|null $description = null, string $isoLanguage = 'en'): void
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
            !is_null($description) => sprintf('%s (%s)', $this->getNameByIsoLanguage($location, $isoLanguage), $description),
            default => $this->getNameByIsoLanguage($location, $isoLanguage),
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
