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

namespace App\ApiPlatform\OpenApiContext;

use App\Constants\Code\ApiKey;
use App\Constants\DB\Distance;
use App\Constants\DB\FeatureClass;
use App\Constants\DB\Format;
use App\Constants\DB\Limit;
use App\Constants\Language\CountryCode;
use App\Constants\Language\LanguageCode;
use App\Constants\Language\LocaleCode;

/**
 * Class Parameter
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 */
class Parameter
{
    final public const API_KEY = [
        'name' => Name::API_KEY_HEADER,
        'in' => 'header', // cookie, header, path, query
        'description' => 'The <strong>api-key</strong> parameter specifies the API key to be checked. For security reasons, the API key must be passed in the header.',
        'required' => true,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => ApiKey::PUBLIC_KEY,
            'minLength' => 32,
            'maxLength' => 32,
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const CLASS_ = [
        'name' => Name::CLASS_,
        'in' => 'query', // cookie, header, path, query
        'description' => 'The <strong>class</strong> parameter specifies the filter for class features. The following are supported: <code>A</code>, <code>H</code>, <code>L</code>, <code>P</code>, <code>R</code>, <code>S</code>, <code>T</code>, <code>U</code> and <code>V</code>.',
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

    final public const COUNTRY = [
        'name' => Name::COUNTRY,
        'in' => 'query', // cookie, header, path, query
        'description' => '<strong>Country</strong>',
        'required' => false,
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
        'description' => 'The <strong>distance</strong> parameter specifies the distance to the specified position (<strong>p</strong>) in which the search is allowed. This parameter requires the parameter <strong>p</strong>. The unit is in meters.',
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
        'description' => 'The parameter <strong>format</strong> with the value <code>"verbose"</code> adds the fields <code>path</code>, <code>created-at</code>, <code>updated-at</code> and <code>execution-time</code> to the response.',
        'required' => false,
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
        'description' => 'The <strong>language</strong> parameter specifies the language in which the response should be given. Currently only German, English and Spanish are supported.',
        'required' => false,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => 'en',
            'enum' => LanguageCode::LANGUAGE_SUPPORTED,
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const LIMIT = [
        'name' => Name::LIMIT,
        'in' => 'query', // cookie, header, path, query
        'description' => 'The <strong>limit</strong> parameter specifies the maximum number of hits per page.',
        'required' => false,
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

    final public const LOCALE = [
        'name' => Name::LOCALE,
        'in' => 'query', // cookie, header, path, query
        'description' => 'The <strong>locale</strong> parameter specifies the locale in which the response should be given. Currently only <code>en_US</code>, <code>en_GB</code>, <code>de_DE</code> and <code>es_ES</code> are supported.',
        'required' => false,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => LocaleCode::EN_GB,
            'enum' => LocaleCode::LOCALE_SUPPORTED,
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const NEXT_PLACES = [
        'name' => Name::NEXT_PLACES,
        'in' => 'query', // cookie, header, path, query
        'description' => 'The <strong>next_places</strong> parameter specifies whether the next locations are to be determined.',
        'required' => false,
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

    final public const PAGE = [
        'name' => Name::PAGE,
        'in' => 'query', // cookie, header, path, query
        'description' => 'The <strong>page</strong> parameter specifies the desired page to be returned as a search result.',
        'required' => false,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'integer',
            'default' => 1,
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const POSITION = [
        'name' => Name::POSITION,
        'in' => 'query', // cookie, header, path, query
        'description' => 'The <strong>p</strong> parameter (<strong>position</strong>) specifies the current position of the search. It is used for distance and direction calculations. An example could be <code>51.0504, 13.7373</code> or <code>40.6894, -74.0448</code> (etc.). Reference system is WGS 84.',
        'required' => false,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => null // '51.0504, 13.7373' /* Dresden, Germany */
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const QUERY = [
        'name' => Name::QUERY_SHORT,
        'in' => 'query', // cookie, header, path, query
        'description' => 'The <strong>q</strong> parameter (<strong>query</strong>) specifies the query search string. Possible search strings would be <code>AIRP 51.05811,13.74133</code> or <code>New York</code> (etc.).',
        'required' => true,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => null // 'AIRP 51.05811,13.74133', /* Dresden, Germany, The golden rider */
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const QUERY_COORDINATE = [
        'name' => Name::QUERY_SHORT,
        'in' => 'query', // cookie, header, path, query
        'description' => 'The <strong>q</strong> parameter (<strong>query</strong>) specifies the coordinate query search string. An example could be <code>51.0504, 13.7373</code> or <code>40.6894, -74.0448</code> (etc.). Reference system is WGS 84.',
        'required' => true,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'schema' => [
            'type' => 'string',
            'default' => null // 'AIRP 51.05811,13.74133', /* Dresden, Germany, The golden rider */
        ],
        'style' => 'simple', // simple, form
        'explode' => false,
        'allowReserved' => false,
    ];

    final public const QUERY_AUTOCOMPLETE = [
        'name' => Name::QUERY_SHORT,
        'in' => 'query', // cookie, header, path, query
        'description' => 'The <strong>query</strong> parameter specifies the search string for the autocomplete. At least three characters are expected.',
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
