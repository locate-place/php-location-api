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

use Ixnode\PhpApiVersionBundle\ApiPlatform\Route\Base\BaseRoute;

/**
 * Class FeatureCodeRoute
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-07)
 * @since 0.1.0 (2024-06-07) First version.
 */
final class FeatureCodeRoute extends BaseRoute
{
    final public const PROPERTIES = [];

    public const SUMMARY_GET_COLLECTION = "Retrieves a collection of FeatureCode resources";

    public const DESCRIPTION_GET_COLLECTION = "This endpoint is used to retrieve the collection of all available or filtered FeatureCode resources.";
}
