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
 * Class PlaceSE
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-16)
 * @since 0.1.0 (2023-08-16) First version.
 */
class PlaceSE
{
    final public const EKEROE_DROTTNINGHOLM_CASTLE = [
        KeyArray::GEONAME_ID => 2_717_199,
        KeyArray::NAME => 'Ekerö - Drottningholm Palace',
        'coordinate' => [
            'latitude' => 59.32364131453378,
            'longitude' => 17.88675328360623,
        ],
        'location' => [
            'district-locality' => 'Drottningholm',
            'city-municipality' => 'Ekerö',
            'state' => 'Stockholm',
            'country' => 'Schweden',
        ],
        'country' => CountryCode::SE,
    ];
    
    final public const LIDINGOE_BOOBOOLIVING = [
        KeyArray::GEONAME_ID => 2_678_030,
        KeyArray::NAME => 'Lidingö - Boobooliving',
        'coordinate' => [
            'latitude' => 59.346496481570604,
            'longitude' => 18.15446911303962,
        ],
        'location' => [
            'district-locality' => 'Skärsätra',
            'city-municipality' => 'Lidingö',
            'state' => 'Stockholm',
            'country' => 'Schweden',
        ],
        'country' => CountryCode::SE,
    ];

    final public const STOCKHOLM_PALACE = [
        KeyArray::GEONAME_ID => 6_942_295,
        KeyArray::NAME => 'Stockholm - Palace',
        'coordinate' => [
            'latitude' => 59.32701953293882,
            'longitude' => 18.071708793902186,
        ],
        'location' => [
            'district-locality' => 'Gamla Stan',
            'city-municipality' => 'Stockholm',
            'state' => 'Stockholm',
            'country' => 'Schweden',
        ],
        'country' => CountryCode::SE,
    ];
}
