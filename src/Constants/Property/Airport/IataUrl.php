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
    final public const AOC = 'https://en.wikipedia.org/wiki/Leipzig%E2%80%93Altenburg_Airport';
    final public const BBJ = 'https://en.wikipedia.org/wiki/Bitburg_Airport';
    final public const BYU = 'https://en.wikipedia.org/wiki/Bayreuth_Airport';
    final public const FMM = 'https://en.wikipedia.org/wiki/Memmingen_Airport';
    final public const FRZ = 'https://en.wikipedia.org/wiki/Fritzlar_Air_Base';
    final public const JFK = 'https://en.wikipedia.org/wiki/John_F._Kennedy_International_Airport';
    final public const LBC = 'https://en.wikipedia.org/wiki/L%C3%BCbeck_Airport';
    final public const QEF = 'https://en.wikipedia.org/wiki/Frankfurt_Egelsbach_Airport';
    final public const SZW = 'https://en.wikipedia.org/wiki/Parchim_International_Airport';
    final public const ZNV = 'https://en.wikipedia.org/wiki/Koblenz-Winningen_Airport';

    final public const URL = [
        'AOC' => self::AOC,
        'BBJ' => self::BBJ,
        'BYU' => self::BYU,
        'FMM' => self::FMM,
        'FRZ' => self::FRZ,
        'JFK' => self::JFK,
        'LBC' => self::LBC,
        'QEF' => self::QEF,
        'SZW' => self::SZW,
        'ZNV' => self::ZNV,
    ];
}
