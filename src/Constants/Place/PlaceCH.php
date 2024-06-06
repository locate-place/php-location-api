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
 * Class PlaceCH
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-16)
 * @since 0.1.0 (2023-08-16) First version.
 */
class PlaceCH
{
    final public const ZUERICH_FRIESENBERG = [
        KeyArray::GEONAME_ID => 6_295_497,
        KeyArray::NAME => 'Zürich Friesenberg',
        KeyArray::COORDINATE => [
            'latitude' => 47.36667,
            'longitude' => 8.5,
        ],
        KeyArray::LOCATION => [
            'district-locality' => 'Zürich (Kreis 3) / Friesenberg',
            'city-municipality' => 'Zürich',
            'state' => 'Zürich',
            'country' => 'Schweiz',
        ],
        KeyArray::COUNTRY => CountryCode::CH,
    ];
}
