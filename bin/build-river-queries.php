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

use App\Utils\GeoJson\GeoJsonRiverConverter;
use App\Utils\GeoJson\RiverFeature;

require 'vendor/autoload.php';

/**
 * File build-river-queries.php
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-03-16)
 * @since 0.1.0 (2024-03-16) First version.
 * @example php bin/split-geojson.php
 * @example php bin/build-river-queries.php import/rivers/DE-1.geojson > river.sql
 * @example php bin/build-river-queries.php import/rivers/DE-2.geojson >> river.sql
 * @example php bin/build-river-queries.php import/rivers/DE-3.geojson >> river.sql
 * @example php bin/build-river-queries.php import/rivers/DE-4.geojson >> river.sql
 * @example php bin/build-river-queries.php import/rivers/DE-5.geojson >> river.sql
 * @example gzip river.sql
 * @example Import with adminer.
 */

if ($argc < 2) {
    print sprintf('Usage: php bin/%s <path-to-geojson-file>', basename(__FILE__)).PHP_EOL;
    exit(1);
}

$jsonFilePath = $argv[1];

if (!file_exists($jsonFilePath)) {
    print sprintf('The JSON file "%s" does not exist.', $jsonFilePath).PHP_EOL;
    exit(1);
}

$geoJsonConverter = new GeoJsonRiverConverter($jsonFilePath);

$riverFeatures = $geoJsonConverter->getRiverFeatures();

/** @var RiverFeature[][] $chunks */
$chunks = array_chunk($riverFeatures, 1000);

foreach ($chunks as $chunk) {
    $queries = [];
    foreach ($chunk as $riverFeature) {
        $queries[] = $riverFeature->getSqlQuery();
    }

    if (!isset($riverFeature)) {
        throw new LogicException('No river features found.');
    }

    if (!$riverFeature instanceof RiverFeature) {
        throw new LogicException('RiverFeature must be an instance of RiverFeature.');
    }

    print $riverFeature->getSqlQueryBulk($queries).PHP_EOL;
}
