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
        Name::COORDINATE => [
            self::KEY_REQUEST => Name::COORDINATE,
            self::KEY_RESPONSE => 'coordinate',
            self::KEY_DEFAULT => '51%2E0504, 13%2E7373',
            self::KEY_TYPE => self::TYPE_STRING,
        ],
    ];

    public const DESCRIPTION = "# Location resources\nRetrieves the collection of Location resources.";

    public const DESCRIPTION_COLLECTION_GET = "# Location resource collection\nRetrieves the collection of Location resources.";
}
