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

namespace App\ApiPlatform\OpenApiContext;

/**
 * Class Parameter
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 */
class Parameter
{
    final public const COORDINATE = [
        'name' => Name::COORDINATE,
        'in' => 'query', // cookie, header, path, query
        'description' => '<strong>Coordinate</strong>',
        'required' => true,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => '51%2E0504, 13%2E7373' /* Dresden, Germany */
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const GEONAME_ID = [
        'name' => Name::GEONAME_ID,
        'in' => 'path', // cookie, header, path, query
        'description' => '<strong>Geoname ID</strong>',
        'required' => true,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'integer',
            'default' => 182559 /* Dresden, Germany */
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];
}
