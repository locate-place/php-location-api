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
 * Class Location
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-12-04)
 * @since 0.1.0 (2024-12-04) First version.
 */
class Location
{
    final public const DISTRICT_LOCALITY = 'district-locality';

    final public const BOROUGH_LOCALITY = 'borough-locality';

    final public const CITY_MUNICIPALITY = 'city-municipality';

    final public const STATE ='state';

    final public const COUNTRY = 'country';



    final public const ALL = [
        self::DISTRICT_LOCALITY,
        self::BOROUGH_LOCALITY,
        self::CITY_MUNICIPALITY,
        self::STATE,
        self::COUNTRY,
    ];
}
