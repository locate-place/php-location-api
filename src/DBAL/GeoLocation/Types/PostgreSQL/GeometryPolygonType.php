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

namespace App\DBAL\GeoLocation\Types\PostgreSQL;

/**
 * Class GeometryPolygonType
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-16)
 * @since 0.1.0 (2024-03-16) First version.
 */
class GeometryPolygonType extends PostGISPolygonType
{
    /**
     * Returns the name of this type.
     *
     * @return string
     */
    public function getName(): string
    {
        return PostGISPolygonType::GEOMETRY;
    }

    /**
     * Sets the possible default options for this column type.
     *
     * @param array{geometry_type?: string|null, srid?: int|string|null} $options
     * @return array{geometry_type: string, srid: int}
     */
    public function getNormalizedPostGISColumnOptions(array $options = []): array
    {
        if (!array_key_exists('geometry_type', $options)) {
            $options['geometry_type'] = PostGISPolygonType::GEOGRAPHY;
        }

        $geometryType = $options['geometry_type'] ?? PostGISPolygonType::GEOGRAPHY;

        return [
            'geometry_type' => strtoupper((string) $geometryType),
            'srid' => (int) ($options['srid'] ?? self::SRID_ZERO),
        ];
    }
}
