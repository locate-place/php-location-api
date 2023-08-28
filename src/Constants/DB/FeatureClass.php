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

namespace App\Constants\DB;

/**
 * Class FeatureClass
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-27)
 * @since 0.1.0 (2023-06-27) First version.
 *
 * @see http://www.geonames.org/export/codes.html
 */
class FeatureClass
{
    /* Feature classes */
    final public const FEATURE_CLASS_A = 'A'; /* country, state, region,... */
    final public const FEATURE_CLASS_H = 'H'; /* stream, lake, ... */
    final public const FEATURE_CLASS_L = 'L'; /* parks, area, ... */
    final public const FEATURE_CLASS_P = 'P'; /* city, village, ... */
    final public const FEATURE_CLASS_R = 'R'; /* road, railroad, ... */
    final public const FEATURE_CLASS_S = 'S'; /* spot, building, farm, ... */
    final public const FEATURE_CLASS_T = 'T'; /* mountain, hill, rock,... */
    final public const FEATURE_CLASS_U = 'U'; /* undersea */
    final public const FEATURE_CLASS_V = 'V'; /* forest, heath, ... */

    /* Feature classes */
    final public const FEATURE_CLASSES_ALL = [
        self::FEATURE_CLASS_A,
        self::FEATURE_CLASS_H,
        self::FEATURE_CLASS_L,
        self::FEATURE_CLASS_P,
        self::FEATURE_CLASS_R,
        self::FEATURE_CLASS_S,
        self::FEATURE_CLASS_T,
        self::FEATURE_CLASS_U,
        self::FEATURE_CLASS_V,
    ];
}
