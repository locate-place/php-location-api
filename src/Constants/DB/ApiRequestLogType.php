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

namespace App\Constants\DB;

/**
 * Class ApiRequestLogType
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-08)
 * @since 0.1.0 (2024-06-08) First version.
 */
class ApiRequestLogType
{
    final public const ERROR_500 = 'error-500';

    final public const ERROR_GENERAL = 'error-general';

    final public const FAILED_400 = 'failed-400';

    final public const FAILED_401 = 'failed-401';

    final public const FAILED_403 = 'failed-403';

    final public const FAILED_404 = 'failed-404';

    final public const FAILED_405 = 'failed-405';

    final public const FAILED_406 = 'failed-406';

    final public const FAILED_410 = 'failed-410';

    final public const FAILED_429 = 'failed-429';

    final public const FAILED_GENERAL = 'failed-general';

    final public const SUCCESS_200 = 'success-200';

    final public const SUCCESS_201 = 'success-201';

    final public const SUCCESS_202 = 'success-202';

    final public const SUCCESS_204 = 'success-204';

    final public const SUCCESS_GENERAL = 'success-general';

    final public const ALL_SUCCESS = [
        self::SUCCESS_200,
        self::SUCCESS_201,
        self::SUCCESS_202,
        self::SUCCESS_204,
        self::SUCCESS_GENERAL,
    ];
}
