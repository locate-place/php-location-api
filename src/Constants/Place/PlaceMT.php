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

namespace App\Constants\Place;

/**
 * Class PlaceMT
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-27)
 * @since 0.1.0 (2023-08-27) First version.
 */
class PlaceMT
{
    final public const FLORIANA_KNISJA_TA_SAN_PUBLIJU = [
        'coordinate' => [
            'latitude' => 35.89258118570912,
            'longitude' => 14.504898844625485,
        ],
        'location' => [
            'city-municipality' => 'Floriana',
            'state' => 'Southern Harbour District',
            'country' => 'Republic of Malta',
        ],
    ];

    final public const VALLETTA_MISRAH_SAN_GORG = [
        'coordinate' => [
            'latitude' => 35.89931809658259,
            'longitude' => 14.51351453878912,
        ],
        'location' => [
            'city-municipality' => 'Valletta',
            'state' => 'Southern Harbour District',
            'country' => 'Republic of Malta',
        ],
    ];
}
