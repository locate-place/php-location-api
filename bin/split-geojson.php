<?php

/* Number of features per file */
$batchSize = 100_000;

/* The path to the GeoJSON file. */
$geoJsonFilePath = dirname(__FILE__, 2).'/import/rivers/DE.geojson';

$valueFeatureCollection = 'FeatureCollection';

/* The output directory. */
$outputDirectory = dirname($geoJsonFilePath);

/* Load the GeoJSON data */
$geoJsonData = json_decode(file_get_contents($geoJsonFilePath), true);

/* Check whether the file is a FeatureCollection */
if ($geoJsonData['type'] !== $valueFeatureCollection) {
    echo sprintf('The file is not a "%s".', $valueFeatureCollection).PHP_EOL;
    exit(1);
}

/* Reads the features from the GeoJSON file. */
$features = $geoJsonData['features'];

/* Count the number of features in the GeoJSON file. */
$featureCount = count($features);

/* Create a directory for the split files if it does not already exist. */
if (!is_dir($outputDirectory)) {
    mkdir($outputDirectory, 0777, true);
}

/* Calculate how many files need to be created. */
$numberOfFiles = ceil($featureCount / $batchSize);

/* Divide features into batches and save them. */
for ($i = 0; $i < $numberOfFiles; $i++) {
    $start = $i * $batchSize;
    $batchFeatures = array_slice($features, $start, $batchSize);

    /* Builds the content of the GeoJSON file. */
    $batchGeoJson = [
        'type' => $valueFeatureCollection,
        'features' => $batchFeatures
    ];

    /* Create the file name */
    $outputFilename = sprintf('%s/DE-%d.geojson', $outputDirectory, $i + 1);

    /* Save the file. */
    echo sprintf('Writing file "%s"... ', $outputFilename);
    file_put_contents($outputFilename, json_encode($batchGeoJson, JSON_PRETTY_PRINT));
    echo 'done'.PHP_EOL;
}

/* Prints the success message. */
echo sprintf(
    'GeoJSON was divided into %d files, each with up to %d features.',
    $numberOfFiles,
    $batchSize
).PHP_EOL;
