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

use App\Constants\DB\Distance;
use App\Constants\DB\FeatureClass;
use App\Constants\DB\Format;
use App\Constants\DB\Limit;
use App\Constants\Language\CountryCode;
use App\Constants\Language\LanguageCode;

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
            'default' => '51.0504, 13.7373' /* Dresden, Germany */
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const COORDINATE_SHORT = [
        'name' => Name::COORDINATE_SHORT,
        'in' => 'query', // cookie, header, path, query
        'description' => '<strong>Coordinate Short</strong>',
        'required' => true,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => '51.0504, 13.7373' /* Dresden, Germany */
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const COUNTRY = [
        'name' => Name::COUNTRY,
        'in' => 'query', // cookie, header, path, query
        'description' => '<strong>Country</strong>',
        'required' => true,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => CountryCode::US,
            'enum' => CountryCode::ALL,
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const DISTANCE = [
        'name' => Name::DISTANCE,
        'in' => 'query', // cookie, header, path, query
        'description' => '<strong>Distance</strong>',
        'required' => false,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'integer',
            'default' => Distance::DISTANCE_1000,
            'enum' => Distance::ALL_DISTANCES,
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const FEATURE_CLASS = [
        'name' => Name::FEATURE_CLASS,
        'in' => 'query', // cookie, header, path, query
        'description' => '<strong>Feature Class</strong>',
        'required' => false,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => null,
            'enum' => FeatureClass::ALL,
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const FORMAT = [
        'name' => Name::FORMAT,
        'in' => 'query', // cookie, header, path, query
        'description' => '<strong>Format</strong>',
        'required' => true,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => Format::SIMPLE,
            'enum' => Format::ALL_FORMATS,
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
            'default' => 2_956_832 /* Altstadt, Dresden, Germany */
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const LANGUAGE = [
        'name' => Name::LANGUAGE,
        'in' => 'query', // cookie, header, path, query
        'description' => '<strong>Language</strong>',
        'required' => true,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => 'en',
            'enum' => LanguageCode::LANGUAGE_ISO_639_1,
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const LIMIT = [
        'name' => Name::LIMIT,
        'in' => 'query', // cookie, header, path, query
        'description' => '<strong>Limit</strong>',
        'required' => true,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'integer',
            'default' => Limit::LIMIT_10,
            'enum' => Limit::ALL_LIMITS,
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const NEXT_PLACES = [
        'name' => Name::NEXT_PLACES,
        'in' => 'query', // cookie, header, path, query
        'description' => '<strong>Next places</strong>',
        'required' => true,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'boolean',
            'default' => false,
            'enum' => [true, false],
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const QUERY = [
        'name' => Name::QUERY_SHORT,
        'in' => 'query', // cookie, header, path, query
        'description' => '<strong>Query</strong>',
        'required' => true,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => 'AIRP 51.05811,13.74133', /* Dresden, Germany, The golden rider */
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const QUERY_AUTOCOMPLETE = [
        'name' => Name::QUERY_SHORT,
        'in' => 'query', // cookie, header, path, query
        'description' => '<strong>Query</strong>',
        'required' => true,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => 'Dresden',
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];
}
