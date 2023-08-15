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

use Ixnode\PhpTimezone\Constants\CountryAsia;
use Ixnode\PhpTimezone\Constants\CountryEurope;
use Ixnode\PhpTimezone\Constants\CountryNorthAmerica;

/**
 * Class CountryConfig
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-27)
 * @since 0.1.0 (2023-06-27) First version.
 */
class CountryConfig
{
    final public const A1 = 'a1';

    final public const A2 = 'a2';

    final public const A3 = 'a3';

    final public const A4 = 'a4';

    final public const A_ALL = [
        self::A1,
        self::A2,
        self::A3,
        self::A4,
    ];

    final public const NOT_NULL = 'not-null';

    final public const NULL = 'null';

    final public const NAME_FEATURE_CLASS = 'feature-class';

    final public const NAME_FEATURE_CODES = 'feature-codes';

    final public const NAME_ADMIN_CODES = 'admin-codes';

    final public const ADMIN_CODES_CITY_DEFAULT = [

        /* Countries where cities are admin code 1 */
        CountryNorthAmerica::COUNTRY_CODE_US => self::A1,
        CountryAsia::COUNTRY_CODE_JP => self::A1,
        CountryEurope::COUNTRY_CODE_CZ => self::A1,

        /* Countries where cities are admin code 2 */
        CountryEurope::COUNTRY_CODE_DK => self::A2,
        CountryEurope::COUNTRY_CODE_NL => self::A2,
        CountryEurope::COUNTRY_CODE_PT => self::A2,
        CountryEurope::COUNTRY_CODE_SE => self::A2,

        /* Countries where cities are admin code 3 */
        CountryEurope::COUNTRY_CODE_AT => self::A3,
        CountryEurope::COUNTRY_CODE_CH => self::A3,
        CountryEurope::COUNTRY_CODE_GB => self::A2,
        CountryEurope::COUNTRY_CODE_EE => self::A3,
        CountryEurope::COUNTRY_CODE_ES => self::A3,
        CountryEurope::COUNTRY_CODE_PL => self::A3,

        /* All other countries use admin code 4 for the district to city assigment! */
    ];

    final public const DISTRICT_PLACES = [
        /* US */
        CountryNorthAmerica::COUNTRY_CODE_US => [
            /* Use feature class P */
            self::NAME_FEATURE_CLASS => FeatureClass::FEATURE_CLASS_P,
            /* The order is important! */
            self::NAME_FEATURE_CODES => [
                FeatureClass::FEATURE_CODE_P_PPLX,
                FeatureClass::FEATURE_CODE_P_PPL,
                FeatureClass::FEATURE_CODE_P_PPLW,
                FeatureClass::FEATURE_CODE_P_PPLA2,
            ],
            /* admin_code.admin2_code must not be null for district places */
            self::NAME_ADMIN_CODES => [
                self::A2 => self::NOT_NULL,
            ]
        ],

        /* All other countries use self::DEFAULT_DISTRICT_CONFIG */
    ];

    final public const CITY_PLACES = [

        /* US */
        CountryNorthAmerica::COUNTRY_CODE_US => [
            /* Use feature class P */
            self::NAME_FEATURE_CLASS => self::DEFAULT_CITY_CONFIG[self::NAME_FEATURE_CLASS],
            /* The order is important! */
            self::NAME_FEATURE_CODES => self::DEFAULT_CITY_CONFIG[self::NAME_FEATURE_CODES],
            /* admin_code.admin2_code must be null for city places */
            self::NAME_ADMIN_CODES => [
                self::A2 => self::NULL,
            ]
        ],

        /* DE */
        CountryEurope::COUNTRY_CODE_DE => [
            /* Feature class P */
            self::NAME_FEATURE_CLASS => self::DEFAULT_CITY_CONFIG[self::NAME_FEATURE_CLASS],
            /* The order is important! */
            self::NAME_FEATURE_CODES => [
                FeatureClass::FEATURE_CODE_P_PPLC,
                FeatureClass::FEATURE_CODE_P_PPL,
                FeatureClass::FEATURE_CODE_P_PPLA5,
                FeatureClass::FEATURE_CODE_P_PPLA4,
                FeatureClass::FEATURE_CODE_P_PPLA3,
                FeatureClass::FEATURE_CODE_P_PPLA2,
                FeatureClass::FEATURE_CODE_P_PPLA,
                FeatureClass::FEATURE_CODE_P_PPLF,
                FeatureClass::FEATURE_CODE_P_PPLG,
                FeatureClass::FEATURE_CODE_P_PPLQ,
                FeatureClass::FEATURE_CODE_P_PPLR,
                FeatureClass::FEATURE_CODE_P_PPLS,
                FeatureClass::FEATURE_CODE_P_PPLW,
                FeatureClass::FEATURE_CODE_P_STLMT,
            ],
            /* Use self::ADMIN_CODES_CITY for the admin codes */
            self::NAME_ADMIN_CODES => null,
        ],

        /* All other countries use self::DEFAULT_CITY_CONFIG */
    ];

    final public const DEFAULT_DISTRICT_CONFIG = [
        /* Feature class P */
        self::NAME_FEATURE_CLASS => FeatureClass::FEATURE_CLASS_P,
        /* The order is important! */
        self::NAME_FEATURE_CODES => [
            FeatureClass::FEATURE_CODE_P_PPLX,
            FeatureClass::FEATURE_CODE_P_PPL,
        ],
        /* Use self::ADMIN_CODES_CITY for the admin codes */
        self::NAME_ADMIN_CODES => null,
    ];

    final public const DEFAULT_CITY_CONFIG = [
        /* Feature class P */
        self::NAME_FEATURE_CLASS => FeatureClass::FEATURE_CLASS_P,
        /* The order is important! */
        self::NAME_FEATURE_CODES => [
            FeatureClass::FEATURE_CODE_P_PPLA5,
            FeatureClass::FEATURE_CODE_P_PPLA4,
            FeatureClass::FEATURE_CODE_P_PPLA3,
            FeatureClass::FEATURE_CODE_P_PPLA2,
            FeatureClass::FEATURE_CODE_P_PPLA,
            FeatureClass::FEATURE_CODE_P_PPLC,
            FeatureClass::FEATURE_CODE_P_PPL,
            FeatureClass::FEATURE_CODE_P_PPLF,
            FeatureClass::FEATURE_CODE_P_PPLG,
            FeatureClass::FEATURE_CODE_P_PPLQ,
            FeatureClass::FEATURE_CODE_P_PPLR,
            FeatureClass::FEATURE_CODE_P_PPLS,
            FeatureClass::FEATURE_CODE_P_PPLW,
            FeatureClass::FEATURE_CODE_P_STLMT,
        ],
        /* Use self::ADMIN_CODES_CITY for the admin codes */
        self::NAME_ADMIN_CODES => null,
    ];
}
