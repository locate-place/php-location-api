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

namespace App\Constants\Place;

use App\Constants\Key\KeyArray;
use App\Constants\Language\CountryCode;

/**
 * Class PlaceDE
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-14)
 * @since 0.1.0 (2023-08-14) First version.
 */
class PlaceDE
{
    final public const BERLIN_FERNSEHTURM = [
        KeyArray::GEONAME_ID => 6_325_497,
        KeyArray::NAME => 'Berlin - Fernsehturm',
        'coordinate' => [
            'latitude' => 52.520645,
            'longitude' => 13.409779,
        ],
        'location' => [
            'district-locality' => 'Mitte',
            'city-municipality' => 'Berlin',
            'state' => 'Berlin',
            'country' => 'Deutschland'
        ],
        'country' => CountryCode::DE,
    ];

    final public const DOEBELN_BLUMENSTRASSE = [
        KeyArray::GEONAME_ID => 2_830_942,
        KeyArray::NAME => 'Döbeln - Blumenstrasse',
        'coordinate' => [
            'latitude' => 51.119882,
            'longitude' => 13.132567,
        ],
        'location' => [
            'district-locality' => 'Sörmitz',
            'city-municipality' => 'Döbeln',
            'state' => 'Sachsen',
            'country' => 'Deutschland',
        ],
        'country' => CountryCode::DE,
    ];

    final public const DRESDEN_FRAUENKIRCHE = [
        KeyArray::GEONAME_ID => 6_543_921,
        KeyArray::NAME => 'Dresden - Frauenkirche',
        'coordinate' => [
            'latitude' => 51.051166462,
            'longitude' => 13.73833038,
        ],
        'location' => [
            'district-locality' => 'Innere Altstadt',
            'city-municipality' => 'Dresden',
            'state' => 'Sachsen',
            'country' => 'Deutschland',
        ],
        'country' => CountryCode::DE,
    ];

    final public const POTSDAM_CECILIENHOF_PALACE = [
        KeyArray::GEONAME_ID => 6_488_416,
        KeyArray::NAME => 'Potsdam - Cecilienhof',
        'coordinate' => [
            'latitude' => 52.419167,
            'longitude' => 13.070833,
        ],
        'location' => [
            'district-locality' => 'Nauener Vorstadt',
            'city-municipality' => 'Potsdam',
            'state' => 'Brandenburg',
            'country' => 'Deutschland',
        ],
        'country' => CountryCode::DE,
    ];

    final public const COLOGNE_COLOGNE_CATHEDRAL = [
        KeyArray::GEONAME_ID => 6_324_464,
        KeyArray::NAME => 'Köln - Kölner Dom',
        'coordinate' => [
            'latitude' => 50.941074,
            'longitude' => 6.957685,
        ],
        'location' => [
            'district-locality' => 'Altstadt Nord',
            'city-municipality' => 'Köln',
            'state' => 'Nordrhein-Westfalen',
            'country' => 'Deutschland',
        ],
        'country' => CountryCode::DE,
    ];
}
