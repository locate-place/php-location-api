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

namespace App\Utils\GeoJson;

use LogicException;

/**
 * Class RiverFeature
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-16)
 * @since 0.1.0 (2023-03-16) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class RiverFeature
{
    private readonly int $id;

    private readonly string $type;

    private readonly string $name;

    private float $length;

    /** @var array<int, array<int, array{latitude: float, longitude: float}>> $multipleCoordinates */
    private array $multipleCoordinates;

    private readonly int $objectId;

    private readonly string $continua;

    private readonly string $euSegCd;

    private readonly string $flowDirection;

    private readonly string $landCd;

    private readonly int $rbdCd;

    private readonly int $riverCd;

    private readonly string $scale;

    private readonly string $template;

    private readonly int $waCd;

    private readonly string $metadata;

    private const NUMBER_OF_COORDINATES = 2;

    private const SQL_INSERT_QUERY_BULK = <<<SQL
INSERT INTO river (id, country_id, type, name, length, number, object_id, continua, european_segment_code, flow_direction, country_state_code, river_basin_district_code, river_code, scale, template, work_area_code, metadata, created_at, updated_at, coordinates)
VALUES
%s
;
SQL;

    private const SQL_INSERT_QUERY = <<<SQL
(nextval('river_id_seq'), %d, '%s', '%s', %.4f, %d, %d, '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', %d, '%s', NOW(), NOW(), 'SRID=4326;LINESTRING(%s)')
SQL;

    /* WGS84 */
    final public const EARTH_RADIUS_METER = 6_371_000;

    protected const PRECISION_KILOMETERS = 3;

    /**
     * @param array<string, mixed> $riverFeature
     */
    public function __construct(array $riverFeature)
    {
        $this->id = $this->extractId($riverFeature);
        $this->type = $this->extractType($riverFeature);
        $this->name = $this->extractName($riverFeature);
        $this->length = $this->extractLength($riverFeature);
        $this->multipleCoordinates = $this->extractCoordinates($riverFeature);
        $this->objectId = $this->extractObjectId($riverFeature);
        $this->continua = $this->extractContinua($riverFeature);
        $this->euSegCd = $this->extractEuSegCd($riverFeature);
        $this->flowDirection = $this->extractFlowDirection($riverFeature);
        $this->landCd = $this->extractLandCd($riverFeature);
        $this->rbdCd = $this->extractRbdCd($riverFeature);
        $this->riverCd = $this->extractRiverCd($riverFeature);
        $this->scale = $this->extractScale($riverFeature);
        $this->template = $this->extractTemplate($riverFeature);
        $this->waCd = $this->extractWaCd($riverFeature);
        $this->metadata = $this->extractMetadata($riverFeature);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getLength(): float
    {
        return $this->length;
    }

    /**
     * @return array<int, array<int, array{latitude: float, longitude: float}>>
     */
    public function getMultipleCoordinates(): array
    {
        return $this->multipleCoordinates;
    }

    /**
     * @return int[]
     */
    public function getMultipleCoordinateIndexes(): array
    {
        return array_keys($this->multipleCoordinates);
    }

    /**
     * @return array<int, array{latitude: float, longitude: float}>
     */
    public function getCoordinates(int $index): array
    {
        if (!array_key_exists($index, $this->multipleCoordinates)) {
            throw new LogicException(sprintf('Index %d does not exist.', $index));
        }

        return $this->multipleCoordinates[$index];
    }

    /**
     * @return int
     */
    public function getObjectId(): int
    {
        return $this->objectId;
    }

    /**
     * @return string
     */
    public function getContinua(): string
    {
        return $this->continua;
    }

    /**
     * @return string
     */
    public function getEuropeanSegmentCode(): string
    {
        return $this->euSegCd;
    }

    /**
     * @return string
     */
    public function getFlowDirection(): string
    {
        return $this->flowDirection;
    }

    /**
     * @return string
     */
    public function getCountryStateCode(): string
    {
        return $this->landCd;
    }

    /**
     * @return int
     */
    public function getRiverBasinDistrictCode(): int
    {
        return $this->rbdCd;
    }

    /**
     * @return int
     */
    public function getRiverCode(): int
    {
        return $this->riverCd;
    }

    /**
     * @return string
     */
    public function getScale(): string
    {
        return $this->scale;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @return int
     */
    public function getWorkAreaCode(): int
    {
        return $this->waCd;
    }

    /**
     * @return string
     */
    public function getMetadata(): string
    {
        return $this->metadata;
    }

    /**
     * Extracts the id from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return int
     */
    private function extractId(array $riverFeature): int
    {
        if (!array_key_exists('id', $riverFeature)) {
            throw new LogicException('RiverFeature must have an id.');
        }

        $id = $riverFeature['id'];

        if (!is_int($id) && !is_string($id)) {
            throw new LogicException('RiverFeature id must be an integer or a string.');
        }

        return (int) $id;
    }

    /**
     * Extracts the type from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return string
     */
    private function extractType(array $riverFeature): string
    {
        if (!array_key_exists('type', $riverFeature)) {
            throw new LogicException('RiverFeature must have a type.');
        }

        $type = $riverFeature['type'];

        if (!is_string($type)) {
            throw new LogicException('RiverFeature type must be a string.');
        }

        if ($type !== 'Feature') {
            throw new LogicException(sprintf('Unexpected feature type: %s', $type));
        }

        return $type;
    }

    /**
     * Extracts the zip code from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return array<string, mixed>
     */
    private function extractProperties(array $riverFeature): array
    {
        if (!array_key_exists('properties', $riverFeature)) {
            throw new LogicException('RiverFeature must have a "properties" property.');
        }

        $properties = $riverFeature['properties'];

        if (!is_array($properties)) {
            throw new LogicException('RiverFeature "properties" property must be an array.');
        }

        return $properties;
    }

    /**
     * Extracts the name from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return string
     */
    private function extractName(array $riverFeature): string
    {
        $properties = $this->extractProperties($riverFeature);

        if (!array_key_exists('S_NAME', $properties)) {
            throw new LogicException('RiverFeature must have a "S_NAME" property.');
        }

        $name = $properties['S_NAME'];

        if (!is_string($name)) {
            throw new LogicException('RiverFeature "S_NAME" property must be a string.');
        }

        return $name;
    }

    /**
     * Extracts the length from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return float
     */
    private function extractLength(array $riverFeature): float
    {
        $properties = $this->extractProperties($riverFeature);

        if (!array_key_exists('LEN_CALC', $properties)) {
            throw new LogicException('RiverFeature must have a "LEN_CALC" property.');
        }

        $length = $properties['LEN_CALC'];

        if (!is_string($length) && !is_float($length) && !is_int($length)) {
            throw new LogicException('RiverFeature "LEN_CALC" property must be a float.');
        }

        return (float) $length;
    }

    /**
     * Extracts the object id from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return int
     */
    private function extractObjectId(array $riverFeature): int
    {
        $properties = $this->extractProperties($riverFeature);

        if (!array_key_exists('OBJECTID', $properties)) {
            throw new LogicException('RiverFeature must have a "OBJECTID" property.');
        }

        $objectId = $properties['OBJECTID'];

        if (!is_string($objectId) && !is_float($objectId) && !is_int($objectId)) {
            throw new LogicException('RiverFeature "OBJECTID" property must be an integer.');
        }

        return (int) $objectId;
    }

    /**
     * Extracts the continua from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return string
     */
    private function extractContinua(array $riverFeature): string
    {
        $properties = $this->extractProperties($riverFeature);

        if (!array_key_exists('CONTINUA', $properties)) {
            throw new LogicException('RiverFeature must have a "CONTINUA" property.');
        }

        $continua = $properties['CONTINUA'];

        if (!is_string($continua) && !is_float($continua) && !is_int($continua)) {
            throw new LogicException('RiverFeature "CONTINUA" property must be a string.');
        }

        return (string) $continua;
    }

    /**
     * Extracts the EU_SEG_CD from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return string
     */
    private function extractEuSegCd(array $riverFeature): string
    {
        $properties = $this->extractProperties($riverFeature);

        if (!array_key_exists('EU_SEG_CD', $properties)) {
            throw new LogicException('RiverFeature must have a "EU_SEG_CD" property.');
        }

        $euSegCd = $properties['EU_SEG_CD'];

        if (!is_string($euSegCd) && !is_float($euSegCd) && !is_int($euSegCd)) {
            throw new LogicException('RiverFeature "EU_SEG_CD" property must be a string.');
        }

        return (string) $euSegCd;
    }

    /**
     * Extracts the FLOWDIR from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return string
     */
    private function extractFlowDirection(array $riverFeature): string
    {
        $properties = $this->extractProperties($riverFeature);

        if (!array_key_exists('FLOWDIR', $properties)) {
            throw new LogicException('RiverFeature must have a "FLOWDIR" property.');
        }

        $flowDirection = $properties['FLOWDIR'];

        if (!is_string($flowDirection) && !is_float($flowDirection) && !is_int($flowDirection)) {
            throw new LogicException('RiverFeature "FLOWDIR" property must be a string.');
        }

        return (string) $flowDirection;
    }

    /**
     * Extracts the LAND_CD from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return string
     */
    private function extractLandCd(array $riverFeature): string
    {
        $properties = $this->extractProperties($riverFeature);

        if (!array_key_exists('LAND_CD', $properties)) {
            throw new LogicException('RiverFeature must have a "LAND_CD" property.');
        }

        $landCd = $properties['LAND_CD'];

        if (!is_string($landCd) && !is_float($landCd) && !is_int($landCd)) {
            throw new LogicException('RiverFeature "LAND_CD" property must be a string.');
        }

        return (string) $landCd;
    }

    /**
     * Extracts the RBD_CD from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return int
     */
    private function extractRbdCd(array $riverFeature): int
    {
        $properties = $this->extractProperties($riverFeature);

        if (!array_key_exists('RBD_CD', $properties)) {
            throw new LogicException('RiverFeature must have a "RBD_CD" property.');
        }

        $rbdCd = $properties['RBD_CD'];

        if (!is_string($rbdCd) && !is_float($rbdCd) && !is_int($rbdCd)) {
            throw new LogicException('RiverFeature "RBD_CD" property must be a string.');
        }

        return (int) $rbdCd;
    }

    /**
     * Extracts the RIVER_CD from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return int
     */
    private function extractRiverCd(array $riverFeature): int
    {
        $properties = $this->extractProperties($riverFeature);

        if (!array_key_exists('RIVER_CD', $properties)) {
            throw new LogicException('RiverFeature must have a "RIVER_CD" property.');
        }

        $riverCd = $properties['RIVER_CD'];

        if (!is_string($riverCd) && !is_float($riverCd) && !is_int($riverCd)) {
            throw new LogicException('RiverFeature "RIVER_CD" property must be a string.');
        }

        return (int) $riverCd;
    }

    /**
     * Extracts the SCALE from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return string
     */
    private function extractScale(array $riverFeature): string
    {
        $properties = $this->extractProperties($riverFeature);

        if (!array_key_exists('SCALE', $properties)) {
            throw new LogicException('RiverFeature must have a "SCALE" property.');
        }

        $scale = $properties['SCALE'];

        if (!is_string($scale) && !is_float($scale) && !is_int($scale)) {
            throw new LogicException('RiverFeature "SCALE" property must be a string.');
        }

        return (string) $scale;
    }

    /**
     * Extracts the TEMPLATE from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return string
     */
    private function extractTemplate(array $riverFeature): string
    {
        $properties = $this->extractProperties($riverFeature);

        if (!array_key_exists('TEMPLATE', $properties)) {
            throw new LogicException('RiverFeature must have a "TEMPLATE" property.');
        }

        $template = $properties['TEMPLATE'];

        if (!is_string($template) && !is_float($template) && !is_int($template)) {
            throw new LogicException('RiverFeature "TEMPLATE" property must be a string.');
        }

        return (string) $template;
    }

    /**
     * Extracts the WA_CD from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return int
     */
    private function extractWaCd(array $riverFeature): int
    {
        $properties = $this->extractProperties($riverFeature);

        if (!array_key_exists('WA_CD', $properties)) {
            throw new LogicException('RiverFeature must have a "WA_CD" property.');
        }

        $waCd = $properties['WA_CD'];

        if (!is_string($waCd) && !is_float($waCd) && !is_int($waCd)) {
            throw new LogicException('RiverFeature "WA_CD" property must be a string.');
        }

        return (int) $waCd;
    }

    /**
     * Extracts the METADATA_U from the given RiverFeature.
     *
     * @param array<string, mixed> $riverFeature
     * @return string
     */
    private function extractMetadata(array $riverFeature): string
    {
        $properties = $this->extractProperties($riverFeature);

        if (!array_key_exists('METADATA_U', $properties)) {
            throw new LogicException('RiverFeature must have a "METADATA_U" property.');
        }

        $metadata = $properties['METADATA_U'];

        if (!is_string($metadata) && !is_float($metadata) && !is_int($metadata)) {
            throw new LogicException('RiverFeature "METADATA_U" property must be a string.');
        }

        return (string) $metadata;
    }

    /**
     * Extracts the coordinates from the given RiverFeature and converts them from EPSG:3857 to EPSG:4326.
     *
     * @param array<string, mixed> $riverFeature
     * @return array<int, array<int, array{latitude: float, longitude: float}>>
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function extractCoordinates(array $riverFeature): array
    {
        if (!array_key_exists('geometry', $riverFeature)) {
            throw new LogicException('RiverFeature must have a "geometry" property.');
        }

        $geometry = $riverFeature['geometry'];

        if (!is_array($geometry)) {
            throw new LogicException('RiverFeature "geometry" property must be an array.');
        }

        if (!array_key_exists('type', $geometry)) {
            throw new LogicException('RiverFeature must have a "type" property.');
        }

        $type = (string) $geometry['type'];

        if ($type !== 'LineString' && $type !== 'MultiLineString') {
            throw new LogicException(sprintf('Unexpected geometry type: %s', $type));
        }

        if (!array_key_exists('coordinates', $geometry)) {
            throw new LogicException('RiverFeature must have a "coordinates" property.');
        }

        if (!is_array($geometry['coordinates'])) {
            throw new LogicException('RiverFeature must have a "coordinates" property that is an array.');
        }

        $coordinateBlocks = $geometry['coordinates'];

        $coordinateBlocks = match($type) {
            'LineString' => [$coordinateBlocks],
            'MultiLineString' => $coordinateBlocks,
        };

        $multipleCoordinates = [];

        $index = 0;
        foreach ($coordinateBlocks as $rawCoordinates) {
            if (!is_array($rawCoordinates)) {
                throw new LogicException('RiverFeature must have a "coordinates" property that is an array of arrays.');
            }

            $this->addCoordinates($index++, $multipleCoordinates, $rawCoordinates);
        }

        return $multipleCoordinates;
    }

    /**
     * Adds coordinates to the given multipleCoordinates array.
     *
     * @param int $index
     * @param array<int, array<int, array{latitude: float, longitude: float}>> $multipleCoordinates
     * @param array<int, mixed> $rawCoordinates
     * @return void
     */
    private function addCoordinates(int $index, array &$multipleCoordinates, array $rawCoordinates): void
    {
        $multipleCoordinates[$index] = [];

        foreach ($rawCoordinates as $rawCoordinate) {
            if (!is_array($rawCoordinate)) {
                throw new LogicException('RiverFeature must have a "coordinates" property that is an array of arrays.');
            }

            if (count($rawCoordinate) !== self::NUMBER_OF_COORDINATES) {
                throw new LogicException('RiverFeature must have a "coordinates" property that is an array of arrays.');
            }

            $latitude = $rawCoordinate[1];
            $longitude = $rawCoordinate[0];

            $multipleCoordinates[$index][] = $this->convertEpsg3857toEpsg4326($latitude, $longitude);
        }
    }

    /**
     * Converts the given latitude and longitude from EPSG:3857 to EPSG:4326 (WGS84).
     *
     * @param float $latitude
     * @param float $longitude
     * @return array{latitude: float, longitude: float}
     */
    private function convertEpsg3857toEpsg4326(float $latitude, float $longitude): array
    {
        $longitude = ($longitude * 180) / 20_037_508.34;
        $latitude = ($latitude * 180) / 20_037_508.34;

        $latitude = (atan(exp($latitude * (pi() / 180))) * 360) / pi() - 90;

        return [
            'latitude' => $latitude,
            'longitude' => $longitude
        ];
    }

    /**
     * @param float $latitudeDecimalStart
     * @param float $longitudeDecimalStart
     * @param float $latitudeDecimalEnd
     * @param float $longitudeDecimalEnd
     * @return float
     */
    private function getDistance(float $latitudeDecimalStart, float $longitudeDecimalStart, float $latitudeDecimalEnd, float $longitudeDecimalEnd): float
    {
        /* Conversion of latitude and longitude in radians. */
        $latitudeRadianStart = deg2rad($latitudeDecimalStart);
        $longitudeRadianStart = deg2rad($longitudeDecimalStart);
        $latitudeRadianEnd = deg2rad($latitudeDecimalEnd);
        $longitudeRadianEnd = deg2rad($longitudeDecimalEnd);

        /* Differences of latitudes and longitudes. */
        $longitudeDelta = $longitudeRadianEnd - $longitudeRadianStart;
        $latitudeDelta = $latitudeRadianEnd - $latitudeRadianStart;

        /* Haversine formula: https://en.wikipedia.org/wiki/Haversine_formula */
        $asinSqrt = sin($latitudeDelta / 2) ** 2 +
            sin($longitudeDelta / 2) ** 2 *
            cos($latitudeRadianStart) * cos($latitudeRadianEnd);

        $distance = 2 * self::EARTH_RADIUS_METER * asin(sqrt($asinSqrt));

        return round($distance / 1000, self::PRECISION_KILOMETERS);
    }

    /**
     * @param array<int, array{latitude: float, longitude: float}> $coordinates
     * @return float
     */
    private function calculateLength(array $coordinates): float
    {
        if (count($coordinates) <= 0) {
            return .0;
        }

        $firstCoordinate = array_shift($coordinates);

        $length = 0;

        foreach ($coordinates as $coordinate) {
            $length += $this->getDistance($firstCoordinate['latitude'], $firstCoordinate['longitude'], $coordinate['latitude'], $coordinate['longitude']);
            $firstCoordinate = $coordinate;
        }

        return $length;
    }

    /**
     * @param string[] $queries
     * @return string
     */
    public function getSqlQueryBulk(array $queries): string
    {
        return sprintf(
            self::SQL_INSERT_QUERY_BULK,
            implode(','.PHP_EOL, $queries)
        );
    }

    /**
     * Returns the insert SQL query for the given RiverFeature.
     *
     * @return string
     */
    public function getSqlQuery(): string
    {
        $countryId = 1;

        $indexes = $this->getMultipleCoordinateIndexes();

        $sqlQueries = [];

        $this->length = 0;

        foreach ($indexes as $index) {
            $length = $this->calculateLength($this->getCoordinates($index));

            $this->length += $length;
            $points = array_map(fn(array $point) => sprintf('%.12f %.12f', $point['longitude'], $point['latitude']), $this->getCoordinates($index));

            $sqlQueries[] = sprintf(
                self::SQL_INSERT_QUERY,
                $countryId,
                $this->getType(),
                str_replace("'", "''", $this->getName()),
                $length,
                $index + 1,
                $this->getObjectId(),
                $this->getContinua(),
                $this->getEuropeanSegmentCode(),
                $this->getFlowDirection(),
                $this->getCountryStateCode(),
                $this->getRiverBasinDistrictCode(),
                $this->getRiverCode(),
                $this->getScale(),
                $this->getTemplate(),
                $this->getWorkAreaCode(),
                str_replace("'", "''", $this->getMetadata()),
                implode(', ', $points),
            );
        }

        return implode(','.PHP_EOL, $sqlQueries);
    }
}
