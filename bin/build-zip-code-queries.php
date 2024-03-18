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

use App\Utils\GeoJson\GeoJsonZipCodeConverter;

require 'vendor/autoload.php';

/**
 * File build-zip-code-queries.php
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-03-16)
 * @since 0.1.0 (2024-03-16) First version.
 * @example php bin/build-zip-code-queries.php > raw.sql
 * @example gzip raw.sql
 * @example Import with adminer.
 */

$jsonFilePath = dirname(__FILE__, 2).'/import/zip-code-area/simple-polygon.geojson';
//$jsonFilePath = dirname(__FILE__, 2).'/import/zip-code-area/simple-multi-polygon.geojson';
//$jsonFilePath = dirname(__FILE__, 2).'/import/zip-code-area/DE.geojson';

$geoJsonConverter = new GeoJsonZipCodeConverter($jsonFilePath);

$zipCodeFeatures = $geoJsonConverter->getZipCodeFeatures();

foreach ($zipCodeFeatures as $zipCodeFeature) {
    print $zipCodeFeature->getSqlQuery().PHP_EOL;
}
