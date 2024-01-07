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

use App\Constants\DB\Base\BaseFeature;
use App\Constants\Language\Domain;

/**
 * Class FeatureClass
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-27)
 * @since 0.1.0 (2023-06-27) First version.
 *
 * @see http://www.geonames.org/export/codes.html
 */
class FeatureClass extends BaseFeature
{
    /* Feature classes */
    final public const A = 'A'; /* A codes → country, state, region,... */
    final public const H = 'H'; /* H codes → stream, lake, ... */
    final public const L = 'L'; /* L codes → parks, area, ... */
    final public const P = 'P'; /* P codes → city, village, ... */
    final public const R = 'R'; /* R codes → road, railroad, ... */
    final public const S = 'S'; /* S codes → spot, building, farm, ... */
    final public const T = 'T'; /* T codes → mountain, hill, rock,... */
    final public const U = 'U'; /* U codes → undersea */
    final public const V = 'V'; /* V codes → forest, heath, ... */

    /* Feature classes */
    final public const ALL = [
        self::A,
        self::H,
        self::L,
        self::P,
        self::R,
        self::S,
        self::T,
        self::U,
        self::V,
    ];

    /**
     * Returns the translated feature class.
     *
     * @inheritdoc
     */
    public function translate(string $feature, string $locale = null): string
    {
        $locale ??= $this->locale;

        return $this->translator->trans(
            $feature,
            domain: Domain::FEATURE_CLASSES,
            locale: $locale,
        );
    }
}
