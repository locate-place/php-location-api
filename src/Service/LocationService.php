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

use App\Constants\DB\FeatureClass;
use App\Entity\Location;
use App\Repository\LocationRepository;
use Doctrine\ORM\NonUniqueResultException;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class LocationService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-29)
 * @since 0.1.0 (2023-07-29) First version.
 */
final class LocationService
{
    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     * @param LocationRepository $locationRepository
     * @param TranslatorInterface $translator
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request,
        protected LocationRepository $locationRepository,
        protected TranslatorInterface $translator
    )
    {
    }

    /**
     * Returns some location information (district, city, state, country, etc.).
     *
     * @param array<int, Location> $locationsP
     * @return array{
     *     district-locality: string|null,
     *     city-municipality: string|null,
     *     state: string|null,
     *     country: string|null
     * }|null
     * @throws CaseUnsupportedException
     * @throws NonUniqueResultException
     * @throws TypeInvalidException
     * @throws ClassInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getLocationInformation(array $locationsP): array|null
    {
        $locationP = count($locationsP) > 0 ? $locationsP[0] : null;

        if (is_null($locationP)) {
            return null;
        }

        $featureCode = $locationP->getFeatureCode()?->getCode();

        switch (true) {
            /* Location: Add administrative information */
            case in_array($featureCode, FeatureClass::FEATURE_CODES_P_DISTRICT_PLACES):

                $district = $locationP;

                $city1 = $this->locationRepository->findCityByLocationDistrict($locationP);
                $city2 = $this->findNextAdminCity($locationsP, $district);
                $city3 = $this->findNextCityPopulation($locationsP, $district);

                $city = match (true) {
                    $city1 === null => $city2 !== null && (int) $city2->getPopulation() > 0 ? $city2 : $city3,
                    default => $city1,
                };
                break;

            /* Location: Add administrative information (Admin place) */
            case in_array($featureCode, FeatureClass::FEATURE_CODES_P_ADMIN_PLACES):
                $city = $locationP;
                $district = $this->findNextDistrict($locationsP, $city);
                break;

            default:
                throw new CaseUnsupportedException(sprintf('Unsupported FeatureCode "%s" given.', $featureCode));
        }

        /* Disable district in some cases. */
        if ($district !== null && $city !== null && $district->getName() === $city->getName()) {
            $district = null;
        }

        $state = $this->locationRepository->findStateByLocation($locationP);

        return [
            'district-locality' => $district?->getName(),
            'city-municipality' => $city?->getName(),
            'state' => $state?->getName(),
            'country' => $this->getCountryByPlace($locationP),
        ];
    }

    /**
     * Returns country name by given place.
     *
     * @param Location $place
     * @return string|null
     */
    public function getCountryByPlace(Location $place): string|null
    {
        $countryCode = $place->getCountry()?->getCode();

        if (is_null($countryCode)) {
            return null;
        }

        $countryCode = strtolower($countryCode);

        return $this->translator->trans(sprintf('country.alpha2.%s', $countryCode), [], 'countries', 'en');
    }

    /**
     * Finds next admin place.
     *
     * @param Location[] $locationsP
     * @param Location $district
     * @return Location|null
     */
    public function findNextAdminCity(array $locationsP, Location $district): Location|null
    {
        /* Get the nearest placeP entry with equal admin4 code. */
        foreach ($locationsP as $locationP) {
            switch (true) {
                case in_array($locationP->getFeatureCode()?->getCode(), FeatureClass::FEATURE_CODES_P_ADMIN_PLACES) &&
                    $district->getAdminCode()?->getAdmin4Code() == $locationP->getAdminCode()?->getAdmin4Code():
                    return $locationP;
            }
        }

        /* Get the nearest placeP entry if no equal admin4 code was found. */
        foreach ($locationsP as $locationP) {
            switch (true) {
                case in_array($locationP->getFeatureCode(), FeatureClass::FEATURE_CODES_P_ADMIN_PLACES):
                    return $locationP;
            }
        }

        return null;
    }

    /**
     * Finds next place with more than 0 population from given district.
     *
     * @param Location[] $locationsP
     * @param Location $district
     * @return Location|null
     */
    public function findNextCityPopulation(array $locationsP, Location $district): ?Location
    {
        if ((int) $district->getPopulation() > 0) {
            return null;
        }

        foreach ($locationsP as $locationP) {
            switch (true) {
                case (int) $locationP->getPopulation() > 0 && $district->getAdminCode()?->getAdmin4Code() == $locationP->getAdminCode()?->getAdmin4Code():
                    $district->setPopulation($locationP->getPopulation());
                    return $locationP;
            }
        }

        return null;
    }

    /**
     * Finds next admin place.
     *
     * @param Location[] $locationsP
     * @param Location $city
     * @return Location|null
     */
    public function findNextDistrict(array $locationsP, Location $city): Location|null
    {
        foreach ($locationsP as $locationP) {
            switch (true) {
                case in_array($locationP->getFeatureCode(), FeatureClass::FEATURE_CODES_P_DISTRICT_PLACES) && $city->getAdminCode()?->getAdmin4Code() == $locationP->getAdminCode()?->getAdmin4Code():
                    return $locationP;
            }
        }

        return null;
    }
}
