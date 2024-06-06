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

namespace App\Constants\DB;

/**
 * Class Distance (all in meter)
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 */
class Distance
{
    final public const DISTANCE_0 = 0;

    final public const DISTANCE_5 = 10;

    final public const DISTANCE_10 = 10;

    final public const DISTANCE_50 = 50;

    final public const DISTANCE_100 = 100;

    final public const DISTANCE_1000 = 1000;

    final public const DISTANCE_2000 = 2000;

    final public const DISTANCE_5000 = 5000;

    final public const DISTANCE_10000 = 10000;

    final public const DISTANCE_50000 = 50000;

    final public const DISTANCE_100000 = 100000;

    final public const ALL_DISTANCES = [
        self::DISTANCE_0,
        self::DISTANCE_5,
        self::DISTANCE_10,
        self::DISTANCE_50,
        self::DISTANCE_100,
        self::DISTANCE_1000,
        self::DISTANCE_2000,
        self::DISTANCE_5000,
        self::DISTANCE_10000,
        self::DISTANCE_50000,
        self::DISTANCE_100000,
    ];
}
