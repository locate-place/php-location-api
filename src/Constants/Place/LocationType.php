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

namespace App\Constants\Place;

/**
 * Class LocationType
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-05-27)
 * @since 0.1.0 (2024-05-27) First version.
 */
class LocationType
{
    final public const ADM2 = 11;

    final public const ADM3 = 12;

    final public const ADM4 = 13;

    final public const ADM5 = 14;

    final public const CITY = 20;

    final public const DISTRICT = 21;

    final public const CITY_DISTRICT = 29;

    final public const UNKNOWN = 90;
}
