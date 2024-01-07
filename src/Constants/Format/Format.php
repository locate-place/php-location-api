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

namespace App\Constants\Format;

/**
 * Class Format
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 */
class Format
{
    final public const JSON = 'json';

    final public const HTML = 'html';

    final public const PDF = 'pdf';

    final public const PHP = 'php';



    final public const FORMATS = [
        self::JSON,
        self::HTML,
        self::PDF,
        self::PHP,
    ];
}
