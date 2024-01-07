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

namespace App\ApiPlatform\Route;

use App\ApiPlatform\OpenApiContext\Name;
use App\Constants\DB\Distance;
use App\Constants\DB\Limit;
use App\Constants\Language\CountryCode;
use App\Constants\Language\LanguageCode;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Route\Base\BaseRoute;

/**
 * Class LocationRoute
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 */
final class LocationRoute extends BaseRoute
{
    final public const PROPERTIES = [
        Name::GEONAME_ID => [
            self::KEY_REQUEST => Name::GEONAME_ID,
            self::KEY_RESPONSE => 'geoname-id',
            self::KEY_DEFAULT => 182559,
            self::KEY_TYPE => self::TYPE_INTEGER,
        ],
        Name::QUERY => [
            self::KEY_REQUEST => Name::QUERY,
            self::KEY_RESPONSE => 'query',
            self::KEY_DEFAULT => 'AIRP 51.05811,13.74133', /* Dresden, Germany, The golden rider */
            self::KEY_TYPE => self::TYPE_STRING,
        ],
        Name::NEXT_PLACES => [
            self::KEY_REQUEST => Name::NEXT_PLACES,
            self::KEY_RESPONSE => 'next-places',
            self::KEY_DEFAULT => false,
            self::KEY_TYPE => self::TYPE_BOOLEAN,
        ],
        Name::DISTANCE => [
            self::KEY_REQUEST => Name::DISTANCE,
            self::KEY_RESPONSE => 'distance',
            self::KEY_DEFAULT => Distance::DISTANCE_1000,
            self::KEY_TYPE => self::TYPE_INTEGER,
        ],
        Name::LIMIT => [
            self::KEY_REQUEST => Name::LIMIT,
            self::KEY_RESPONSE => 'limit',
            self::KEY_DEFAULT => Limit::LIMIT_10,
            self::KEY_TYPE => self::TYPE_INTEGER,
        ],
        Name::LANGUAGE => [
            self::KEY_REQUEST => Name::LANGUAGE,
            self::KEY_RESPONSE => 'language',
            self::KEY_DEFAULT => LanguageCode::EN,
            self::KEY_TYPE => self::TYPE_STRING,
        ],
        Name::COUNTRY => [
            self::KEY_REQUEST => Name::COUNTRY,
            self::KEY_RESPONSE => 'country',
            self::KEY_DEFAULT => CountryCode::US,
            self::KEY_TYPE => self::TYPE_STRING,
        ],
    ];

    public const DESCRIPTION = "# Location resources\nRetrieves the collection of Location resources.";

    public const DESCRIPTION_COLLECTION_GET = "# Location resource collection\nRetrieves the collection of Location resources.";
}
