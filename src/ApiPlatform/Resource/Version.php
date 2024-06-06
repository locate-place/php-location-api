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

namespace App\ApiPlatform\Resource;

use ApiPlatform\Metadata\Get;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Version as VersionOrigin;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Route\VersionRoute as VersionRouteOrigin;
use Ixnode\PhpApiVersionBundle\ApiPlatform\State\VersionProvider as VersionProviderOrigin;

/**
 * Class Version
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-26)
 * @since 0.1.0 (2023-06-26) First version.
 */
#[Get(
    openapiContext: [
        'summary' => VersionRouteOrigin::SUMMARY_GET,
        'description' => VersionRouteOrigin::DESCRIPTION_GET,
        'responses' => [
            '200' => [
                'description' => 'Version resource',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => "#/components/schemas/Version"
                        ]
                    ]
                ]
            ]
        ]
    ],
    provider: VersionProviderOrigin::class
)]
class Version extends VersionOrigin
{
}
