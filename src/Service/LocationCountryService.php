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
 * Class LocationCountryService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-24)
 * @since 0.1.0 (2023-08-24) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class LocationCountryService
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
     * @param Location $location
     * @return string
     * @throws CaseUnsupportedException
     */
    private function getCountryCode(Location $location): string
    {
        $countryCode = $location->getCountry()?->getCode();

        if (is_null($countryCode)) {
            throw new CaseUnsupportedException('Unable to get country code from location.');
        }

        return $countryCode;
    }

    /**
     * Returns the country config.
     *
     * @param string $countryCode
     * @param string $type
     * @return array<string, mixed>
     * @throws CaseUnsupportedException
     */
    private function getCountryConfig(string $countryCode, string $type = 'district'): array
    {
        $locationCountry = $this->parameterBag->get('location_country');

        if (!is_array($locationCountry)) {
            throw new CaseUnsupportedException('The given location_country configuration is not an array.');
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
     * @return array{a1?: string, a2?: string, a3?: string, a4?: string}
     * @throws TypeInvalidException
     */
    private function getAdminCodesFromConfig(array $adminCodeConfig): array
    {
        $adminCodes = [];

        foreach (['a1', 'a2', 'a3', 'a4'] as $adminCode) {
            if (!array_key_exists($adminCode, $adminCodeConfig)) {
                continue;
            }

            $adminCodes[$adminCode] = (new TypeCastingHelper($adminCodeConfig[$adminCode]))->strval();
        }

        return $adminCodes;
    }

    /**
     * Returns the exception configuration if exists.
     *
     * @param array<string, mixed> $countryConfig
     * @param Location $location
     * @return array<string, mixed>|null
     * @throws CaseUnsupportedException
     */
    private function getExceptionMatchConfig(array $countryConfig, Location $location): ?array
    {
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

            $adminCodes = $exception['admin_codes'];

            $match = true;

            foreach ($adminCodes as $key => $adminCode) {
                $value = match (true) {
                    $key === 'a1' => $location->getAdminCode()?->getAdmin1Code(),
                    $key === 'a2' => $location->getAdminCode()?->getAdmin2Code(),
                    $key === 'a3' => $location->getAdminCode()?->getAdmin3Code(),
                    $key === 'a4' => $location->getAdminCode()?->getAdmin4Code(),
                    default => throw new CaseUnsupportedException(sprintf('The given admin key %s is not supported.', $key))
                };

                if ($value !== $adminCode) {
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
     * @param Location $location
     * @param string $type
     * @return mixed
     * @throws CaseUnsupportedException
     */
    private function getValueFromConfig(string $key, Location $location, string $type = 'district'): mixed
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

        throw new CaseUnsupportedException(sprintf(
            'The given key "%s" was not found.',
            $key
        ));
    }

    /**
     * Returns the feature class of given country.
     *
     * @param Location $location
     * @param string $type
     * @return string
     * @throws CaseUnsupportedException
     */
    private function getFeatureClass(Location $location, string $type = 'district'): string
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
     * @param Location $location
     * @param string $type
     * @return array<int, string>
     * @throws CaseUnsupportedException
     */
    private function getFeatureCodes(Location $location, string $type = 'district'): array
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
     * Returns if the feature codes should be sorted of given location.
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

        $countryCode = $this->getCountryCode($location);

        $countryConfig = $this->getCountryConfig($countryCode, $type);

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

        return $this->getAdminCodesFromConfig($adminCodeConfig);
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
     * Returns the admin codes for district.
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
}
