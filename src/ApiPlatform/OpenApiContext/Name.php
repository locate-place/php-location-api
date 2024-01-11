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
 * Class Name
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 */
class Name
{
    final public const COORDINATE = 'coordinate';

    final public const COORDINATE_SHORT = 'c';

    final public const COUNTRY = 'country';

    final public const DISTANCE = 'distance';

    final public const FEATURE_CLASS = 'feature_class';

    final public const FEATURE_CODE = 'feature_code';

    final public const FORMAT = 'format';

    final public const GEONAME_ID = 'geoname_id';

    final public const LANGUAGE = 'language';

    final public const LIMIT = 'limit';

    final public const NEXT_PLACES = 'next_places';

    final public const QUERY = 'query';

    final public const QUERY_SHORT = 'q';

    final public const SCHEMA = 'schema';
}
