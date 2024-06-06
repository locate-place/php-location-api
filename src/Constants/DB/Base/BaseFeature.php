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

namespace App\Constants\DB\Base;

use App\Constants\Language\CountryCode;
use App\Constants\Language\LanguageCode;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class BaseFeature
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-06)
 * @since 0.1.0 (2024-01-06) First version.
 */
abstract class BaseFeature
{
    private const TEMPLATE_LOCALE = '%s_%s';

    /**
     * @param TranslatorInterface $translator
     * @param string $locale
     */
    public function __construct(
        protected TranslatorInterface $translator,
        protected string $locale = LanguageCode::EN.'_'.CountryCode::US
    )
    {
    }

    /**
     * Sets the locale.
     *
     * @param string $locale
     * @return void
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * Returns the locale from given language code.
     *
     * @param string $isoLanguage
     * @param string $country
     * @return void
     */
    public function setLocaleByLanguageAndCountry(string $isoLanguage, string $country): void
    {
        $this->setLocale(sprintf(self::TEMPLATE_LOCALE, $isoLanguage, $country));
    }

    /**
     * Returns the translated feature element.
     *
     * @param string $feature
     * @param string|null $locale
     * @return string
     */
    abstract public function translate(string $feature, string $locale = null): string;
}
