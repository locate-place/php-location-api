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

namespace App\DBAL\GeoLocation\Types\PostgreSQL\Base;

use App\DBAL\GeoLocation\Converter\ValueToLinestring;
use App\DBAL\GeoLocation\Converter\ValueToPoint;
use App\DBAL\GeoLocation\Converter\ValueToPolygon;
use App\DBAL\GeoLocation\ValueObject\Linestring;
use App\DBAL\GeoLocation\ValueObject\Point;
use App\DBAL\GeoLocation\ValueObject\Polygon;
use Doctrine\DBAL\Types\Type;
use Ixnode\PhpException\Type\TypeInvalidException;

/**
 * Abstract class BasePostGISType
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-22)
 * @since 0.1.0 (2024-03-22) First version.
 */
abstract class BasePostGISType extends Type
{
    /**
     * Returns the shortened name of this type.
     *
     * Examples:
     *
     * - "geometry_point" -> "geometry"
     * - "geography_point" -> "geography"
     * - "geometry_linestring" -> "geometry"
     * - "geography_linestring" -> "geography"
     * - "geometry_polygon" -> "geometry"
     * - "geography_polygon" -> "geography"
     * - etc.
     *
     * @return string
     */
    protected function getNameShorten(): string
    {
        $name = $this->getName();

        return explode('_', $name)[0];
    }

    /**
     * Returns the instantiated Point.
     *
     * @param string $value
     * @return Point
     * @throws TypeInvalidException
     */
    public function convertPointToPHPValue(string $value): Point
    {
        return (new ValueToPoint($value))->get();
    }

    /**
     * Returns the instantiated Linestring.
     *
     * @param string $value
     * @return Linestring
     * @throws TypeInvalidException
     */
    public function convertLinestringToPHPValue(string $value): Linestring
    {
        return (new ValueToLinestring($value))->get();
    }

    /**
     * Returns the instantiated Polygon.
     *
     * @param string $value
     * @return Polygon
     * @throws TypeInvalidException
     */
    public function convertPolygonToPHPValue(string $value): Polygon
    {
        return (new ValueToPolygon($value))->get();
    }
}
