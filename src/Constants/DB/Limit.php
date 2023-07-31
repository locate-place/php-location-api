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

namespace App\Constants\DB;

/**
 * Class Limit
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 */
class Limit
{
    final public const LIMIT_1 = 1;

    final public const LIMIT_5 = 5;

    final public const LIMIT_10 = 10;

    final public const LIMIT_20 = 20;

    final public const LIMIT_50 = 50;

    final public const LIMIT_100 = 100;

    final public const ALL_LIMITS = [
        self::LIMIT_1,
        self::LIMIT_5,
        self::LIMIT_10,
        self::LIMIT_20,
        self::LIMIT_50,
        self::LIMIT_100,
    ];
}
