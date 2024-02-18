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
 * Class LocaleCode
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 */
class LocaleCode
{
    final public const DE_DE = 'de_DE';

    final public const EN_GB = 'en_GB';

    final public const EN_US = 'en_US';

    final public const ES_ES = 'es_ES';



    final public const ALL = [
        self::DE_DE,
        self::EN_GB,
        self::EN_US,
        self::ES_ES,
    ];
}
