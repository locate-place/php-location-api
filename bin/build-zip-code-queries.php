<?php

use App\Utils\GeoJson\GeoJsonConverter;

require 'vendor/autoload.php';

$jsonFilePath = dirname(__FILE__, 2).'/import/zip-code-area/simple-polygon.geojson';
//$jsonFilePath = dirname(__FILE__, 2).'/import/zip-code-area/simple-multi-polygon.geojson';
//$jsonFilePath = dirname(__FILE__, 2).'/import/zip-code-area/DE.geojson';

$geoJsonConverter = new GeoJsonConverter($jsonFilePath);

$zipCodeFeatures = $geoJsonConverter->getZipCodeFeatures();

foreach ($zipCodeFeatures as $zipCodeFeature) {
    print $zipCodeFeature->getSqlQuery().PHP_EOL;
}
