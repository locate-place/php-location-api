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
 * Class GeoJsonConverter
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-16)
 * @since 0.1.0 (2023-03-16) First version.
 */
class GeoJsonConverter
{
    /** @var array<string, mixed> $zipCodeData */
    protected array $zipCodeData;

    /** @var ZipCodeFeature[] $zipCodeFeatures */
    protected array $zipCodeFeatures;

    /**
     * @param string $geoJsonPath
     */
    public function __construct(protected string $geoJsonPath)
    {
        $jsonData = file_get_contents($geoJsonPath);

        if ($jsonData === false) {
            throw new LogicException(sprintf('Could not read JSON file "%s".', $geoJsonPath));
        }

        $zipCodeData = json_decode($jsonData, true);

        if ($zipCodeData === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new LogicException(sprintf('Error reading/decoding the JSON file: %s', json_last_error_msg()));
        }

        if (!is_array($zipCodeData)) {
            throw new LogicException(sprintf('Unexpected JSON data type: %s', gettype($zipCodeData)));
        }

        $this->zipCodeData = $zipCodeData;

        foreach ($zipCodeData['features'] as $zipCodeFeature) {
            $this->zipCodeFeatures[] = new ZipCodeFeature($zipCodeFeature);
        }

        unset($jsonData);
        unset($zipCodeData);
    }

    /**
     * @return array<string, mixed>
     */
    public function getZipCodeData(): array
    {
        return $this->zipCodeData;
    }

    /**
     * @return ZipCodeFeature[]
     */
    public function getZipCodeFeatures(): array
    {
        return $this->zipCodeFeatures;
    }
}
