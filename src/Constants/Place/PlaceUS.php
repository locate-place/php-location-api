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

use App\Constants\Language\CountryCode;

/**
 * Class PlaceUS
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-26)
 * @since 0.1.0 (2023-08-26) First version.
 */
class PlaceUS
{
    final public const AUSTIN_EAST_CESAR_CHAVEZ = [
        'name' => 'Austin - East Cesar Chavez',
        'coordinate' => [
            'latitude' => 30.26683157778637,
            'longitude' => -97.73855873989905,
        ],
        'location' => [
            'district-locality' => 'East Cesar Chavez',
            'city-municipality' => 'Austin',
            'state' => 'Texas',
            'country' => 'Vereinigte Staaten',
        ],
        'country' => CountryCode::US,
    ];

    final public const BINGHAMTON = [
        'name' => 'Binghamton',
        'coordinate' => [
            'latitude' => 42.101996,
            'longitude' => -75.920821,
        ],
        'location' => [
            'city-municipality' => 'Binghamton',
            'state' => 'New York',
            'country' => 'Vereinigte Staaten',
        ],
        'country' => CountryCode::US,
    ];

    final public const HOUSTON_EAST_DOWNTOWN = [
        'name' => 'Houston - East DownTown',
        'coordinate' => [
            'latitude' => 29.747600,
            'longitude' => -95.351568,
        ],
        'location' => [
            'district-locality' => 'East Downtown',
            'city-municipality' => 'Houston',
            'state' => 'Texas',
            'country' => 'Vereinigte Staaten',
        ],
        'country' => CountryCode::US,
    ];

    final public const NEW_YORK_BROOKLYN = [
        'name' => 'New York - Brooklyn',
        'coordinate' => [
            'latitude' => 40.703231405519865,
            'longitude' => -73.98961663598128,
        ],
        'location' => [
            'district-locality' => 'Dumbo',
            'borough-locality' => 'Brooklyn',
            'city-municipality' => 'New York City',
            'state' => 'New York',
            'country' => 'Vereinigte Staaten',
        ],
        'country' => CountryCode::US,
    ];

    final public const NEW_YORK_ONE_WORLD = [
        'name' => 'New York - One World',
        'coordinate' => [
            'latitude' => 40.71299578580626,
            'longitude' => -74.01313612224772,
        ],
        'location' => [
            'district-locality' => 'Battery Park City',
            'borough-locality' => 'Manhattan',
            'city-municipality' => 'New York City',
            'state' => 'New York',
            'country' => 'Vereinigte Staaten',
        ],
        'country' => CountryCode::US,
    ];

    final public const WASHINGTON_DC_WHITE_HOUSE = [
        'name' => 'Washington DC - White House',
        'coordinate' => [
            'latitude' => 38.89788058641667,
            'longitude' => -77.03584566370898,
        ],
        'location' => [
            'district-locality' => 'Franklin McPherson Square',
            'city-municipality' => 'Washington',
            'state' => 'Washington, D.C.',
            'country' => 'Vereinigte Staaten',
        ],
        'country' => CountryCode::US,
    ];
}
