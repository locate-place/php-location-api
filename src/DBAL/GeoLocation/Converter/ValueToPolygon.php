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

use App\DBAL\GeoLocation\Converter\Base\BaseValueToX;
use App\DBAL\GeoLocation\Types\PostgreSQL\GeographyType;
use App\DBAL\GeoLocation\ValueObject\Polygon;
use Ixnode\PhpException\Type\TypeInvalidException;
use LogicException;

/**
 * Class ValueToPolygon
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-16)
 * @since 0.1.0 (2024-03-16) First version.
 */
class ValueToPolygon extends BaseValueToX
{
    /**
     * @return Polygon
     * @throws TypeInvalidException
     */
    public function get(): Polygon
    {
        $result = sscanf($this->value, 'SRID=%d;POLYGON');

        if (is_null($result)) {
            throw new TypeInvalidException('array', 'null');
        }

        [$srid] = $result;

        if (!is_int($srid)) {
            $srid = GeographyType::SRID_WSG84;
        }

        if (!preg_match('/POLYGON\(\((.*?)\)\)/', $this->value, $matches)) {
            throw new LogicException('No POLYGON found in given value: ' . $this->value);
        }

        $coordinatePairs = explode(',', trim($matches[1]));

        $coordinates = $this->getCoordinates($coordinatePairs);

        return new Polygon($coordinates, $srid);
    }
}
