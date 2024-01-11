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
use App\ApiPlatform\Resource\Base;
use App\Constants\Key\KeyArray;
use App\Constants\Language\CountryCode;
use App\Constants\Language\LanguageCode;
use App\Constants\Language\LocaleCode;
use App\Constants\Place\Search;
use App\Utils\Performance\PerformanceLogger;
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
use LogicException;

/**
 * Class BaseProviderCustom
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-04)
 * @since 0.1.0 (2023-07-04) First version.
 */
class BaseProviderCustom extends BaseResourceWrapperProvider
{
    /**
     * Add some additional data to ResourceWrapper (memory-taken, data-licence, etc.).
     *
     * @param BasePublicResource|BasePublicResource[] $baseResource
     * @param string $timeTaken
     * @return ResourceWrapperCustom
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     */
    protected function getResourceWrapper(BasePublicResource|array $baseResource, string $timeTaken): ResourceWrapperCustom
    {
        $resourceWrapper = parent::getResourceWrapper($baseResource, $timeTaken);

        $resourceWrapperNew = new ResourceWrapperCustom();

        /* Show schema */
        if ($this->isSchemaByFilter()) {
            $data = $resourceWrapper->getData();

            if (!$data instanceof Base) {
                throw new LogicException(sprintf('The data part must be an instance of "%s".', Base::class));
            }

            $resourceWrapperNew
                ->setSchema($data->getAll())
                ->setDate($resourceWrapper->getDate())
                ->setVersion($resourceWrapper->getVersion())
            ;
        }

        $memoryTaken = sprintf('%.2f MB', memory_get_usage() / 1024 / 1024);

        $resourceWrapperNew
            ->setMemoryTaken($memoryTaken)
            ->setTimeTaken($resourceWrapper->getTimeTaken())
            ->setPerformance(PerformanceLogger::getInstance()->getPerformanceData()->getArray())
            ->setGiven($resourceWrapper->getGiven())
            ->setDate($resourceWrapper->getDate())
            ->setVersion($resourceWrapper->getVersion())
            ->setData($resourceWrapper->getData())
        ;

        /* Add data licence. */
        $resourceWrapperNew->setDataLicence([
            'full' => (new TypeCastingHelper($this->parameterBag->get('data_license_full')))->strval(),
            'short' => (new TypeCastingHelper($this->parameterBag->get('data_license_short')))->strval(),
            'url' => (new TypeCastingHelper($this->parameterBag->get('data_license_url')))->strval(),
        ]);

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

        if (array_key_exists('country', $uriVariablesOutput)) {
            $country = (new TypeCastingHelper($uriVariablesOutput['country']))->strval();

            $uriVariablesOutput['country'] = [
                'raw' => $country,
                'parsed' => [
                    'name' => $country,
                ]
            ];
        }

        if (!array_key_exists(KeyArray::NEXT_PLACES, $uriVariablesOutput)) {
            $uriVariablesOutput[KeyArray::NEXT_PLACES] = false;
        }

        if (array_key_exists(KeyArray::GEONAME_ID, $uriVariablesOutput) && $uriVariablesOutput[KeyArray::GEONAME_ID] === 0) {
            unset($uriVariablesOutput[KeyArray::GEONAME_ID]);
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
     * Returns whether schema should be displayed.
     *
     * @return bool
     * @throws TypeInvalidException
     */
    protected function isSchemaByFilter(): bool
    {
        if (!$this->hasFilter(Name::SCHEMA)) {
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

    /**
     * Returns the iso language and country given by filter.
     *
     * @return array{iso-language: string|null, country: string|null}
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function getIsoLanguageAndCountryByFilter(): array
    {
        $isoLanguage = $this->getIsoLanguageByFilter();
        $country = $this->getCountryByFilter();
        $locale = $this->getLocaleByFilter($isoLanguage, $country);

        if (!in_array($locale, LocaleCode::ALL)) {
            $this->setError(sprintf('Locale "%s" is not supported yet. Please choose on of them: %s', $locale, implode(', ', LocaleCode::ALL)));
            return [
                KeyArray::ISO_LANGUAGE => null,
                KeyArray::COUNTRY => null,
            ];
        }

        return [
            KeyArray::ISO_LANGUAGE => $isoLanguage,
            KeyArray::COUNTRY => $country,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function endpointContains(string $word): bool
    {
        $pathInfo = explode('/', $this->getCurrentRequest()->getPathInfo());

        foreach ($pathInfo as $pathInfoPart) {
            if (preg_match(sprintf('~^%s$~', $word), $pathInfoPart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns some geoname id examples.
     *
     * @return int[]
     */
    protected function getGeonameIds(): array
    {
        $geonameIds = [];

        foreach (array_keys(Search::VALUES) as $key) {
            $search = new Search($key);

            $geonameIds[] = $search->getGeonameId();
        }

        return $geonameIds;
    }

    /**
     * Returns the full name array.
     *
     * @return array<int, string>
     */
    protected function getNamesFull(): array
    {
        $namesFull = [];

        foreach (array_keys(Search::VALUES) as $key) {
            $search = new Search($key);

            $namesFull[$search->getGeonameId()] = $search->getNameFull();
        }

        return $namesFull;
    }
}
