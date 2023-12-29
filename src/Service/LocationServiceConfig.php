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

namespace App\Service;

use App\Entity\Location;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class LocationServiceConfig
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-24)
 * @since 0.1.0 (2023-08-24) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
final class LocationServiceConfig
{
    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(protected ParameterBagInterface $parameterBag)
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
     * @return array<string, mixed>
     * @throws CaseUnsupportedException
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getCountryConfig(string|null $countryCode, string $type = 'district'): array
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

        if (!is_array($configType)) {
            throw new CaseUnsupportedException(sprintf('The given config for country.%s %s is not an array.', $type, $countryCode));
        }

        return $configType;
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
        $countryCode = $location->getCountry()?->getCode();

        if (is_null($countryCode)) {
            throw new CaseUnsupportedException('Unable to get country code from location.');
        }

        $districtMatch = $this->parameterBag->get('district_match');

        if (!is_array($districtMatch)) {
            throw new CaseUnsupportedException('The given district_match configuration is not an array.');
        }

        $adminCode = array_key_exists($countryCode, $districtMatch) ?
            $districtMatch[$countryCode] :
            'a4'
        ;

        return match ($adminCode) {
            'a0' => [],
            'a1' => ['a1' => (string) $location->getAdminCode()?->getAdmin1Code()],
            'a2' => ['a2' => (string) $location->getAdminCode()?->getAdmin2Code()],
            'a3' => ['a3' => (string) $location->getAdminCode()?->getAdmin3Code()],
            default => ['a4' => (string) $location->getAdminCode()?->getAdmin4Code()]
        };
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
     */
    private function getValueFromConfig(string $key, Location|null $location, string $type = 'district'): mixed
    {
        $countryCode = $this->getCountryCode($location);

        $countryConfig = $this->getCountryConfig($countryCode, $type);

        $exceptionMatch = $this->getExceptionMatchConfig($countryConfig, $location);

        /* Return the exception value. */
        if (!is_null($exceptionMatch) && array_key_exists($key, $exceptionMatch)) {
            return $exceptionMatch[$key];
        }

        /* Return the value for key from default or country settings. */
        if (array_key_exists($key, $countryConfig)) {
            return $countryConfig[$key];
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
     * @param mixed|null $default
     * @return mixed
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getConfig(string $key, string|null $featureClass = null, string|null $featureCode = null, mixed $default = null): mixed
    {
        $nextPlaces = $this->parameterBag->get('next_places');

        if (!is_array($nextPlaces)) {
            throw new TypeInvalidException('array', gettype($nextPlaces));
        }

        $config = array_key_exists($key, $nextPlaces) ? $nextPlaces[$key] : [];

        if (!is_array($config)) {
            throw new TypeInvalidException('array', gettype($config));
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

        if (!is_null($featureClass)) {
            $featureClassConfig = array_key_exists('feature_class', $config) ? $config['feature_class'] : [];

            if (!is_array($featureClassConfig)) {
                throw new TypeInvalidException('array', gettype($featureClassConfig));
            }

            if (array_key_exists($featureClass, $featureClassConfig)) {
                return $featureClassConfig[$featureClass];
            }
        }

        return array_key_exists('default', $config) ? $config['default'] : $default;
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
     * @param mixed|null $default
     * @return int
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     */
    public function getLimit(string|null $featureClass = null, string|null $featureCode = null, mixed $default = null): int
    {
        $limit = $this->getConfig('limit', $featureClass, $featureCode, $default);

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
     * @param mixed|null $default
     * @return int|null
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     */
    public function getDistance(string|null $featureClass = null, string|null $featureCode = null, mixed $default = null): int|null
    {
        $distance = $this->getConfig('distance', $featureClass, $featureCode, $default);

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
     * Returns the flag "use_admin_codes_general" for given feature class and feature code.
     *
     * @param string|null $featureClass
     * @param string|null $featureCode
     * @param mixed|null $default
     * @return bool
     * @throws TypeInvalidException
     */
    public function isUseAdminCodesGeneral(string|null $featureClass = null, string|null $featureCode = null, mixed $default = null): bool
    {
        $useAdminCodesGeneral = $this->getConfig('use_admin_codes_general', $featureClass, $featureCode, $default);

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
     * @throws TypeInvalidException
     */
    public function isUseLocationCountry(string|null $featureClass = null, string|null $featureCode = null, mixed $default = null): bool
    {
        $useLocationCountry = $this->getConfig('use_location_country', $featureClass, $featureCode, $default);

        if (is_bool($useLocationCountry)) {
            return $useLocationCountry;
        }

        throw new TypeInvalidException('bool', gettype($useLocationCountry));
    }
}
