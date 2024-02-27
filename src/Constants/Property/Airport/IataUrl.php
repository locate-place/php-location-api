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

namespace App\Constants\Property\Airport;

/**
 * Class IataUrl
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-27)
 * @since 0.1.0 (2024-02-27) First version.
 */
class IataUrl
{
    final public const BYU = 'https://en.wikipedia.org/wiki/Bayreuth_Airport';
    final public const FRZ = 'https://en.wikipedia.org/wiki/Fritzlar_Air_Base';
    final public const JFK = 'https://en.wikipedia.org/wiki/John_F._Kennedy_International_Airport';
    final public const ZNV = 'https://en.wikipedia.org/wiki/Koblenz-Winningen_Airport';

    final public const URL = [
        'BYU' => self::BYU,
        'FRZ' => self::FRZ,
        'JFK' => self::JFK,
        'ZNV' => self::ZNV,
    ];
}
