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

namespace App\DBAL\GeoLocation\Converter\Base;

use App\DBAL\GeoLocation\ValueObject\Point;
use Ixnode\PhpException\Type\TypeInvalidException;

/**
 * Class BaseValueToX
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-23)
 * @since 0.1.0 (2024-03-23) First version.
 */
abstract class BaseValueToX
{
    /**
     * @param string $value
     */
    public function __construct(protected string $value)
    {
    }

    /**
     * Returns coordinates from coordinate pairs.
     *
     * @param string[] $coordinatePairs
     * @return Point[]
     * @throws TypeInvalidException
     */
    public function getCoordinates(array $coordinatePairs): array
    {
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

        return $coordinates;
    }
}
