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
 * Class GeoJsonRiverConverter
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-16)
 * @since 0.1.0 (2023-03-16) First version.
 */
class GeoJsonRiverConverter
{
    /** @var array<string, mixed> $riverData */
    protected array $riverData;

    /** @var RiverFeature[] $riverFeatures */
    protected array $riverFeatures;

    /**
     * @param string $geoJsonPath
     */
    public function __construct(protected string $geoJsonPath)
    {
        $jsonData = file_get_contents($geoJsonPath);

        if ($jsonData === false) {
            throw new LogicException(sprintf('Could not read JSON file "%s".', $geoJsonPath));
        }

        $riverData = json_decode($jsonData, true);

        if ($riverData === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new LogicException(sprintf('Error reading/decoding the JSON file: %s', json_last_error_msg()));
        }

        if (!is_array($riverData)) {
            throw new LogicException(sprintf('Unexpected JSON data type: %s', gettype($riverData)));
        }

        $this->riverData = $riverData;

        foreach ($riverData['features'] as $riverFeature) {
            $this->riverFeatures[] = new RiverFeature($riverFeature);
        }

        unset($jsonData);
        unset($riverData);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRiverData(): array
    {
        return $this->riverData;
    }

    /**
     * @return RiverFeature[]
     */
    public function getRiverFeatures(): array
    {
        return $this->riverFeatures;
    }
}
