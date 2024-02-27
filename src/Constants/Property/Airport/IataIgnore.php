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
 * Class IataIgnore
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-27)
 * @since 0.1.0 (2024-02-27) First version.
 */
class IataIgnore
{
    final public const IGNORE = [
        'BFE' => 'BFE',
        'EIB' => 'EIB',
        'GKE' => 'GKE',
        'GUT' => 'GUT',
        'IZE' => 'IZE',
        'OBF' => 'OBF',
        'QPK' => 'QPK',
        'SPM' => 'SPM',
        'WBG' => 'WBG',
        'WIE' => 'WIE',
        'ZCN' => 'ZCN',
        'ZMG' => 'ZMG',
    ];
}
