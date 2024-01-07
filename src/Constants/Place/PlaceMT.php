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
 * Class PlaceMT
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-27)
 * @since 0.1.0 (2023-08-27) First version.
 */
class PlaceMT
{
    final public const FLORIANA_KNISJA_TA_SAN_PUBLIJU = [
        'name' => 'Floriana - Saint Publius Parish Church',
        'coordinate' => [
            'latitude' => 35.89258118570912,
            'longitude' => 14.504898844625485,
        ],
        'location' => [
            'city-municipality' => 'Floriana',
            'state' => 'Southern Harbour District',
            'country' => 'Malta',
        ],
        'country' => CountryCode::MT,
    ];

    final public const VALLETTA_MISRAH_SAN_GORG = [
        'name' => 'Valletta - Misrah San Gorg',
        'coordinate' => [
            'latitude' => 35.89931809658259,
            'longitude' => 14.51351453878912,
        ],
        'location' => [
            'city-municipality' => 'Valletta',
            'state' => 'Southern Harbour District',
            'country' => 'Malta',
        ],
        'country' => CountryCode::MT,
    ];

    final public const VICTORIA_IL_KATIDRAL_TA_GHAWDEX = [
        'name' => 'Victoria - Cathedral of the Assumption of the Blessed Virgin Mary',
        'coordinate' => [
            'latitude' => 36.046679087139104,
            'longitude' => 14.23976854681788,
        ],
        'location' => [
            'city-municipality' => 'Victoria',
            'state' => 'Gozo and Comino District',
            'country' => 'Malta',
        ],
        'country' => CountryCode::MT,
    ];
}
