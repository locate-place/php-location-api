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
use App\DBAL\GeoLocation\ValueObject\Linestring;
use App\DBAL\GeoLocation\ValueObject\Point;
use Ixnode\PhpException\Type\TypeInvalidException;
use LogicException;

/**
 * Class ValueToLinestring
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-19)
 * @since 0.1.0 (2024-03-19) First version.
 */
readonly class ValueToLinestring
{
    /**
     * @param string $value
     */
    public function __construct(private string $value)
    {
    }

    /**
     * @return Linestring
     * @throws TypeInvalidException
     */
    public function get(): Linestring
    {
        $result = sscanf($this->value, 'SRID=%d;LINESTRING');

        if (is_null($result)) {
            throw new TypeInvalidException('array', 'null');
        }

        [$srid] = $result;

        if (!is_int($srid)) {
            $srid = GeographyPolygonType::SRID_WSG84;
        }

        if (!preg_match('/LINESTRING\((.*?)\)/', $this->value, $matches)) {
            throw new LogicException('No LINESTRING found in given value: ' . $this->value);
        }

        $coordinatePairs = explode(',', trim($matches[1]));

        $coordinates = [];
        foreach ($coordinatePairs as $coordinatePair) {
            if (!is_string($coordinatePair)) {
                throw new TypeInvalidException('string', 'string');
            }

            $coordinatePair = trim($coordinatePair);

            $result = sscanf($coordinatePair, "%f %f");

            if (is_null($result)) {
                throw new TypeInvalidException('array', 'null');
            }

            [$longitude, $latitude] = $result;

            if (is_null($latitude)) {
                throw new TypeInvalidException('float', 'null');
            }

            if (is_null($longitude)) {
                throw new TypeInvalidException('float', 'null');
            }

            $coordinates[] = new Point($latitude, $longitude);
        }

        return new Linestring($coordinates, $srid);
    }
}
