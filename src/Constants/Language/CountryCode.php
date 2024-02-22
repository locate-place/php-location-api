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

namespace App\Constants\Language;

/**
 * Class CountryCode
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-06)
 * @since 0.1.0 (2024-01-06) First version.
 */
class CountryCode
{
    final public const CH = 'CH';

    final public const DE = 'DE';

    final public const ES = 'ES';

    final public const GB = 'GB';

    final public const MT = 'MT';

    final public const RU = 'RU';

    final public const SE = 'SE';

    final public const UA = 'UA';

    final public const US = 'US';

    final public const UTC = 'UTC';

    final public const DEFAULT = 'default';

    final public const ALL = [
        self::DE,
        self::GB,
        self::US
    ];
}
