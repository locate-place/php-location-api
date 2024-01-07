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

use Ixnode\PhpApiVersionBundle\ApiPlatform\Route\Base\BaseRoute;

/**
 * Class ExampleRoute
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 */
final class ExampleRoute extends BaseRoute
{
    final public const PROPERTIES = [

    ];

    public const DESCRIPTION_COLLECTION_GET = "# Example resource collection\nRetrieves the collection of Example resources.";
}
