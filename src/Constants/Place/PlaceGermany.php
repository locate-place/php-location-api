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
 * Class PlaceGermany
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-14)
 * @since 0.1.0 (2023-08-14) First version.
 */
class PlaceGermany
{
    final public const GERMANY_POTSDAM_CECILIENHOF_PALACE = [
        'coordinate' => [
            'latitude' => 52.419167,
            'longitude' => 13.070833,
        ],
        'location' => [
            'district-locality' => 'Nauener Vorstadt',
            'city-municipality' => 'Potsdam',
            'state' => 'Brandenburg',
            'country' => 'Federal Republic of Germany',
        ],
    ];

    final public const GERMANY_DOEBELN_BLUMENSTRASSE = [
        'coordinate' => [
            'latitude' => 51.119882,
            'longitude' => 13.132567,
        ],
        'location' => [
            'district-locality' => 'Sörmitz',
            'city-municipality' => 'Döbeln',
            'state' => 'Saxony',
            'country' => 'Federal Republic of Germany',
        ],
    ];
}
