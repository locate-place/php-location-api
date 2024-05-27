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

use App\ApiPlatform\Resource\Location as LocationResource;
use App\Constants\DB\FeatureClass;
use App\Constants\Key\KeyArray;
use App\Constants\Language\CountryCode;
use App\Constants\Language\LanguageCode;
use App\Constants\Path\Path;
use App\Constants\Timezone\Timezone as TimezoneDefault;
use App\Constants\Translation\Translation;
use App\Constants\Unit\Length;
use App\Constants\Unit\Numero;
use App\DataTypes\Coordinate;
use App\DataTypes\Feature;
use App\DataTypes\Links;
use App\DataTypes\Locations;
use App\DataTypes\NextPlaces;
use App\DataTypes\NextPlacesConfig;
use App\DataTypes\Properties;
use App\DataTypes\Timezone;
use App\DBAL\GeoLocation\ValueObject\Point;
use App\Entity\AlternateName;
use App\Entity\Location;
use App\Entity\Location as LocationEntity;
use App\Entity\ZipCode;
use App\Entity\ZipCodeArea;
use App\Service\Base\Helper\BaseHelperLocationService;
use App\Service\LocationContainer;
use App\Utils\Wikipedia\Wikipedia;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Ixnode\PhpCoordinate\Coordinate as CoordinateIxnode;
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
use LogicException;
use NumberFormatter;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class BaseLocationService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class BaseLocationService extends BaseHelperLocationService
{
    private const DEFAULT_DISTANCE_METER = 100_000;

    private const DEFAULT_LIMIT = 100;

    private const ZERO_RESULT = 0;

    private bool $useNewAdminDetectionStrategy = false;

    /**
     * @return bool
     */
    public function isUseNewAdminDetectionStrategy(): bool
    {
        return $this->useNewAdminDetectionStrategy;
    }

    /**
     * @param bool $useNewAdminDetectionStrategy
     * @return self
     */
    public function setUseNewAdminDetectionStrategy(bool $useNewAdminDetectionStrategy): self
    {
        $this->useNewAdminDetectionStrategy = $useNewAdminDetectionStrategy;

        return $this;
    }

    /**
     * Returns a Location entity.
     *
     * @param LocationEntity $locationEntity
     * @param string|null $nameFull
     * @param bool $loadLocations
     * @return LocationResource
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function getLocationResourceSimple(
        LocationEntity $locationEntity,
        string $nameFull = null,
        bool $loadLocations = false
    ): LocationResource
    {
        /* Adds location helper class. */
        $this->setServiceLocationContainer($locationEntity, loadLocations: $loadLocations);

        $location = new LocationResource();

        /* Add base information (geoname-id, name, wikipedia links, etc.) */
        $locationBaseInformation = $this->getLocationBaseInformation(
            locationEntity: $locationEntity,
            nameFull: $nameFull,
            featureDetailed: true,
            moreDetails: true
        );

        foreach ($locationBaseInformation as $key => $value) {
            match (true) {
                /* Single fields */
                $key === KeyArray::GEONAME_ID => $location->setGeonameId(is_int($value) ? $value : 0),
                $key === KeyArray::NAME => $location->setName(is_string($value) ? $value : ''),
                $key === KeyArray::NAME_FULL => $location->setNameFull(is_string($value) ? $value : ''),
                $key === KeyArray::ALTERNATE_NAMES => $location->setAlternateNames(is_array($value) ? $value : []),
                $key === KeyArray::ZIP_CODE => $location->setZipCode(is_string($value) ? $value : null),
                $key === KeyArray::UPDATED_AT => $value instanceof DateTimeImmutable ? $location->setUpdatedAt($value) : null,

                /* Complex structure */
                $value instanceof Coordinate => $location->setCoordinate($value),
                $value instanceof Feature => $location->setFeature($value),
                $value instanceof Links => $location->setLinks($value),
                $value instanceof Properties => $location->setProperties($value),
                $value instanceof Timezone => $location->setTimezone($value),
                $value instanceof NextPlacesConfig => $location->setNextPlacesConfig($value),

                /* Unknown type */
                default => throw new LogicException(sprintf('Unknown key "%s".', $key)),
            };
        }

        /* Add timezone information (timezone id, timezone name, timezone offset, etc.). */
        $location->setTimezone($this->getDataTypeTimezone($locationEntity));

        return $location;
    }

    /**
     * Returns the full location api plattform resource.
     *
     * @param LocationEntity $locationEntity
     * @return LocationResource
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    protected function getLocationResourceFull(LocationEntity $locationEntity): LocationResource
    {
        /* Adds location helper class. */
        $this->setServiceLocationContainer($locationEntity, loadLocations: true);

        /* Adds simple location api plattform resource (geoname-id, name, features and codes, coordinate, timezone, etc.). */
        $locationResource = $this->getLocationResourceSimple(
            locationEntity: $locationEntity,
            loadLocations: true
        );

        /* Adds additional locations (district, borough, city, state, country, etc.). */
        $locationResource->setLocations(
            $this->getDataTypeLocations()
        );

        /* Adds next places:
         * - A: country, state, region,...
         * - H: stream, lake, ...
         * - L: parks,area, ...
         * - P: city, village,...
         * - R: road, railroad
         * - S: spot, building, farm
         * - T: mountain,hill,rock,...
         * - U: undersea
         * - V: forest,heath,...
         */
        if ($this->isAddNextPlaces()) {
            $locationResource->setNextPlaces($this->getDataTypeNextPlaces($locationEntity));
        }

        /* Sets the full name of the location resource. */
        $locationResource->setNameFullFromLocationResource();

        /* Collects all wikipedia links and add them to the main link section. */
        $locationResource->setMainWikipediaLinks();

        return $locationResource;
    }

    /**
     * Gets all places from the given feature class.
     *
     * @param CoordinateIxnode $searchPosition Use the current position or the one of the main location.
     * @param string $featureClass
     * @param int $distanceMeter
     * @param int $limit
     * @return array<int, array<string, mixed>>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws ParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    private function getPlacesFromFeatureClass(
        CoordinateIxnode $searchPosition,
        string $featureClass,
        int $distanceMeter,
        int $limit
    ): array
    {
        $featureCodes = $this->locationServiceConfig->getFeatureCodesByFeatureClass($featureClass);

        $locations = $this->locationRepository->findLocationsByCoordinate(
            coordinate: $searchPosition,
            distanceMeter: $distanceMeter,
            featureClasses: $featureClass,
            featureCodes: $featureCodes,
            limit: $limit,
        );

        $locationArray = [];

        foreach ($locations as $location) {
            $locationArray[] = $this->getLocationBaseInformation(
                locationEntity: $location,
                searchPosition: $searchPosition,
                featureDetailed: true
            );
        }

        return $locationArray;
    }

    /**
     * Returns the coordinate data type.
     *
     * @param CoordinateIxnode|null $coordinateEntity
     * @param CoordinateIxnode|null $coordinateSource
     * @param CoordinateIxnode|null $coordinateUser
     * @param int|null $srid
     * @return Coordinate
     * @throws CaseUnsupportedException
     * @throws FunctionReplaceException
     * @throws TypeInvalidException
     */
    private function getDataTypeCoordinate(
        CoordinateIxnode|null $coordinateEntity,
        CoordinateIxnode|null $coordinateSource,
        CoordinateIxnode|null $coordinateUser,
        int|null $srid = null
    ): Coordinate
    {
        /* Creates the new Coordinate data type. */
        $coordinate = new Coordinate();

        if (is_null($coordinateEntity)) {
            return $coordinate;
        }

        $srid = match (true) {
            !is_null($srid) => $srid,
            default => Point::SRID_WSG84,
        };

        /* See: https://de.wikipedia.org/wiki/Geographische_Breite */
        $coordinate->addValue(KeyArray::LATITUDE, [
            KeyArray::DECIMAL => $coordinateEntity->getLatitudeDecimal(),
            KeyArray::DMS => $coordinateEntity->getLatitudeDMS(),
        ]);

        /* See: https://de.wikipedia.org/wiki/Geographische_L%C3%A4nge */
        $coordinate->addValue(KeyArray::LONGITUDE, [
            KeyArray::DECIMAL => $coordinateEntity->getLongitudeDecimal(),
            KeyArray::DMS => $coordinateEntity->getLongitudeDMS(),
        ]);

        /* See: https://de.wikipedia.org/wiki/SRID, https://de.wikipedia.org/wiki/World_Geodetic_System_1984, etc. */
        $coordinate->addValue(KeyArray::SRID, $srid);

        /* Add the distance and direction according to given source coordinate. */
        if (!is_null($coordinateSource)) {
            $this->addDistanceAndDirection(
                coordinate: $coordinate,
                coordinateEntity: $coordinateEntity,
                coordinateDistance: $coordinateSource,
                keyNameDistance: KeyArray::DISTANCE,
                keyNameDirection: KeyArray::DIRECTION,
            );
        }

        /* Add the distance and direction according to given user coordinate. */
        if (!is_null($coordinateUser)) {
            $this->addDistanceAndDirection(
                coordinate: $coordinate,
                coordinateEntity: $coordinateEntity,
                coordinateDistance: $coordinateUser,
                keyNameDistance: KeyArray::DISTANCE_USER,
                keyNameDirection: KeyArray::DIRECTION_USER,
            );
        }

        return $coordinate;
    }

    /**
     * Adds the distance and direction to the given coordinate.
     *
     * @param Coordinate $coordinate
     * @param CoordinateIxnode $coordinateEntity
     * @param CoordinateIxnode $coordinateDistance
     * @param string $keyNameDistance
     * @param string $keyNameDirection
     * @return void
     * @throws CaseUnsupportedException
     * @throws FunctionReplaceException
     * @throws TypeInvalidException
     */
    private function addDistanceAndDirection(
        Coordinate $coordinate,
        CoordinateIxnode $coordinateEntity,
        CoordinateIxnode $coordinateDistance,
        string $keyNameDistance,
        string $keyNameDirection
    ): void
    {
        /* Add distance. */
        $distanceMeters = $coordinateDistance->getDistance($coordinateEntity);
        $distanceKilometers = $coordinateDistance->getDistance($coordinateEntity, CoordinateIxnode::RETURN_KILOMETERS);
        $coordinate->addValue($keyNameDistance, [
            KeyArray::METERS => [
                KeyArray::VALUE => $distanceMeters,
                KeyArray::UNIT => Length::METERS_SHORT,
                KeyArray::VALUE_FORMATTED => $this->getFloatWithUnitFormatted($distanceMeters, Length::METERS_SHORT),
            ],
            KeyArray::KILOMETERS => [
                KeyArray::VALUE => $distanceKilometers,
                KeyArray::UNIT => Length::KILOMETERS_SHORT,
                KeyArray::VALUE_FORMATTED => $this->getFloatWithUnitFormatted($distanceKilometers, Length::KILOMETERS_SHORT),
            ],
        ]);

        /* Add direction. */
        $direction = $coordinateDistance->getDirection($coordinateEntity);
        $directionTranslated = $this->translateCardinalDirection($direction);
        $coordinate->addValue($keyNameDirection, [
            KeyArray::DEGREE => $coordinateDistance->getDegree($coordinateEntity),
            KeyArray::CARDINAL_DIRECTION => $direction,
            KeyArray::CARDINAL_DIRECTION_TRANSLATED => $directionTranslated,
        ]);
    }

    /**
     * Returns the feature data type.
     *
     * @param LocationEntity $locationEntity
     * @param bool $detailed
     * @return Feature
     * @throws TypeInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function getDataTypeFeature(
        LocationEntity $locationEntity,
        bool $detailed = false
    ): Feature
    {
        $feature = new Feature([]);

        $featureCode = $locationEntity->getFeatureCode()?->getCode() ?: '';
        $featureClass = $locationEntity->getFeatureClass()?->getClass() ?: '';

        $feature->addValue(KeyArray::FEATURE_CODE, $featureCode);
        $feature->addValue(KeyArray::FEATURE_CODE_NAME, $this->featureCodeService->translate($featureCode,));

        if ($detailed) {
            $feature->addValue(KeyArray::FEATURE_CLASS, $featureClass);
            $feature->addValue(KeyArray::FEATURE_CLASS_NAME, $this->featureClassService->translate($featureClass));
        }

        return $feature;
    }

    /**
     * Returns the feature data type.
     *
     * @param LocationEntity $locationEntity
     * @return Links
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws FunctionReplaceException
     * @throws ParserException
     * @throws TypeInvalidException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function getDataTypeLinks(
        LocationEntity $locationEntity
    ): Links
    {
        $links = new Links();

        /* Add google maps and open street map link. */
        $coordinateEntity = $locationEntity->getCoordinateIxnode();

        $links->addValue([KeyArray::MAPS, KeyArray::GOOGLE], $coordinateEntity->getLinkGoogle());
        $links->addValue([KeyArray::MAPS, KeyArray::OPENSTREETMAP], $coordinateEntity->getLinkOpenStreetMap());



        /* Add alternate name links. */
        $alternateNames = $this->alternateNameRepository->findByIsoLanguage($locationEntity, LanguageCode::LINK);
        if (count($alternateNames) <= self::ZERO_RESULT) {
            return $links;
        }

        $wikipedia = new Wikipedia($alternateNames);

        /* Add wikipedia link. */
        $alternateNameWikipediaLink = $wikipedia->getWikipediaLink($this->getIsoLanguage());
        if ($alternateNameWikipediaLink instanceof AlternateName) {
            $links->addValue(Path::WIKIPEDIA_THIS, $alternateNameWikipediaLink->getAlternateName());
        }

        /* Add other links. */
        $otherLinks = array_map(fn(AlternateName $alternateName) => $alternateName->getAlternateName(), $wikipedia->getAlternateNamesOther());

        if (count($otherLinks) > self::ZERO_RESULT) {
            $links->addValue(KeyArray::OTHER, $otherLinks);
        }

        return $links;
    }

    /**
     * Returns the "Locations" data type (with district, borough, city, state, country, etc.).
     *
     * @return Locations
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws ParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    private function getDataTypeLocations(): Locations
    {
        $locations = new Locations();

        foreach (LocationContainer::ALLOWED_LOCATION_TYPES as $locationType) {
            $this->addDataToLocations($locations, $locationType);
        }

        return $locations;
    }

    /**
     * Returns the "NextPlaces" data type:
     *
     * - A: country, state, region,...
     * - H: stream, lake, ...
     * - L: parks,area, ...
     * - P: city, village,...
     * - R: road, railroad
     * - S: spot, building, farm
     * - T: mountain,hill,rock,...
     * - U: undersea
     * - V: forest,heath,...
     *
     * @param LocationEntity $locationEntity
     * @return NextPlaces
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws ParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    private function getDataTypeNextPlaces(LocationEntity $locationEntity): NextPlaces
    {
        $nextPlaces = new NextPlaces();

        foreach (FeatureClass::ALL as $featureClass) {
            /* Ignore administrative locations. */
            if ($featureClass === FeatureClass::A) {
                continue;
            }

            $country = $locationEntity->getCountry()?->getCode() ?? CountryCode::DEFAULT;

            $limit = $this->locationServiceConfig->getLimit(
                featureClass: $featureClass,
                country: $country
            );
            $distanceMeter = $this->locationServiceConfig->getDistance(
                featureClass: $featureClass,
                country: $country
            );

            $this->addDataToNextPlaces(
                $nextPlaces,
                $locationEntity,
                $featureClass,
                is_null($distanceMeter) ? self::DEFAULT_DISTANCE_METER : $distanceMeter,
                $limit
            );
        }

        return $nextPlaces;
    }

    /**
     * Returns the "NextPlacesConfig" data type.
     *
     * @param array<string, mixed> $nextPlaces
     * @return NextPlacesConfig
     * @throws FunctionReplaceException
     * @throws TypeInvalidException
     */
    private function getDataTypeNextPlacesConfig(array $nextPlaces): NextPlacesConfig
    {
        $nextPlacesConfig = new NextPlacesConfig();

        $nextPlacesConfig->addValue(null, $nextPlaces);

        return $nextPlacesConfig;
    }

    /**
     * Returns the "properties" data type.
     *
     * @param LocationEntity $locationEntity
     * @return Properties
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws FunctionReplaceException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    private function getDataTypeProperties(LocationEntity $locationEntity): Properties
    {
        $properties = new Properties();

        /* Add population. */
        $population = $locationEntity->getPopulationCompiled();
        if (is_int($population)) {
            $properties->addValue(KeyArray::POPULATION, [
                KeyArray::VALUE => $population,
                KeyArray::UNIT => Numero::NUMERO_V1,
                KeyArray::VALUE_FORMATTED => $this->getNumberFormatted($population),
            ]);
        }

        /* Add elevation. */
        $elevation = $locationEntity->getElevationOverall();
        if (is_int($elevation)) {
            $properties->addValue(KeyArray::ELEVATION, [
                KeyArray::VALUE => $elevation,
                KeyArray::UNIT => Length::METERS_SHORT,
                KeyArray::VALUE_FORMATTED => $this->getFloatWithUnitFormatted($elevation, Length::METERS_SHORT),
            ]);
        }

        /* Add country. */
        $country = $locationEntity->getCountry()?->getCode();
        if (!is_null($country)) {
            $properties->addValue(KeyArray::COUNTRY, $country);
        }

        /* Add additional properties. */
        $this->addAdditionalDataTypeProperties($properties, $locationEntity);

        return $properties;
    }

    /**
     * Adds additional data type properties.
     *
     * @param Properties $properties
     * @param LocationEntity $locationEntity
     * @return void
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws FunctionReplaceException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function addAdditionalDataTypeProperties(Properties $properties, LocationEntity $locationEntity): void
    {
        $this->locationEntityHelper->setLocation($locationEntity);

        $airportCodes = $this->locationEntityHelper->getAirportCodes();
        if (!is_null($airportCodes) && count($airportCodes) > 0) {
            $properties->addValue(KeyArray::AIRPORT_CODES, $airportCodes);
        }

        $riverLength = $this->locationEntityHelper->getRiverLength();
        if (!is_null($riverLength)) {
            $properties->addValue(KeyArray::RIVER_LENGTH, $riverLength);
        }

        $propertyValues = [];

        $locationEntityProperties = $locationEntity->getProperties();

        if (count($locationEntityProperties) <= 0) {
            return;
        }

        foreach ($locationEntityProperties as $property) {
            $type = $property->getPropertyType();

            if (is_null($type)) {
                throw new LogicException('Property type is null.');
            }

            if (!array_key_exists($type, $propertyValues)) {
                $propertyValues[$type] = [];
            }

            $number = $property->getPropertyNumber();
            $name = $property->getPropertyName();
            $value = $property->getPropertyValue();

            if (is_null($name)) {
                throw new LogicException('Property type is null.');
            }

            if (is_null($number)) {
                $propertyValues[$type][$name] = $value;
                continue;
            }

            if (!array_key_exists($name, $propertyValues[$type])) {
                $propertyValues[$type][$name] = [];
            }

            $propertyValues[$type][$name][$number] = $value;
        }

        foreach ($propertyValues as $type => $values) {
            $properties->addValue($type, $values);
        }
    }

    /**
     * Returns the DateTimeZone object from given location entity.
     *
     * @param LocationEntity $locationEntity
     * @return DateTimeZone
     * @throws CaseUnsupportedException
     * @throws Exception
     */
    private function getDateTimeZone(LocationEntity $locationEntity): DateTimeZone
    {
        $timezoneString = $locationEntity->getTimezone()?->getTimezone();

        if (is_null($timezoneString)) {
            throw new CaseUnsupportedException('Unable to get timezone.');
        }

        $timezoneString = match ($timezoneString) {
            'Europe/Kyiv' => 'Europe/Kiev',
            default => $timezoneString,
        };

        if (!empty($timezoneString)) {
            return new DateTimeZone($timezoneString);
        }

        /* Use the timezone from the country code. Fuzzy. I know. */
        $countryCode = $locationEntity->getCountry()?->getCode();

        if (is_null($countryCode)) {
            throw new CaseUnsupportedException(sprintf('Bad timezone for location with geoname id "%d".', $locationEntity->getGeonameId()));
        }

        if (array_key_exists($countryCode, TimezoneDefault::DEFAULT)) {
            return new DateTimeZone(TimezoneDefault::DEFAULT[$countryCode]);
        }

        throw new CaseUnsupportedException(sprintf('Bad timezone for location with geoname id "%d".', $locationEntity->getGeonameId()));
    }

    /**
     * Returns the coordinate data type.
     *
     * @param LocationEntity $locationEntity
     * @return Timezone
     * @throws CaseUnsupportedException
     * @throws FunctionReplaceException
     * @throws ParserException
     * @throws TypeInvalidException
     * @throws Exception
     */
    private function getDataTypeTimezone(LocationEntity $locationEntity): Timezone
    {
        $dateTimeZone = $this->getDateTimeZone($locationEntity);

        $dateTime = new DateTime('now', $dateTimeZone);
        $locationArray = $dateTimeZone->getLocation();

        if ($locationArray === false) {
            throw new CaseUnsupportedException('Unable to get timezone location.');
        }

        $offset = $dateTime->format('P');
        $currentTimeTimezone = $dateTime->format('c');
        $currentTimeUtc = $dateTime->setTimezone(new DateTimeZone(CountryCode::UTC))->format('c');
        $coordinateEntity = new CoordinateIxnode($locationArray['latitude'], $locationArray['longitude']);

        $timezone = new Timezone();
        $timezone->addValue(KeyArray::TIMEZONE, $locationEntity->getTimezone()?->getTimezone());
        $timezone->addValue(KeyArray::COUNTRY, $locationEntity->getTimezone()?->getCountry()?->getCode());
        $timezone->addValue(KeyArray::CURRENT_TIME, [
            KeyArray::TIMEZONE => $currentTimeTimezone,
            KeyArray::UTC => $currentTimeUtc,
        ]);
        $timezone->addValue(KeyArray::OFFSET, $offset);
        $timezone->addValue(KeyArray::COORDINATE, $this->getDataTypeCoordinate(
            coordinateEntity: $coordinateEntity,
            coordinateSource: $this->coordinate ?? null,
            coordinateUser: $this->currentPosition ?? null,
        )->getArray());

        return $timezone;
    }

    /**
     * Adds a single district, borough or city (etc.) to the given location entity.
     *
     * @param Locations $locations
     * @param string $locationType
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws ParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function addDataToLocations(
        Locations $locations,
        string $locationType
    ): void
    {
        $hasElement = match ($locationType) {
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

        $locationEntity = match ($locationType) {
            LocationContainer::TYPE_DISTRICT => $this->locationContainer->getDistrict(),
            LocationContainer::TYPE_BOROUGH => $this->locationContainer->getBorough(),
            LocationContainer::TYPE_CITY => $this->locationContainer->getCity(),
            LocationContainer::TYPE_STATE => $this->locationContainer->getState(),
            LocationContainer::TYPE_COUNTRY => $this->locationContainer->getCountry(),
            default => null,
        };

        if (!$locationEntity instanceof LocationEntity) {
            return;
        }

        $key = $this->getLocationKey($locationType);

        $locationBaseInformation = $this->getLocationBaseInformation(
            locationEntity: $locationEntity,
            featureDetailed: true
        );

        $locations->addValue($key, $locationBaseInformation);
    }

    /**
     * Adds the NextPlaces data to the container.
     *
     * @param NextPlaces $nextPlaces
     * @param LocationEntity $locationEntity
     * @param string $featureClass
     * @param int $distanceMeter
     * @param int $limit
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws ParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    public function addDataToNextPlaces(
        NextPlaces $nextPlaces,
        LocationEntity $locationEntity,
        string $featureClass = FeatureClass::P,
        int $distanceMeter = self::DEFAULT_DISTANCE_METER,
        int $limit = self::DEFAULT_LIMIT
    ): void
    {
        $featureClassName = $this->featureClassService->translate($featureClass);

        /* Use the search coordinate or the main location as coordinate:
         * - isset($this->coordinate) means that the coordinate was given by query parameter.
         */
        $searchPosition = $this->coordinate ?? $locationEntity->getCoordinateIxnode();
        $searchPositionType = isset($this->coordinate) ? 'coordinate' : 'location';

        $nextPlacesFeatureArray = $this->getPlacesFromFeatureClass(
            searchPosition: $searchPosition,
            featureClass: $featureClass,
            distanceMeter: $distanceMeter,
            limit: $limit
        );

        /* Add config part */
        $nextPlaces->addValue([$featureClass, KeyArray::CONFIG], [
            KeyArray::DISTANCE_METER => $distanceMeter,
            KeyArray::LIMIT => $limit,
            KeyArray::COORDINATE => $this->getDataTypeCoordinate(
                coordinateEntity: $searchPosition,
                coordinateSource: null,
                coordinateUser: null,
            ),
            KeyArray::COORDINATE_TYPE => $searchPositionType,
            KeyArray::LOCATION => [
                KeyArray::NAME => $this->locationContainer->getAlternateName(
                    $locationEntity,
                    $this->getIsoLanguage()
                ),
            ],
        ]);

        /* Add feature part */
        $nextPlaces->addValue([$featureClass, KeyArray::FEATURE], [
            KeyArray::FEATURE_CLASS => $featureClass,
            KeyArray::FEATURE_CLASS_NAME => $featureClassName,
        ]);

        /* Add number part */
        $nextPlaces->addValue([$featureClass, KeyArray::PLACES_NUMBER], count($nextPlacesFeatureArray));

        /* Adds next places. */
        $nextPlaces->addValue([$featureClass, KeyArray::PLACES], $nextPlacesFeatureArray);
    }

    /**
     * Returns the base information of a location entity.
     *
     * @param LocationEntity $locationEntity
     * @param CoordinateIxnode|null $searchPosition
     * @param string|null $nameFull
     * @param bool $featureDetailed
     * @param bool $moreDetails
     * @return array<string, mixed>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws ParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getLocationBaseInformation(
        LocationEntity $locationEntity,
        CoordinateIxnode $searchPosition = null,
        string $nameFull = null,
        bool $featureDetailed = false,
        bool $moreDetails = false,
    ): array
    {
        $geonameId = $locationEntity->getGeonameId();
        $name = $this->locationContainer->getAlternateName($locationEntity, $this->getIsoLanguage());
        $updateAt = $locationEntity->getUpdatedAt();

        $zipCode = $this->getZipCode($locationEntity);
        $zipCodeString = $moreDetails ? match (true) {
            $zipCode instanceof ZipCode => $zipCode->getPostalCode(),
            $zipCode instanceof ZipCodeArea => $zipCode->getZipCode(),
            default => null,
        } : null;

        $name = match (true) {
            !is_null($name) && array_key_exists($name, Translation::TRANSLATION) => Translation::TRANSLATION[$name],
            default => $name,
        };

        /* Determine the position of coordinate to calculate the distance:
         * - $searchPosition: The main location which is shown.
         * - $this->coordinate: The given search coordinate.
         */
        $coordinateSource = match (true) {
            !is_null($searchPosition) => $searchPosition,
            isset($this->coordinate) => $this->coordinate,
            default => null,
        };

        return [
            /* Single fields. */
            ...(is_int($geonameId) ? [KeyArray::GEONAME_ID => $geonameId] : []),
            ...(is_string($name) ? [KeyArray::NAME => $name] : []),
            ...(is_string($name) ? [KeyArray::ALTERNATE_NAMES => $this->getAlternateNames($locationEntity)] : []),
            ...(is_string($nameFull) ? [KeyArray::NAME_FULL => $nameFull] : []),
            ...(!is_null($zipCodeString) ? [KeyArray::ZIP_CODE => $zipCodeString] : []),
            ...(!is_null($updateAt) ? [KeyArray::UPDATED_AT => $updateAt] : []),

            /* Complex structures. */
            KeyArray::COORDINATE => $this->getDataTypeCoordinate(
                coordinateEntity: $locationEntity->getCoordinateIxnode(),
                coordinateSource: $coordinateSource,
                coordinateUser: $this->currentPosition ?? null,
                srid: $locationEntity->getCoordinate()?->getSrid()
            ),
            KeyArray::FEATURE => $this->getDataTypeFeature($locationEntity, $featureDetailed),
            KeyArray::LINKS => $this->getDataTypeLinks($locationEntity),
            KeyArray::PROPERTIES => $this->getDataTypeProperties($locationEntity),

            /* Add next places */
            ...($this->isAddNextPlacesConfig() ? [KeyArray::NEXT_PLACES_CONFIG => $this->getDataTypeNextPlacesConfig([
                KeyArray::CONFIG => $this->locationServiceConfig->getConfigNextPlacesGroups($locationEntity->getCountry()?->getCode() ?? CountryCode::DEFAULT),
                KeyArray::ENDPOINTS => [
                    KeyArray::COORDINATE => '/api/v1/location/coordinate.json',
                    KeyArray::LIST => '/api/v1/location.json',
                ],
            ])] : [])
        ];
    }

    /**
     * Returns the first location by given coordinate.
     *
     * @param CoordinateIxnode $coordinate
     * @return LocationEntity|null
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function getLocationEntityByCoordinate(CoordinateIxnode $coordinate): LocationEntity|null
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


    /**
     * Returns the service LocationContainer (location helper class).
     *
     * @param LocationEntity $locationEntity
     * @param bool $loadLocations
     * @return LocationContainer
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @throws ORMException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getServiceLocationContainerFromLocationRepository(
        LocationEntity $locationEntity,
        bool $loadLocations = false
    ): LocationContainer
    {
        $this->setUseNewAdminDetectionStrategy(false);

        $id = $locationEntity->getId();

        if (is_null($id)) {
            throw new LogicException('Given Location entity must have an ID.');
        }

        if (array_key_exists($id, $this->locationContainerHolder)) {
            return $this->locationContainerHolder[$id];
        }

        $locationContainer = new LocationContainer($this->locationServiceAlternateName);

        $this->locationContainerHolder[$id] = $locationContainer;

        if (!$loadLocations) {
            return $locationContainer;
        }

        $isDistrictVisible = $this->locationServiceConfig->isDistrictVisible($locationEntity);
        $isBoroughVisible = $this->locationServiceConfig->isBoroughVisible($locationEntity);
        $isCityVisible = $this->locationServiceConfig->isCityVisible($locationEntity);

        /* Try to get the current position or use the coordinate from the location entity. */
        $coordinate = match (true) {
            isset($this->coordinate) => $this->coordinate,
            default => $locationEntity->getCoordinateIxnode(),
        };

        /* Find the next place if other feature class than A or P was found. */
        $locationEntityPlace = match ($locationEntity->getFeatureClass()?->getClass()) {
            FeatureClass::A, FeatureClass::P => $locationEntity,
            default => $this->locationRepository->findNextLocationByCoordinate(
                coordinate: $coordinate,
                featureClasses: $this->locationServiceConfig->getLocationReferenceFeatureClass(),
                featureCodes: $this->locationServiceConfig->getLocationReferenceFeatureCodes(),
                country: $locationEntity->getCountry(),
            ),
        };

        if (is_null($locationEntityPlace)) {
            throw new LogicException('Unable to find next place for given location entity.');
        }

        /* Try to find the admin areas according to given strategy. */
        switch ($this->isUseNewAdminDetectionStrategy()) {
            /* Use the NEW admin detection strategy. */
            case true:
                $adminAreas = $this->locationRepository->findAdminAreas(
                    $locationEntityPlace,
                    $coordinate
                );

                $district = $isDistrictVisible && array_key_exists(KeyArray::DISTRICT_LOCALITY, $adminAreas) ?
                    $adminAreas[KeyArray::DISTRICT_LOCALITY] : null;
//                $borough = $isBoroughVisible && array_key_exists(KeyArray::BOROUGH_LOCALITY, $adminAreas) ?
//                    $adminAreas[KeyArray::BOROUGH_LOCALITY] : null;
                $borough = null;
                $city = $isCityVisible && array_key_exists(KeyArray::CITY_MUNICIPALITY, $adminAreas) ?
                    $adminAreas[KeyArray::CITY_MUNICIPALITY] : null;
                break;

            /* Use the OLD admin detection strategy. */
            default:
                $district = $isDistrictVisible ? $this->locationRepository->findDistrictByLocation($locationEntityPlace, $coordinate) : null;
                $borough = $isBoroughVisible? $this->locationRepository->findBoroughByLocation($locationEntityPlace, $coordinate) : null;
                $city = $isCityVisible ? $this->locationRepository->findCityByLocation($district ?: $locationEntityPlace, $coordinate) : null;
                break;
        }

        $state = $this->locationRepository->findStateByLocation(($district ?: $city) ?: $locationEntityPlace);
        $country = $this->locationRepository->findCountryByLocation($state);

        if (is_null($city) && !is_null($district)) {
            $city = $district;
            $district = null;
        }

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
     * @param bool $loadLocations
     * @return void
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function setServiceLocationContainer(
        LocationEntity $locationEntity,
        bool $loadLocations = false
    ): void
    {
        $this->locationContainer = $this->getServiceLocationContainerFromLocationRepository(
            $locationEntity,
            loadLocations: $loadLocations
        );
    }

    /**
     * Returns the locale from given language code.
     *
     * @return string
     */
    private function getLocale(): string
    {
        return sprintf('%s_%s', $this->getIsoLanguage(), $this->getCountry());
    }

    /**
     * Returns the formatted number.
     *
     * @param int $value
     * @return string
     */
    private function getNumberFormatted(int $value): string
    {
        $numberFormatted = (new NumberFormatter($this->getLocale(), NumberFormatter::DEFAULT_STYLE))
            ->format($value);

        if ($numberFormatted === false) {
            throw new LogicException(sprintf('Unable to format number "%d".', $value));
        }

        return $numberFormatted;
    }

    /**
     * Returns the formatted number.
     *
     * @param float $value
     * @param string|null $unit
     * @return string
     */
    private function getFloatWithUnitFormatted(float $value, string|null $unit = null): string
    {
        return (new NumberFormatter($this->getLocale(), NumberFormatter::DEFAULT_STYLE))
            ->format($value).(!is_null($unit) ? ' '.$unit : '');
    }

    /**
     * Translates the given short cardinal direction into a long one.
     *
     * @param string $cardinalDirection
     * @return string
     */
    private function translateCardinalDirection(string $cardinalDirection): string
    {
        return match ($cardinalDirection) {
            'N' => 'North',
            'NE' => 'North-East',
            'E' => 'East',
            'SE' => 'South-East',
            'S' => 'South',
            'SW' => 'South-West',
            'W' => 'West',
            'NW' => 'North-West',
            default => throw new LogicException(sprintf('Unexpected cardinal direction given "%s".', $cardinalDirection)),
        };
    }

    /**
     * Returns the location key from the given location type.
     *
     * @param string $locationType
     * @return string
     */
    private function getLocationKey(string $locationType): string
    {
        return match ($locationType) {
            LocationContainer::TYPE_DISTRICT => KeyArray::DISTRICT_LOCALITY,
            LocationContainer::TYPE_BOROUGH => KeyArray::BOROUGH_LOCALITY,
            LocationContainer::TYPE_CITY => KeyArray::CITY_MUNICIPALITY,
            LocationContainer::TYPE_STATE => KeyArray::STATE,
            LocationContainer::TYPE_COUNTRY => KeyArray::COUNTRY,
            default => throw new LogicException(sprintf('Invalid location type given: "%s"', $locationType)),
        };
    }

    /**
     * Return de, en, es alternate names.
     *
     * @param LocationEntity $locationEntity
     * @return string[]
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    private function getAlternateNames(Location $locationEntity): array
    {
        $alternateNames = [];

        foreach ($this->alternateNameRepository->findByIsoLanguagesWithNull($locationEntity, ['de', 'en', 'es']) as $alternateName) {
            $name = $alternateName->getAlternateName();

            if (is_null($name)) {
                continue;
            }

            $alternateNames[] = $name;
        }

        $alternateNames = array_unique($alternateNames);

        return array_values($alternateNames);
    }
}
