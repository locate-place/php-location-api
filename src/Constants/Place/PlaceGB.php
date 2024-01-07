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
 * Class PlaceGB
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-15)
 * @since 0.1.0 (2023-08-15) First version.
 */
class PlaceGB
{
    final public const AMESBURY_STONEHENGE = [
        'name' => 'Amesbury - Stonehenge',
        'coordinate' => [
            'latitude' => 51.1739726374,
            'longitude' => -1.82237671048,
        ],
        'location' => [
            'district-locality' => 'Countess',
            'city-municipality' => 'Amesbury',
            'state' => 'England',
            'country' => 'UK',
        ],
        'country' => CountryCode::GB,
    ];

    final public const EDINBURGH_LEITH = [
        'name' => 'Edinburgh - Leith',
        'coordinate' => [
            'latitude' => 55.975070,
            'longitude' => -3.158110,
        ],
        'location' => [
            'district-locality' => 'Leith',
            'city-municipality' => 'Edinburgh',
            'state' => 'Schottland',
            'country' => 'UK',
        ],
        'country' => CountryCode::GB,
    ];

    final public const LONDON_TOWER_BRIDGE = [
        'name' => 'London - Tower Bridge',
        'coordinate' => [
            'latitude' => 51.505554,
            'longitude' => -0.075278,
        ],
        'location' => [
            'district-locality' => 'London City',
            'city-municipality' => 'London',
            'state' => 'England',
            'country' => 'UK',
        ],
        'country' => CountryCode::GB,
    ];

    final public const OXFORD_SUMMERTOWN = [
        'name' => 'Oxford - Summertown',
        'coordinate' => [
            'latitude' => 51.778,
            'longitude' => -1.265,
        ],
        'location' => [
            'district-locality' => 'Summertown',
            'city-municipality' => 'Oxford',
            'state' => 'England',
            'country' => 'UK',
        ],
        'country' => CountryCode::GB,
    ];

    final public const WARWICK_UNIVERSITY = [
        'name' => 'Warwick - University',
        'coordinate' => [
            'latitude' => 52.2901301100511,
            'longitude' => -1.5553016206954282,
        ],
        'location' => [
            'district-locality' => 'Guys Cliffe',
            'city-municipality' => 'Warwick',
            'state' => 'England',
            'country' => 'UK',
        ],
        'country' => CountryCode::GB,
    ];
}
