<?php

/*
 * This file is part of the locate-place/php-location-api project.
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
 * Class GeoJsonConverter
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-16)
 * @since 0.1.0 (2023-03-16) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ZipCodeFeature
{
    private readonly int $id;

    private readonly string $type;

    private readonly string $zipCode;

    private readonly string $placeName;

    private readonly int $population;

    private readonly float $area;

    private readonly float $populationDensity;

    /** @var array<int, array<int, array{latitude: float, longitude: float}>> $multipleCoordinates */
    private array $multipleCoordinates;

    private const NUMBER_OF_COORDINATES = 2;

    private const SQL_INSERT_QUERY = <<<SQL
INSERT INTO zip_code_area (id, country_id, type, zip_code, place_name, number, population, area, population_density, created_at, updated_at, coordinates)
VALUES (nextval('zip_code_area_id_seq'), %d, '%s', '%s', '%s', %d, %d, %.4f, %.1f, NOW(), NOW(), 'SRID=4326;POLYGON((%s))');
SQL;

    /**
     * @param array<string, mixed> $zipCodeFeature
     */
    public function __construct(array $zipCodeFeature)
    {
        $this->id = $this->extractId($zipCodeFeature);
        $this->type = $this->extractType($zipCodeFeature);
        $this->zipCode = $this->extractZipCode($zipCodeFeature);
        $this->placeName = $this->extractPlaceName($zipCodeFeature);
        $this->population = $this->extractPopulation($zipCodeFeature);
        $this->area = $this->extractArea($zipCodeFeature);
        $this->populationDensity = $this->population / $this->area;
        $this->multipleCoordinates = $this->extractCoordinates($zipCodeFeature);
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
    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    /**
     * @return string
     */
    public function getPlaceName(): string
    {
        return $this->placeName;
    }

    /**
     * @return int
     */
    public function getPopulation(): int
    {
        return $this->population;
    }

    /**
     * @return float
     */
    public function getArea(): float
    {
        return $this->area;
    }

    /**
     * @return float
     */
    public function getPopulationDensity(): float
    {
        return $this->populationDensity;
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
     * Extracts the id from the given zipCodeFeature.
     *
     * @param array<string, mixed> $zipCodeFeature
     * @return int
     */
    private function extractId(array $zipCodeFeature): int
    {
        if (!array_key_exists('id', $zipCodeFeature)) {
            throw new LogicException('ZipCodeFeature must have an id.');
        }

        $id = $zipCodeFeature['id'];

        if (!is_int($id) && !is_string($id)) {
            throw new LogicException('ZipCodeFeature id must be an integer or a string.');
        }

        return (int) $id;
    }

    /**
     * Extracts the type from the given zipCodeFeature.
     *
     * @param array<string, mixed> $zipCodeFeature
     * @return string
     */
    private function extractType(array $zipCodeFeature): string
    {
        if (!array_key_exists('type', $zipCodeFeature)) {
            throw new LogicException('ZipCodeFeature must have a type.');
        }

        $type = $zipCodeFeature['type'];

        if (!is_string($type)) {
            throw new LogicException('ZipCodeFeature type must be a string.');
        }

        if ($type !== 'Feature') {
            throw new LogicException(sprintf('Unexpected feature type: %s', $type));
        }

        return $type;
    }

    /**
     * Extracts the zip code from the given zipCodeFeature.
     *
     * @param array<string, mixed> $zipCodeFeature
     * @return array<string, mixed>
     */
    private function extractProperties(array $zipCodeFeature): array
    {
        if (!array_key_exists('properties', $zipCodeFeature)) {
            throw new LogicException('ZipCodeFeature must have a "properties" property.');
        }

        $properties = $zipCodeFeature['properties'];

        if (!is_array($properties)) {
            throw new LogicException('ZipCodeFeature "properties" property must be an array.');
        }

        return $properties;
    }

    /**
     * Extracts the zip code from the given zipCodeFeature.
     *
     * @param array<string, mixed> $zipCodeFeature
     * @return string
     */
    private function extractZipCode(array $zipCodeFeature): string
    {
        $properties = $this->extractProperties($zipCodeFeature);

        if (!array_key_exists('plz', $properties)) {
            throw new LogicException('ZipCodeFeature must have a "plz" property.');
        }

        $zipCode = $properties['plz'];

        if (!is_string($zipCode)) {
            throw new LogicException('ZipCodeFeature "plz" property must be a string.');
        }

        return $zipCode;
    }

    /**
     * Extracts the place name from the given zipCodeFeature.
     *
     * @param array<string, mixed> $zipCodeFeature
     * @return string
     */
    private function extractPlaceName(array $zipCodeFeature): string
    {
        $properties = $this->extractProperties($zipCodeFeature);

        if (!array_key_exists('note', $properties)) {
            throw new LogicException('ZipCodeFeature must have a "note" property.');
        }

        $note = $properties['note'];

        if (!is_string($note)) {
            throw new LogicException('ZipCodeFeature "note" property must be a string.');
        }

        $zipCode = $this->getZipCode();

        /* Remove zip code from the note. */
        if (str_starts_with($note, $zipCode)) {
            $note = substr($note, strlen($zipCode));
        }

        return trim($note);
    }

    /**
     * Extracts the population from the given zipCodeFeature.
     *
     * @param array<string, mixed> $zipCodeFeature
     * @return int
     */
    private function extractPopulation(array $zipCodeFeature): int
    {
        $properties = $this->extractProperties($zipCodeFeature);

        if (!array_key_exists('einwohner', $properties)) {
            throw new LogicException('ZipCodeFeature must have a "einwohner" property.');
        }

        $population = $properties['einwohner'];

        if (!is_int($population) && !is_string($population)) {
            throw new LogicException('ZipCodeFeature einwohner must be an integer or a string.');
        }

        return (int) $population;
    }

    /**
     * Extracts the area from the given zipCodeFeature.
     *
     * @param array<string, mixed> $zipCodeFeature
     * @return float
     */
    private function extractArea(array $zipCodeFeature): float
    {
        $properties = $this->extractProperties($zipCodeFeature);

        if (!array_key_exists('qkm', $properties)) {
            throw new LogicException('ZipCodeFeature must have a "qkm" property.');
        }

        $area = $properties['qkm'];

        if (!is_int($area) && !is_string($area) && !is_float($area)) {
            throw new LogicException('ZipCodeFeature qkm must be an integer or a string.');
        }

        return (float) $area;
    }

    /**
     * Extracts the coordinates from the given zipCodeFeature and converts them from EPSG:3857 to EPSG:4326.
     *
     * @param array<string, mixed> $zipCodeFeature
     * @return array<int, array<int, array{latitude: float, longitude: float}>>
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function extractCoordinates(array $zipCodeFeature): array
    {
        if (!array_key_exists('geometry', $zipCodeFeature)) {
            throw new LogicException('ZipCodeFeature must have a "geometry" property.');
        }

        $geometry = $zipCodeFeature['geometry'];

        if (!is_array($geometry)) {
            throw new LogicException('ZipCodeFeature "geometry" property must be an array.');
        }

        if (!array_key_exists('type', $geometry)) {
            throw new LogicException('ZipCodeFeature must have a "type" property.');
        }

        $type = (string) $geometry['type'];

        if ($type !== 'Polygon' && $type !== 'MultiPolygon') {
            throw new LogicException(sprintf('Unexpected geometry type: %s', $type));
        }

        if (!array_key_exists('coordinates', $geometry)) {
            throw new LogicException('ZipCodeFeature must have a "coordinates" property.');
        }

        if (!is_array($geometry['coordinates'])) {
            throw new LogicException('ZipCodeFeature must have a "coordinates" property that is an array.');
        }

        $coordinateBlocks = $geometry['coordinates'];

        $coordinateBlocks = match($type) {
            'Polygon' => [$coordinateBlocks],
            'MultiPolygon' => $coordinateBlocks,
        };

        $multipleCoordinates = [];

        $index = 0;
        foreach ($coordinateBlocks as $coordinateBlock) {
            foreach ($coordinateBlock as $rawCoordinates) {
                if (!is_array($rawCoordinates)) {
                    throw new LogicException('ZipCodeFeature must have a "coordinates" property that is an array of arrays.');
                }

                $this->addCoordinates($index++, $multipleCoordinates, $rawCoordinates);
            }
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
                throw new LogicException('ZipCodeFeature must have a "coordinates" property that is an array of arrays.');
            }

            if (count($rawCoordinate) !== self::NUMBER_OF_COORDINATES) {
                throw new LogicException('ZipCodeFeature must have a "coordinates" property that is an array of arrays.');
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
     * Returns the insert SQL query for the given zipCodeFeature.
     *
     * @return string
     */
    public function getSqlQuery(): string
    {
        $countryId = 1;

        $indexes = $this->getMultipleCoordinateIndexes();

        $sqlQueries = [];

        foreach ($indexes as $index) {
            $points = array_map(fn(array $point) => sprintf('%.12f %.12f', $point['longitude'], $point['latitude']), $this->getCoordinates($index));

            $sqlQueries[] = sprintf(
                self::SQL_INSERT_QUERY,
                $countryId,
                $this->getType(),
                $this->getZipCode(),
                $this->getPlaceName(),
                $index + 1,
                $this->getPopulation(),
                $this->getArea(),
                $this->getPopulationDensity(),
                implode(', ', $points),
            );
        }

        return implode(PHP_EOL, $sqlQueries);
    }
}
