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

namespace App\Service;

use App\Constants\DB\Distance;
use App\Constants\DB\FeatureClass;
use App\Constants\DB\FeatureCode;
use App\Constants\DB\Limit;
use App\Constants\Key\KeyArray;
use App\Constants\Language\CountryCode;
use App\Constants\Place\AdminType;
use App\Entity\Location;
use App\Exception\QueryParserException;
use App\Utils\Query\Query;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class LocationServiceConfig
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-24)
 * @since 0.1.0 (2023-08-24) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
final class LocationServiceConfig
{
    private const FEATURE_CODES_A_ALL = 'feature_codes_a_all';

    private const FEATURE_CODES_H_ALL = 'feature_codes_h_all';

    private const FEATURE_CODES_L_ALL = 'feature_codes_l_all';

    private const FEATURE_CODES_P_ALL = 'feature_codes_p_all';

    private const FEATURE_CODES_R_ALL = 'feature_codes_r_all';

    private const FEATURE_CODES_S_ALL = 'feature_codes_s_all';

    private const FEATURE_CODES_T_ALL = 'feature_codes_t_all';

    private const FEATURE_CODES_U_ALL = 'feature_codes_u_all';

    private const FEATURE_CODES_V_ALL = 'feature_codes_v_all';

    /** @var array<string, array<string, mixed>> $configNextPlacesGroupsCache */
    private array $configNextPlacesGroupsCache = [];

    /**
     * @param ParameterBagInterface $parameterBag
     * @param TranslatorInterface $translator
     */
    public function __construct(
        protected ParameterBagInterface $parameterBag,
        protected TranslatorInterface $translator
    )
    {
    }

    /**
     * Returns the country code of the given location.
     *
     * @param Location|null $location
     * @return string|null
     * @throws CaseUnsupportedException
     */
    private function getCountryCode(Location|null $location): string|null
    {
        if (is_null($location)) {
            return null;
        }

        $countryCode = $location->getCountry()?->getCode();

        if (is_null($countryCode)) {
            throw new CaseUnsupportedException('Unable to get country code from location.');
        }

        return $countryCode;
    }

    /**
     * Returns the country config.
     *
     * @param string|null $countryCode
     * @param string $type
     * @return array<string, mixed>|int
     * @throws CaseUnsupportedException
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getCountryConfig(string|null $countryCode, string $type = 'district'): array|int
    {
        $locationCountry = $this->parameterBag->get('location_configuration');

        if (!is_array($locationCountry)) {
            throw new CaseUnsupportedException('The given location_configuration configuration is not an array.');
        }

        /* No country code given -> return default config */
        if (is_null($countryCode)) {
            return $locationCountry['default'][$type];
        }

        if (!array_key_exists($countryCode, $locationCountry)) {
            return $locationCountry['default'][$type];
        }

        $config = $locationCountry[$countryCode];

        if (is_null($config)) {
            return $locationCountry['default'][$type];
        }

        if (!is_array($config)) {
            throw new CaseUnsupportedException(sprintf('The given config for country %s is not an array.', $countryCode));
        }

        if (!array_key_exists($type, $config)) {
            return $locationCountry['default'][$type];
        }

        $configType = $config[$type];

        if (is_null($configType)) {
            return $locationCountry['default'][$type];
        }

        if (!is_array($configType) && !is_int($configType)) {
            throw new CaseUnsupportedException(sprintf('The given config for country.%s %s is not an array.', $type, $countryCode));
        }

        return $configType;
    }

    /**
     * Returns the default config.
     *
     * @param string $type
     * @return array<string, mixed>|int
     * @throws CaseUnsupportedException
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getDefaultConfig(string $type = 'district'): array|int
    {
        $locationCountry = $this->parameterBag->get('location_configuration');

        if (!is_array($locationCountry)) {
            throw new CaseUnsupportedException('The given location_configuration configuration is not an array.');
        }

        return $locationCountry['default'][$type];
    }

    /**
     * Returns the admin codes for the given location (Country).
     *
     * @param Location $location
     * @return array{a1?: string, a2?: string, a3?: string, a4?: string}
     * @throws CaseUnsupportedException
     */
    public function getAdminCodesGeneral(Location $location): array
    {
        $adminCode = $this->getAdminDistrictMatch($location);

        return match ($adminCode) {
            AdminType::A0 => [],
            AdminType::A1 => [AdminType::A1 => (string) $location->getAdminCode()?->getAdmin1Code()],
            AdminType::A2 => [AdminType::A2 => (string) $location->getAdminCode()?->getAdmin2Code()],
            AdminType::A3 => [AdminType::A3 => (string) $location->getAdminCode()?->getAdmin3Code()],
            default => [AdminType::A4 => (string) $location->getAdminCode()?->getAdmin4Code()]
        };
    }

    /**
     * Returns the admin codes for the given location (Country).
     *
     * @param Location $location
     * @return array{a1: string|false|null, a2: string|false|null, a3: string|false|null, a4: string|false|null}
     * @throws CaseUnsupportedException
     */
    public function getAdminCodesMatch(Location $location): array
    {
        $adminCode = $this->getAdminDistrictMatch($location);

        return match ($adminCode) {
            AdminType::A0 => [
                'a1' => false,
                'a2' => false,
                'a3' => false,
                'a4' => false,
            ],
            AdminType::A1 => [
                'a1' => $location->getAdminCode()?->getAdmin1Code(),
                'a2' => false,
                'a3' => false,
                'a4' => false,
            ],
            AdminType::A2 => [
                'a1' => $location->getAdminCode()?->getAdmin1Code(),
                'a2' => $location->getAdminCode()?->getAdmin2Code(),
                'a3' => false,
                'a4' => false,
            ],
            AdminType::A3 => [
                'a1' => $location->getAdminCode()?->getAdmin1Code(),
                'a2' => $location->getAdminCode()?->getAdmin2Code(),
                'a3' => $location->getAdminCode()?->getAdmin3Code(),
                'a4' => false,
            ],
            default => [
                'a1' => $location->getAdminCode()?->getAdmin1Code(),
                'a2' => $location->getAdminCode()?->getAdmin2Code(),
                'a3' => $location->getAdminCode()?->getAdmin3Code(),
                'a4' => $location->getAdminCode()?->getAdmin4Code(),
            ]
        };
    }

    /**
     * Returns the admin codes for the given location (Country).
     *
     * @param Location $location
     * @param string $type
     * @return string
     * @throws CaseUnsupportedException
     */
    public function getAdminDistrictMatch(Location $location, string $type = 'district'): string
    {
        $key = 'match';

        $match = $this->getValueFromConfig($key, $location, $type);

        if (!is_string($match)) {
            throw new LogicException('The given match config is not a string.');
        }

        return $match;
    }

    /**
     * @param Location $location
     * @return int
     * @throws CaseUnsupportedException
     */
    public function getDetectionMode(Location $location): int
    {
        $type = 'detection_mode';

        $countryCode = $this->getCountryCode($location);

        $countryConfig = $this->getCountryConfig($countryCode, $type);

        if (!is_int($countryConfig)) {
            throw new LogicException('The given config for country is not an int.');
        }

        return $countryConfig;
    }

    /**
     * Returns the admin codes from given configuration.
     *
     * @param array<string, mixed> $adminCodeConfig
     * @param Location $location
     * @return array{a1?: string, a2?: string, a3?: string, a4?: string}
     * @throws TypeInvalidException
     */
    private function getAdminCodesFromConfig(array $adminCodeConfig, Location $location): array
    {
        $adminCodes = [];

        foreach (['a1', 'a2', 'a3', 'a4'] as $adminCode) {
            if (!array_key_exists($adminCode, $adminCodeConfig)) {
                continue;
            }

            $adminCodes[$adminCode] = (new TypeCastingHelper($adminCodeConfig[$adminCode]))->strval();

            if ($adminCodes[$adminCode] === 'from-location') {
                $adminCodes[$adminCode] = match (true) {
                    $adminCode === 'a1' => $location->getAdminCode()?->getAdmin1Code() ?: 'null',
                    $adminCode === 'a2' => $location->getAdminCode()?->getAdmin2Code() ?: 'null',
                    $adminCode === 'a3' => $location->getAdminCode()?->getAdmin3Code() ?: 'null',
                    $adminCode === 'a4' => $location->getAdminCode()?->getAdmin4Code() ?: 'null',
                };
            }
        }

        return $adminCodes;
    }

    /**
     * Returns the admin codes for the given location.
     *
     * @param Location $location
     * @param bool $withNull
     * @return array{a1?: string, a2?: string, a3?: string, a4?: string}
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function getAdminCodesFromLocation(Location $location, bool $withNull = false): array
    {
        $adminCodes = [];

        foreach (['a1', 'a2', 'a3', 'a4'] as $key) {
            $adminCode = match (true) {
                $key === 'a1' => $location->getAdminCode()?->getAdmin1Code(),
                $key === 'a2' => $location->getAdminCode()?->getAdmin2Code(),
                $key === 'a3' => $location->getAdminCode()?->getAdmin3Code(),
                $key === 'a4' => $location->getAdminCode()?->getAdmin4Code(),
            };

            if (!$withNull && is_null($adminCode)) {
                continue;
            }

            if (is_null($adminCode)) {
                $adminCode = 'null';
            }

            $adminCodes[$key] = $adminCode;
        }

        return $adminCodes;
    }

    /**
     * Returns the exception configuration if exists.
     *
     * @param array<string, mixed> $countryConfig
     * @param Location|null $location
     * @return array<string, mixed>|null
     * @throws CaseUnsupportedException
     */
    private function getExceptionMatchConfig(array $countryConfig, Location|null $location): ?array
    {
        if (is_null($location)) {
            return null;
        }

        if (!array_key_exists('exceptions', $countryConfig)) {
            return null;
        }

        $exceptions = $countryConfig['exceptions'];

        if (!is_array($exceptions)) {
            throw new CaseUnsupportedException('The given exceptions configuration is not an array.');
        }

        foreach ($exceptions as $exception) {
            if (!is_array($exception)) {
                throw new CaseUnsupportedException('The given exception configuration is not an array.');
            }

            $adminCodes = $exception['filter'];

            $match = true;

            foreach ($adminCodes as $key => $adminCode) {
                $value = match (true) {
                    $key === 'a1' => $location->getAdminCode()?->getAdmin1Code(),
                    $key === 'a2' => $location->getAdminCode()?->getAdmin2Code(),
                    $key === 'a3' => $location->getAdminCode()?->getAdmin3Code(),
                    $key === 'a4' => $location->getAdminCode()?->getAdmin4Code(),
                    default => throw new CaseUnsupportedException(sprintf('The given admin key %s is not supported.', $key))
                };

                $adminCodeSplit = explode('|', (string) $adminCode);

                if (!in_array($value, $adminCodeSplit)) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                return $exception;
            }
        }

        return null;
    }

    /**
     * Returns the values from config from given key and location.
     *
     * @param string $key
     * @param Location|null $location
     * @param string $type
     * @return mixed
     * @throws CaseUnsupportedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getValueFromConfig(string $key, Location|null $location, string $type = 'district'): mixed
    {
        $countryCode = $this->getCountryCode($location);

        $defaultConfig = $this->getDefaultConfig($type);

        if (!is_array($defaultConfig)) {
            throw new LogicException('The given default config is not an array.');
        }

        $countryConfig = $this->getCountryConfig($countryCode, $type);

        if (!is_array($countryConfig)) {
            throw new LogicException('The given country config is not an array.');
        }

        $exceptionMatch = $this->getExceptionMatchConfig($countryConfig, $location);

        /* Return the exception value. */
        if (!is_null($exceptionMatch) && array_key_exists($key, $exceptionMatch) && !is_null($exceptionMatch[$key])) {
            return $exceptionMatch[$key];
        }

        /* Return the value for key from default or country settings. */
        if (array_key_exists($key, $countryConfig) && !is_null($countryConfig[$key])) {
            return $countryConfig[$key];
        }

        /* Return the value for key from default settings. */
        if (array_key_exists($key, $defaultConfig) && !is_null($defaultConfig[$key])) {
            return $defaultConfig[$key];
        }

        return null;
    }

    /**
     * Returns the feature class of given country.
     *
     * @param Location $location
     * @param string $type
     * @return bool
     * @throws CaseUnsupportedException
     */
    private function isVisible(Location $location, string $type = 'district'): bool
    {
        $key = 'visible';

        $visible = $this->getValueFromConfig($key, $location, $type);

        if (is_null($visible)) {
            return true;
        }

        if (!is_bool($visible)) {
            throw new CaseUnsupportedException(sprintf(
                'Unsupported type given for %s.%s.%s: %s.',
                $this->getCountryCode($location),
                $type,
                $key,
                gettype($visible)
            ));
        }

        return $visible;
    }

    /**
     * Returns the feature class of given country.
     *
     * @param Location|null $location
     * @param string $type
     * @return string
     * @throws CaseUnsupportedException
     */
    private function getFeatureClass(Location|null $location, string $type = 'district'): string
    {
        $key = 'feature_class';

        $featureClass = $this->getValueFromConfig($key, $location, $type);

        if (!is_string($featureClass)) {
            throw new CaseUnsupportedException(sprintf(
                'Unsupported type given for %s.%s.%s: %s.',
                $this->getCountryCode($location),
                $type,
                $key,
                gettype($featureClass)
            ));
        }

        return $featureClass;
    }

    /**
     * Returns the feature codes of given country.
     *
     * @param Location|null $location
     * @param string $type
     * @return array<int, string>
     * @throws CaseUnsupportedException
     */
    private function getFeatureCodes(Location|null $location, string $type = 'district'): array
    {
        $key = 'feature_codes';

        $featureCodes = $this->getValueFromConfig($key, $location, $type);

        if (!is_array($featureCodes)) {
            throw new CaseUnsupportedException(sprintf(
                'Unsupported type given for %s.%s.%s: %s.',
                $this->getCountryCode($location),
                $type,
                $key,
                gettype($featureCodes)
            ));
        }

        return array_values($featureCodes);
    }

    /**
     * Returns if the feature codes should be sorted by given location.
     *
     * @param Location $location
     * @param string $type
     * @return bool
     * @throws CaseUnsupportedException
     */
    private function isSortByFeatureCodes(Location $location, string $type = 'district'): bool
    {
        $key = 'sort_by_feature_codes';

        $sortByFeatureCodes = $this->getValueFromConfig($key, $location, $type);

        if (!is_bool($sortByFeatureCodes)) {
            throw new CaseUnsupportedException(sprintf(
                'Unsupported type given for %s.%s.%s: %s.',
                $this->getCountryCode($location),
                $type,
                $key,
                gettype($sortByFeatureCodes)
            ));
        }

        return $sortByFeatureCodes;
    }

    /**
     * Returns if the feature codes should be sorted by given population.
     *
     * @param Location $location
     * @param string $type
     * @return bool
     * @throws CaseUnsupportedException
     */
    private function isSortByPopulation(Location $location, string $type = 'district'): bool
    {
        $key = 'sort_by_population';

        $sortByPopulation = $this->getValueFromConfig($key, $location, $type);

        if (!is_bool($sortByPopulation)) {
            throw new CaseUnsupportedException(sprintf(
                'Unsupported type given for %s.%s.%s: %s.',
                $this->getCountryCode($location),
                $type,
                $key,
                gettype($sortByPopulation)
            ));
        }

        return $sortByPopulation;
    }

    /**
     * Returns if the coordinate should be used of given location.
     *
     * @param Location $location
     * @param string $type
     * @return bool
     * @throws CaseUnsupportedException
     */
    private function isUseCoordinate(Location $location, string $type = 'district'): bool
    {
        $key = 'use_coordinate';

        $sortByFeatureCodes = $this->getValueFromConfig($key, $location, $type);

        if (!is_bool($sortByFeatureCodes)) {
            throw new CaseUnsupportedException(sprintf(
                'Unsupported type given for %s.%s.%s: %s.',
                $this->getCountryCode($location),
                $type,
                $key,
                gettype($sortByFeatureCodes)
            ));
        }

        return $sortByFeatureCodes;
    }

    /**
     * Returns the admin codes for district.
     *
     * @param Location $location
     * @param string $type
     * @return array{a1?: string, a2?: string, a3?: string, a4?: string}
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     */
    private function getAdminCodes(Location $location, string $type = 'district'): array
    {
        $key = 'admin_codes';

        if ($this->isMustMatchAdminCodes($location, $type)) {
            return $this->getAdminCodesFromLocation($location);
        }

        $countryCode = $this->getCountryCode($location);

        $countryConfig = $this->getCountryConfig($countryCode, $type);

        if (!is_array($countryConfig)) {
            throw new LogicException('The given country config is not an array.');
        }

        $exceptionMatch = $this->getExceptionMatchConfig($countryConfig, $location);

        /* Returns overwritten exception admin codes. */
        if (!is_null($exceptionMatch) && array_key_exists('admin_codes', $exceptionMatch)) {
            $adminCodes = $exceptionMatch['admin_codes'];

            if (!is_array($adminCodes)) {
                throw new CaseUnsupportedException('Given admin_codes configuration is not an array.');
            }

            return $this->getAdminCodesFromConfig($adminCodes, $location);
        }

        /* Returns the default admin codes from district_match configuration. */
        if (!array_key_exists($key, $countryConfig)) {
            return $this->getAdminCodesGeneral($location);
        }

        /* if no admin codes are given, use the city admin codes */
        if (is_null($countryConfig[$key])) {
            return $this->getAdminCodesGeneral($location);
        }

        $adminCodeConfig = $countryConfig[$key];

        if (!is_array($adminCodeConfig)) {
            throw new CaseUnsupportedException(sprintf(
                'Unsupported type given for %s.%s.%s: %s.',
                $this->getCountryCode($location),
                $type,
                $key,
                gettype($adminCodeConfig)
            ));
        }

        return $this->getAdminCodesFromConfig($adminCodeConfig, $location);
    }

    /**
     * Returns the district with population of given location.
     *
     * @param Location $location
     * @param string $type
     * @return bool|null
     * @throws CaseUnsupportedException
     */
    private function getWithPopulation(Location $location, string $type = 'district'): bool|null
    {
        $key = 'with_population';

        $withPopulation = $this->getValueFromConfig($key, $location, $type);

        if (!is_bool($withPopulation) && !is_null($withPopulation)) {
            throw new CaseUnsupportedException(sprintf(
                'Unsupported type given for %s.%s.%s: %s.',
                $this->getCountryCode($location),
                $type,
                $key,
                gettype($withPopulation)
            ));
        }

        return $withPopulation;
    }

    /**
     * Returns the district "must match admin codes" flag of given location.
     *
     * @param Location $location
     * @param string $type
     * @return bool
     * @throws CaseUnsupportedException
     */
    private function isMustMatchAdminCodes(Location $location, string $type = 'district'): bool
    {
        $key = 'must_match_admin_codes';

        $mustMatchAdminCodes = $this->getValueFromConfig($key, $location, $type);

        if (is_null($mustMatchAdminCodes)) {
            return false;
        }

        if (!is_bool($mustMatchAdminCodes)) {
            throw new CaseUnsupportedException(sprintf(
                'Unsupported type given for %s.%s.%s: %s.',
                $this->getCountryCode($location),
                $type,
                $key,
                gettype($mustMatchAdminCodes)
            ));
        }

        return $mustMatchAdminCodes;
    }

    /**
     * Returns the value for given feature class and feature code.
     *
     * @param string $key
     * @param string|null $featureClass
     * @param string|null $featureCode
     * @param string|null $country
     * @param mixed|null $default
     * @return mixed
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getConfigNextPlaces(
        string $key,
        string|null $featureClass = null,
        string|null $featureCode = null,
        string|null $country = null,
        mixed $default = null
    ): mixed
    {
        $nextPlaces = $this->parameterBag->get('next_places');

        if (!is_array($nextPlaces)) {
            throw new TypeInvalidException('array', gettype($nextPlaces));
        }

        $config = array_key_exists($key, $nextPlaces) ? $nextPlaces[$key] : [];

        if (!is_array($config)) {
            throw new TypeInvalidException('array', gettype($config));
        }

        /* Try to find country overwrites and update config. */
        if (!is_null($country) && array_key_exists('overwrites', $config)) {
            $overwrites = new Json($config['overwrites']);
            if ($overwrites->hasKey($country)) {
                $config = array_merge($config, $overwrites->getKeyArray($country));
            }
        }

        /* Try to get config for given feature code. */
        if (!is_null($featureCode)) {
            $featureCodeConfig = array_key_exists('feature_code', $config) ? $config['feature_code'] : [];

            if (!is_array($featureCodeConfig)) {
                throw new TypeInvalidException('array', gettype($featureCodeConfig));
            }

            $featureCodeKey = sprintf('%s.%s', $featureClass, $featureCode);

            if (array_key_exists($featureCodeKey, $featureCodeConfig)) {
                return $featureCodeConfig[$featureCodeKey];
            }
        }

        /* Try to get config for given feature class. */
        if (!is_null($featureClass)) {
            $featureClassConfig = array_key_exists('feature_class', $config) ? $config['feature_class'] : [];

            if (!is_array($featureClassConfig)) {
                throw new TypeInvalidException('array', gettype($featureClassConfig));
            }

            if (array_key_exists($featureClass, $featureClassConfig)) {
                return $featureClassConfig[$featureClass];
            }
        }

        /* Last and least try to get default config, otherwise use the default parameter. */
        return array_key_exists('default', $config) ? $config['default'] : $default;
    }

    /**
     * @param string|null $country
     * @param array<string, int|string> $default
     * @return array<string, mixed>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getConfigNextPlacesGroups(
        string|null $country = null,
        array $default = [
            KeyArray::DISTANCE => Distance::DISTANCE_100000,
            KeyArray::LIMIT => Limit::LIMIT_10,
        ]
    ): array
    {
        $cacheKey = is_string($country) ? $country : CountryCode::DEFAULT;

        if (array_key_exists($cacheKey, $this->configNextPlacesGroupsCache)) {
            return $this->configNextPlacesGroupsCache[$cacheKey];
        }

        $nextPlacesGroups = $this->parameterBag->get('next_places_groups');

        if (!is_array($nextPlacesGroups)) {
            throw new TypeInvalidException('array', gettype($nextPlacesGroups));
        }

        /* Read overwrites configuration by given country. */
        $overwritesConfigDefault = [];
        $overwritesConfigNext = [];
        if (!is_null($country) && array_key_exists('overwrites', $nextPlacesGroups)) {
            $overwrites = new Json($nextPlacesGroups['overwrites']);
            $overwritesConfig = [];

            if ($overwrites->hasKey($country)) {
                $overwritesConfig = $overwrites->getKeyArray($country);
            }

            if (array_key_exists('default', $overwritesConfig) && is_array($overwritesConfig['default'])) {
                $overwritesConfigDefault = $overwritesConfig['default'];
            }

            if (array_key_exists('next', $overwritesConfig) && is_array($overwritesConfig['next'])) {
                $overwritesConfigNext = $overwritesConfig['next'];
            }

            foreach ($overwritesConfigNext as $key => $value) {
                if (!is_array($value)) {
                    throw new LogicException('Each next_places_groups.next configuration must be an array.');
                }

                $overwritesConfigNext[$key] = array_replace_recursive($overwritesConfigDefault, $value);
            }
        }

        $default = match (true) {
            array_key_exists('default', $nextPlacesGroups) && is_array($nextPlacesGroups['default']) => $nextPlacesGroups['default'],
            default => $default,
        };

        /* Overwrites the default configuration with the overwrites configuration from given country. */
        $default = array_replace_recursive($default, $overwritesConfigDefault);

        $next = match (true) {
            array_key_exists('next', $nextPlacesGroups) && is_array($nextPlacesGroups['next']) => $nextPlacesGroups['next'],
            default => [],
        };

        foreach ($next as $key => $value) {
            if (!is_array($value)) {
                throw new LogicException('Each next_places_groups.next configuration must be an array.');
            }

            $next[$key] = array_replace_recursive($default, $value);
        }

        /* Overwrites the country configuration */
        $next = array_replace_recursive($next, $overwritesConfigNext);

        $this->configNextPlacesGroupsCache[$cacheKey] = $next;

        return $next;
    }

    /**
     * Returns if district is visible.
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isDistrictVisible(Location $location): bool
    {
        return $this->isVisible($location);
    }

    /**
     * Returns if borough is visible.
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isBoroughVisible(Location $location): bool
    {
        return $this->isVisible($location, 'borough');
    }

    /**
     * Returns if city is visible.
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isCityVisible(Location $location): bool
    {
        return $this->isVisible($location, 'city');
    }

    /**
     * Returns the location reference feature class of given location.
     *
     * @param Location|null $location
     * @return string
     * @throws CaseUnsupportedException
     */
    public function getLocationReferenceFeatureClass(Location|null $location = null): string
    {
        return $this->getFeatureClass($location, 'location_reference');
    }

    /**
     * Returns the district feature class of given location.
     *
     * @param Location $location
     * @return string
     * @throws CaseUnsupportedException
     */
    public function getDistrictFeatureClass(Location $location): string
    {
        return $this->getFeatureClass($location);
    }

    /**
     * Returns the borough feature codes of given location.
     *
     * @param Location $location
     * @return string
     * @throws CaseUnsupportedException
     */
    public function getBoroughFeatureClass(Location $location): string
    {
        return $this->getFeatureClass($location, 'borough');
    }

    /**
     * Returns the city feature codes of given location.
     *
     * @param Location $location
     * @return string
     * @throws CaseUnsupportedException
     */
    public function getCityFeatureClass(Location $location): string
    {
        return $this->getFeatureClass($location, 'city');
    }

    /**
     * Returns the state feature codes of given location.
     *
     * @param Location $location
     * @return string
     * @throws CaseUnsupportedException
     */
    public function getStateFeatureClass(Location $location): string
    {
        return $this->getFeatureClass($location, 'state');
    }

    /**
     * Returns the location reference feature codes of given location.
     *
     * @param Location|null $location
     * @return array<int, string>
     * @throws CaseUnsupportedException
     */
    public function getLocationReferenceFeatureCodes(Location|null $location = null): array
    {
        return $this->getFeatureCodes($location, 'location_reference');
    }

    /**
     * Returns the district feature codes of given location.
     *
     * @param Location $location
     * @return array<int, string>
     * @throws CaseUnsupportedException
     */
    public function getDistrictFeatureCodes(Location $location): array
    {
        return $this->getFeatureCodes($location);
    }

    /**
     * Returns the borough feature codes of given location.
     *
     * @param Location $location
     * @return array<int, string>
     * @throws CaseUnsupportedException
     */
    public function getBoroughFeatureCodes(Location $location): array
    {
        return $this->getFeatureCodes($location, 'borough');
    }

    /**
     * Returns the city feature codes of given location.
     *
     * @param Location $location
     * @return array<int, string>
     * @throws CaseUnsupportedException
     */
    public function getCityFeatureCodes(Location $location): array
    {
        return $this->getFeatureCodes($location, 'city');
    }

    /**
     * Returns the state feature codes of given location.
     *
     * @param Location $location
     * @return array<int, string>
     * @throws CaseUnsupportedException
     */
    public function getStateFeatureCodes(Location $location): array
    {
        return $this->getFeatureCodes($location, 'state');
    }

    /**
     * Returns if the feature codes should be sorted of given location.
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isDistrictSortByFeatureCodes(Location $location): bool
    {
        return $this->isSortByFeatureCodes($location);
    }

    /**
     * Returns if the borough feature codes should be sorted.
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isBoroughSortByFeatureCodes(Location $location): bool
    {
        return $this->isSortByFeatureCodes($location, 'borough');
    }

    /**
     * Returns if the feature codes should be sorted.
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isCitySortByFeatureCodes(Location $location): bool
    {
        return $this->isSortByFeatureCodes($location, 'city');
    }

    /**
     * Returns if the feature codes should be sorted (state).
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isStateSortByFeatureCodes(Location $location): bool
    {
        return $this->isSortByFeatureCodes($location, 'state');
    }

    /**
     * Returns if the feature codes should be sorted by given population.
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isDistrictSortByPopulation(Location $location): bool
    {
        return $this->isSortByPopulation($location);
    }

    /**
     * Returns if the borough feature codes should be sorted.
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isBoroughSortByPopulation(Location $location): bool
    {
        return $this->isSortByPopulation($location, 'borough');
    }

    /**
     * Returns if the feature codes should be sorted.
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isCitySortByPopulation(Location $location): bool
    {
        return $this->isSortByPopulation($location, 'city');
    }

    /**
     * Returns if the feature codes should be sorted (state).
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isStateSortByPopulation(Location $location): bool
    {
        return $this->isSortByPopulation($location, 'state');
    }

    /**
     * Returns if the coordinate should be used (state).
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isStateUseCoordinate(Location $location): bool
    {
        return $this->isUseCoordinate($location, 'state');
    }

    /**
     * Returns the admin codes for district.
     *
     * @param Location $location
     * @return array{a1?: string, a2?: string, a3?: string, a4?: string}
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     */
    public function getDistrictAdminCodes(Location $location): array
    {
        return $this->getAdminCodes($location);
    }

    /**
     * Returns the admin codes for borough.
     *
     * @param Location $location
     * @return array{a1?: string, a2?: string, a3?: string, a4?: string}
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     */
    public function getBoroughAdminCodes(Location $location): array
    {
        return $this->getAdminCodes($location, 'borough');
    }

    /**
     * Returns the admin codes for city.
     *
     * @param Location $location
     * @return array{a1?: string, a2?: string, a3?: string, a4?: string}
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     */
    public function getCityAdminCodes(Location $location): array
    {
        return $this->getAdminCodes($location, 'city');
    }

    /**
     * Returns the admin codes for state.
     *
     * @param Location $location
     * @return array{a1?: string, a2?: string, a3?: string, a4?: string}
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     */
    public function getStateAdminCodes(Location $location): array
    {
        return $this->getAdminCodes($location, 'state');
    }

    /**
     * Returns the district with population from given location.
     *
     * @param Location $location
     * @return bool|null
     * @throws CaseUnsupportedException
     */
    public function getDistrictWithPopulation(Location $location): bool|null
    {
        return $this->getWithPopulation($location);
    }

    /**
     * Returns the borough with population of given location.
     *
     * @param Location $location
     * @return bool|null
     * @throws CaseUnsupportedException
     */
    public function getBoroughWithPopulation(Location $location): bool|null
    {
        return $this->getWithPopulation($location, 'borough');
    }

    /**
     * Returns the city with population of given location.
     *
     * @param Location $location
     * @return bool|null
     * @throws CaseUnsupportedException
     */
    public function getCityWithPopulation(Location $location): bool|null
    {
        return $this->getWithPopulation($location, 'city');
    }

    /**
     * Returns the state with population of given location.
     *
     * @param Location $location
     * @return bool|null
     * @throws CaseUnsupportedException
     */
    public function getStateWithPopulation(Location $location): bool|null
    {
        return $this->getWithPopulation($location, 'state');
    }

    /**
     * Returns the district "must match admin codes" flag of given location.
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isDistrictMustMatchAdminCodes(Location $location): bool
    {
        return $this->isMustMatchAdminCodes($location);
    }

    /**
     * Returns the borough "must match admin codes" flag of given location.
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isBoroughMustMatchAdminCodes(Location $location): bool
    {
        return $this->isMustMatchAdminCodes($location, 'borough');
    }

    /**
     * Returns the city "must match admin codes" flag of given location.
     *
     * @param Location $location
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function isCityMustMatchAdminCodes(Location $location): bool
    {
        return $this->isMustMatchAdminCodes($location, 'city');
    }

    /**
     * Returns the limit for given feature class and feature code.
     *
     * @param string|null $featureClass
     * @param string|null $featureCode
     * @param string|null $country
     * @param mixed|null $default
     * @return int
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    public function getLimit(
        string|null $featureClass = null,
        string|null $featureCode = null,
        string|null $country = null,
        mixed $default = null
    ): int
    {
        $limit = $this->getConfigNextPlaces('limit', $featureClass, $featureCode, $country, $default);

        if (is_int($limit)) {
            return $limit;
        }

        throw new CaseUnsupportedException(sprintf(
            'The distance configuration returns an invalid value: %s (%s)',
            (new TypeCastingHelper($limit))->strval(),
            gettype($limit)
        ));
    }

    /**
     * Returns the distance for given feature class and feature code.
     *
     * @param string|null $featureClass
     * @param string|null $featureCode
     * @param string|null $country
     * @param mixed|null $default
     * @return int|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    public function getDistance(
        string|null $featureClass = null,
        string|null $featureCode = null,
        string|null $country = null,
        mixed $default = null
    ): int|null
    {
        $distance = $this->getConfigNextPlaces('distance', $featureClass, $featureCode, $country, $default);

        if (is_null($distance) || is_int($distance)) {
            return $distance;
        }

        throw new CaseUnsupportedException(sprintf(
            'The distance configuration returns an invalid value: %s (%s)',
            (new TypeCastingHelper($distance))->strval(),
            gettype($distance)
        ));
    }

    /**
     * Returns the limit according to the given query.
     *
     * @param Query $query
     * @return int|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws ParserException
     * @throws TypeInvalidException
     * @throws QueryParserException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getLimitByQuery(Query $query): int|null
    {
        $country = $query->getCountry();

        $queryParser = $query->getQueryParser();

        /* Check if the limit was given by query (first via search term, second via url parameter). */
        $limit = $queryParser?->getLimit() ?? $query->getLimit();
        if (!is_null($limit)) {
            return $limit;
        }

        /* Check if the query parser is available. */
        if (is_null($queryParser)) {
            return $this->getLimit(country: $country);
        }

        $featureClasses = $queryParser->getFeatureClasses();
        $featureCodes = $queryParser->getFeatureCodes();

        if (is_null($featureClasses) && is_null($featureCodes)) {
            return $this->getLimit(country: $country);
        }

        $featureCodeTranslator = new FeatureCode($this->translator);

        $limit = 0;

        if (is_array($featureCodes)) {
            foreach ($featureCodes as $featureCode) {
                $featureClass = $featureCodeTranslator->getFeatureClass($featureCode);

                $limit = max($limit, $this->getLimit(
                    featureClass: $featureClass,
                    featureCode: $featureCode,
                    country: $country
                ));
            }

            return $limit > 0 ? $limit : $this->getLimit(country: $country);
        }

        foreach ($featureClasses as $featureClass) {

            $limit = max($limit, $this->getLimit(
                featureClass: $featureClass,
                country: $country
            ));
        }

        return $limit > 0 ? $limit : $this->getLimit(country: $country);
    }

    /**
     * Returns the distance according to the given query.
     *
     * @param Query $query
     * @return int
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws ParserException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getDistanceByQuery(Query $query): int
    {
        $country = $query->getCountry();
        $queryParser = $query->getQueryParser();

        /* Check if the distance was given by query. */
        $distance = $queryParser?->getDistance() ?? $query->getDistance();
        if (!is_null($distance)) {
            return $distance;
        }

        /* Check if the query parser is available. */
        if (is_null($queryParser)) {
            $distance = $this->getDistance(country: $country);
            return $distance ?? Distance::DISTANCE_1000;
        }

        $featureClasses = $queryParser->getFeatureClasses();
        $featureCodes = $queryParser->getFeatureCodes();

        if (is_null($featureClasses) && is_null($featureCodes)) {
            $distance = $this->getDistance(country: $country);
            return $distance ?? Distance::DISTANCE_1000;
        }

        $featureCodeTranslator = new FeatureCode($this->translator);

        $distance = 0;

        if (is_array($featureCodes)) {
            foreach ($featureCodes as $featureCode) {
                $featureClass = $featureCodeTranslator->getFeatureClass($featureCode);

                $distanceFeatureCode = $this->getDistance(
                    featureClass: $featureClass,
                    featureCode: $featureCode,
                    country: $country
                );

                if (is_null($distanceFeatureCode)) {
                    continue;
                }

                $distance = max($distance, $distanceFeatureCode);
            }

            if ($distance > 0) {
                return $distance;
            }

            $distance = $this->getDistance(country: $country);
            return $distance ?? Distance::DISTANCE_1000;
        }

        foreach ($featureClasses as $featureClass) {
            $distanceFeatureClass = $this->getDistance(
                featureClass: $featureClass,
                country: $country
            );

            if (is_null($distanceFeatureClass)) {
                continue;
            }

            $distance = max($distance, $distanceFeatureClass);
        }

        if ($distance > 0) {
            return $distance;
        }

        $distance = $this->getDistance(country: $country);
        return $distance ?? Distance::DISTANCE_1000;
    }

    /**
     * Returns the flag "use_admin_codes_general" for given feature class and feature code.
     *
     * @param string|null $featureClass
     * @param string|null $featureCode
     * @param mixed|null $default
     * @return bool
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function isUseAdminCodesGeneral(string|null $featureClass = null, string|null $featureCode = null, mixed $default = null): bool
    {
        $useAdminCodesGeneral = $this->getConfigNextPlaces(
            'use_admin_codes_general',
            $featureClass,
            $featureCode,
            null,
            $default
        );

        if (is_bool($useAdminCodesGeneral)) {
            return $useAdminCodesGeneral;
        }

        throw new TypeInvalidException('bool', gettype($useAdminCodesGeneral));
    }

    /**
     * Returns the flag "use_location_country" for given feature class and feature code.
     *
     * @param string|null $featureClass
     * @param string|null $featureCode
     * @param mixed|null $default
     * @return bool
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function isUseLocationCountry(string|null $featureClass = null, string|null $featureCode = null, mixed $default = null): bool
    {
        $useLocationCountry = $this->getConfigNextPlaces(
            'use_location_country',
            $featureClass,
            $featureCode,
            null,
            $default
        );

        if (is_bool($useLocationCountry)) {
            return $useLocationCountry;
        }

        throw new TypeInvalidException('bool', gettype($useLocationCountry));
    }

    /**
     * Returns the feature codes by given feature class.
     *
     * @param string $featureClass
     * @return array<int, string>
     * @throws CaseUnsupportedException
     */
    public function getFeatureCodesByFeatureClass(string $featureClass = FeatureClass::P): array
    {
        $featureCodes = match ($featureClass) {
            FeatureClass::A => $this->parameterBag->get(self::FEATURE_CODES_A_ALL),
            FeatureClass::H => $this->parameterBag->get(self::FEATURE_CODES_H_ALL),
            FeatureClass::L => $this->parameterBag->get(self::FEATURE_CODES_L_ALL),
            FeatureClass::P => $this->parameterBag->get(self::FEATURE_CODES_P_ALL),
            FeatureClass::R => $this->parameterBag->get(self::FEATURE_CODES_R_ALL),
            FeatureClass::S => $this->parameterBag->get(self::FEATURE_CODES_S_ALL),
            FeatureClass::T => $this->parameterBag->get(self::FEATURE_CODES_T_ALL),
            FeatureClass::U => $this->parameterBag->get(self::FEATURE_CODES_U_ALL),
            FeatureClass::V => $this->parameterBag->get(self::FEATURE_CODES_V_ALL),
            default => throw new CaseUnsupportedException(sprintf('Feature class "%s" is not supported.', $featureClass)),
        };

        if (!is_array($featureCodes)) {
            throw new CaseUnsupportedException('The feature_codes array is not an array.');
        }

        return $featureCodes;
    }
}
