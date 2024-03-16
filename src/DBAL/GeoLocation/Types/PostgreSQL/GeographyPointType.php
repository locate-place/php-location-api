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

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Class GeographyPointType
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 */
class GeographyPointType extends PostGISPointType
{
    final public const SRID_WSG84 = 4326;

    /**
     * Returns the name of this type.
     *
     * @return string
     */
    public function getName(): string
    {
        return PostGISPointType::GEOGRAPHY;
    }

    /**
     * Converts the PHP value into an SQL statement (custom mapping).
     *
     * @param string $sqlExpr
     * @param AbstractPlatform $platform
     * @return string
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string
    {
        return sprintf('ST_GeographyFromText(%s)', $sqlExpr);
    }

    /**
     * Sets the possible default options for this column type.
     *
     * @param array{geometry_type?: string|null, srid?: int|string|null} $options
     * @return array{geometry_type: string, srid: int}
     */
    public function getNormalizedPostGISColumnOptions(array $options = []): array
    {
        $srid = (int) ($options['srid'] ?? self::SRID_WSG84);

        if ($srid === self::SRID_ZERO) {
            $srid = self::SRID_WSG84;
        }

        if (!array_key_exists('geometry_type', $options)) {
            $options['geometry_type'] = PostGISPointType::GEOGRAPHY;
        }

        $geometryType = $options['geometry_type'] ?? PostGISPointType::GEOGRAPHY;

        return [
            'geometry_type' => strtoupper((string) $geometryType),
            'srid' => $srid,
        ];
    }
}
