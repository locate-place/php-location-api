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

namespace App\Constants\Language;

/**
 * Class WikipediaCode
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-21)
 * @since 0.1.0 (2024-02-21) First version.
 */
class WikipediaCode
{
    final public const ALLOWED_LANGUAGES = [
        LanguageCode::DE,
        LanguageCode::EN,
        LanguageCode::ES,
    ];
}
