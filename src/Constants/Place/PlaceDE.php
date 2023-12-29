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
 * Class PlaceDE
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-14)
 * @since 0.1.0 (2023-08-14) First version.
 */
class PlaceDE
{
    final public const BERLIN_FERNSEHTURM = [
        'coordinate' => [
            'latitude' => 52.520645,
            'longitude' => 13.409779,
        ],
        'location' => [
            'district-locality' => 'Mitte',
            'city-municipality' => 'Berlin',
            'state' => 'Land Berlin',
            'country' => 'Federal Republic of Germany'
        ],
    ];

    final public const DOEBELN_BLUMENSTRASSE = [
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

    final public const DRESDEN_FRAUENKIRCHE = [
        'coordinate' => [
            'latitude' => 51.051166462,
            'longitude' => 13.73833038,
        ],
        'location' => [
            'district-locality' => 'Innere Altstadt',
            'city-municipality' => 'Dresden',
            'state' => 'Saxony',
            'country' => 'Federal Republic of Germany',
        ],
    ];

    final public const POTSDAM_CECILIENHOF_PALACE = [
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

    final public const COLOGNE_COLOGNE_CATHEDRAL = [
        'coordinate' => [
            'latitude' => 50.941074,
            'longitude' => 6.957685,
        ],
        'location' => [
            'district-locality' => 'Altstadt Nord',
            'city-municipality' => 'Köln',
            'state' => 'Nordrhein-Westfalen',
            'country' => 'Federal Republic of Germany',
        ],
    ];
}
