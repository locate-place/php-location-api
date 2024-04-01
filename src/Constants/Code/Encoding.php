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

namespace App\Constants\Code;

/**
 * Class Encoding
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-30)
 * @since 0.1.0 (2023-12-30) First version.
 */
class Encoding
{
    final public const ASCII = 'ASCII';

    final public const UTF_8 = 'UTF-8';

    final public const UTF_16_BE = 'UTF-16BE';

    final public const UTF_16_LE = 'UTF-16LE';

    final public const UTF_32_BE = 'UTF-32BE';

    final public const UTF_32_LE = 'UTF-32LE';

    final public const ISO_8859_1 = 'ISO-8859-1';
}
