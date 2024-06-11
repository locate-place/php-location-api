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

namespace App\ApiPlatform\Route;

use App\ApiPlatform\OpenApiContext\Name;
use App\Constants\Code\ApiKey;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Route\Base\BaseRoute;

/**
 * Class ApiKeyRoute
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-11)
 * @since 0.1.0 (2024-06-11) First version.
 */
final class ApiKeyRoute extends BaseRoute
{
    final public const PROPERTIES = [
        Name::API_KEY_HEADER => [
            self::KEY_REQUEST => Name::API_KEY_HEADER,
            self::KEY_RESPONSE => 'api-key',
            self::KEY_DEFAULT => ApiKey::PUBLIC_KEY,
            self::KEY_TYPE => self::TYPE_STRING,
        ],
    ];

    public const SUMMARY_GET = "Retrieves an ApiKey resource";

    public const DESCRIPTION_GET = "This endpoint is used to check the validity of an API key.";
}
