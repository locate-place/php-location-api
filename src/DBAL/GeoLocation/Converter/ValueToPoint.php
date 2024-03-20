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

namespace App\DBAL\GeoLocation\Converter;

use App\DBAL\GeoLocation\Types\PostgreSQL\GeographyPolygonType;
use App\DBAL\GeoLocation\ValueObject\Point;
use Ixnode\PhpException\Type\TypeInvalidException;

/**
 * Class ValueToPoint
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-20)
 * @since 0.1.0 (2024-03-20) First version.
 */
readonly class ValueToPoint
{
    /**
     * @param string $value
     */
    public function __construct(private string $value)
    {
    }

    /**
     * @return Point
     * @throws TypeInvalidException
     */
    public function get(): Point
    {
        $resultSrid = sscanf($this->value, 'SRID=%d;POINT');

        if (is_null($resultSrid)) {
            throw new TypeInvalidException('array', 'null');
        }

        [$srid] = $resultSrid;

        if (!is_int($srid)) {
            $srid = GeographyPolygonType::SRID_WSG84;
        }

        $resultPoint = sscanf($this->value, 'POINT(%f %f)');

        if (is_null($resultPoint)) {
            throw new TypeInvalidException('array', 'null');
        }

        /* Attention: PostgreSQL uses lon/lat not lat/lon: Switch order. */
        [$longitude, $latitude, ] = $resultPoint;

        if (is_null($latitude)) {
            throw new TypeInvalidException('float', 'null');
        }

        if (is_null($longitude)) {
            throw new TypeInvalidException('float', 'null');
        }

        return new Point((float) $latitude, (float) $longitude, (int) $srid);
    }
}
