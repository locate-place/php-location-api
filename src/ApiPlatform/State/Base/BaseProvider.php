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

namespace App\ApiPlatform\State\Base;

use App\ApiPlatform\OpenApiContext\Name;
use App\Constants\Key\KeyArray;
use App\Constants\Language\CountryCode;
use App\Constants\Language\LanguageCode;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\ApiPlatform\State\Base\Wrapper\BaseResourceWrapperProvider;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpTimezone\Constants\Language;

/**
 * Class BaseProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-04)
 * @since 0.1.0 (2023-07-04) First version.
 */
class BaseProvider extends BaseResourceWrapperProvider
{
    /**
     * Add some additional data to ResourceWrapper (memory-taken, data-licence, etc.).
     *
     * @param BasePublicResource|BasePublicResource[] $baseResource
     * @param string $timeTaken
     * @return ResourceWrapper
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     */
    protected function getResourceWrapper(BasePublicResource|array $baseResource, string $timeTaken): ResourceWrapper
    {
        $resourceWrapper = parent::getResourceWrapper($baseResource, $timeTaken);

        /** @phpstan-ignore-next-line */
        $resourceWrapperNew = (new ResourceWrapper())
            ->setGiven($resourceWrapper->getGiven())
            ->setDate($resourceWrapper->getDate())
            ->setTimeTaken($resourceWrapper->getTimeTaken())
            ->setVersion($resourceWrapper->getVersion())
            ->setData($resourceWrapper->getData())
            ->setDataLicence([
                'full' => (new TypeCastingHelper($this->parameterBag->get('data_license_full')))->strval(),
                'short' => (new TypeCastingHelper($this->parameterBag->get('data_license_short')))->strval(),
                'url' => (new TypeCastingHelper($this->parameterBag->get('data_license_url')))->strval(),
            ])
            ->setMemoryTaken(sprintf('%.2f MB', memory_get_usage() / 1024 / 1024))
        ;

        $error = $resourceWrapper->getError();

        if (!is_null($error)) {
            $resourceWrapperNew->setError($error);
        }

        return $resourceWrapperNew;
    }

    /**
     * Extends the getUriVariablesOutput method.
     *
     * @return array<int|string, array<string, array<string, array<string, float|string>|string>|string>|bool|int|string>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     * @throws ParserException
     */
    protected function getUriVariablesOutput(): array
    {
        $uriVariablesOutput = parent::getUriVariablesOutput();

        if (array_key_exists(KeyArray::COORDINATE, $uriVariablesOutput)) {
            $language = (string) $uriVariablesOutput[KeyArray::COORDINATE];

            $coordinateInstance = new Coordinate($language);

            $uriVariablesOutput[KeyArray::COORDINATE] = [
                KeyArray::RAW => $language,
                KeyArray::PARSED => [
                    KeyArray::LATITUDE => [
                        KeyArray::DECIMAL => $coordinateInstance->getLatitudeDecimal(),
                        KeyArray::DMS => $coordinateInstance->getLatitudeDMS(),
                    ],
                    KeyArray::LONGITUDE => [
                        KeyArray::DECIMAL => $coordinateInstance->getLongitudeDecimal(),
                        KeyArray::DMS => $coordinateInstance->getLongitudeDMS(),
                    ],
                ]
            ];
        }

        if (array_key_exists('language', $uriVariablesOutput)) {
            $language = (new TypeCastingHelper($uriVariablesOutput['language']))->strval();

            $languageValues = array_key_exists($language, Language::LANGUAGE_ISO_639_1) ?
                Language::LANGUAGE_ISO_639_1[$language] :
                null
            ;

            $uriVariablesOutput['language'] = [
                'raw' => $language,
                'parsed' => [
                    'name' => !is_null($languageValues) ? $languageValues['en'] : 'n/a',
                ]
            ];
        }

        return $uriVariablesOutput;
    }

    /**
     * Returns the Coordinate object from url parameters.
     *
     * @return Coordinate|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    protected function getCoordinateByFilter(): Coordinate|null
    {
        if (!$this->hasFilter(Name::COORDINATE)) {
            return null;
        }

        $coordinate = $this->getFilterString(Name::COORDINATE);

        return new Coordinate($coordinate);
    }

    /**
     * Returns the iso language from url parameters.
     *
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function getIsoLanguageByFilter(): string
    {
        if (!$this->hasFilter(Name::LANGUAGE)) {
            return LanguageCode::EN;
        }

        $isoLanguage = $this->getFilterString(Name::LANGUAGE);

        return strtolower($isoLanguage);
    }

    /**
     * Returns the country from url parameters.
     *
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function getCountryByFilter(): string
    {
        if (!$this->hasFilter(Name::COUNTRY)) {
            return CountryCode::US;
        }

        $country = $this->getFilterString(Name::COUNTRY);

        return strtoupper($country);
    }

    /**
     * Returns whether next places should be added.
     *
     * @return bool
     * @throws TypeInvalidException
     */
    protected function isNextPlacesByFilter(): bool
    {
        if (!$this->hasFilter(Name::NEXT_PLACES)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the locale from url parameters.
     *
     * @param string|null $isoLanguage
     * @param string|null $country
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function getLocaleByFilter(string|null $isoLanguage = null, string|null $country = null): string
    {
        if (is_null($isoLanguage)) {
            $isoLanguage = $this->getIsoLanguageByFilter();
        }

        if (is_null($country)) {
            $country = $this->getCountryByFilter();
        }

        return sprintf('%s_%s', $isoLanguage, $country);
    }
}
