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

namespace App\Constants\Timezone;

use App\Constants\Language\CountryCode;

/**
 * Class CountryCode
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-06)
 * @since 0.1.0 (2024-01-06) First version.
 */
class Timezone
{
    final public const ES = 'Europe/Madrid';
    final public const RU = 'Europe/Moscow';
    final public const US = 'America/New_York';
    final public const UA = 'Europe/Kiev';

    final public const DEFAULT = [
        CountryCode::ES => self::ES,
        CountryCode::RU => self::RU,
        CountryCode::US => self::US,
        CountryCode::UA => self::UA,
    ];
}
