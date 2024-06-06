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
use App\Constants\Language\LanguageCode;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Route\Base\BaseRoute;

/**
 * Class AutocompleteRoute
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-04-07)
 * @since 0.1.0 (2024-04-07) First version.
 */
final class AutocompleteRoute extends BaseRoute
{
    final public const PROPERTIES = [
        Name::QUERY_SHORT => [
            self::KEY_REQUEST => Name::QUERY_SHORT,
            self::KEY_RESPONSE => 'query',
            self::KEY_DEFAULT => 'AIRP 51.05811,13.74133', /* Dresden, Germany, The golden rider */
            self::KEY_TYPE => self::TYPE_STRING,
        ],
        Name::LANGUAGE => [
            self::KEY_REQUEST => Name::LANGUAGE,
            self::KEY_RESPONSE => 'language',
            self::KEY_DEFAULT => LanguageCode::DE,
            self::KEY_TYPE => self::TYPE_STRING,
        ],
    ];

    public const SUMMARY_GET = "Retrieves a Autocomplete resource";

    public const DESCRIPTION_GET = "This endpoint is used to search the database for hits and returns them with the ID.";
}
