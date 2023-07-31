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

namespace App\Service\Base\Helper;

use App\ApiPlatform\Resource\Location;
use App\Constants\DB\FeatureClass;
use App\Entity\Location as LocationEntity;
use App\Repository\LocationRepository;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class BaseHelperLocationService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 */
abstract class BaseHelperLocationService
{
    protected ?string $error = null;

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
     * Returns country name by given location.
     *
     * @param LocationEntity $location
     * @return string|null
     */
    public function findCountry(LocationEntity $location): string|null
    {
        $countryCode = $location->getCountry()?->getCode();

        if (is_null($countryCode)) {
            return null;
        }

        $countryCode = strtolower($countryCode);

        return $this->translator->trans(sprintf('country.alpha2.%s', $countryCode), [], 'countries', 'en');
    }

    /**
     * Finds next admin place.
     *
     * @param LocationEntity[] $locationsP
     * @param LocationEntity $district
     * @return LocationEntity|null
     */
    public function findNextAdminCity(array $locationsP, LocationEntity $district): LocationEntity|null
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
     * @param LocationEntity[] $locationsP
     * @param LocationEntity $district
     * @return LocationEntity|null
     */
    public function findNextCityPopulation(array $locationsP, LocationEntity $district): ?LocationEntity
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
     * @param LocationEntity[] $locationsP
     * @param LocationEntity $city
     * @return LocationEntity|null
     */
    protected function findNextDistrict(array $locationsP, LocationEntity $city): LocationEntity|null
    {
        foreach ($locationsP as $locationP) {
            switch (true) {
                case in_array($locationP->getFeatureCode(), FeatureClass::FEATURE_CODES_P_DISTRICT_PLACES) && $city->getAdminCode()?->getAdmin4Code() == $locationP->getAdminCode()?->getAdmin4Code():
                    return $locationP;
            }
        }

        return null;
    }

    /**
     * Gets an error of this resource.
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Checks if an error occurred.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return is_string($this->error);
    }

    /**
     * Sets an error of this resource.
     *
     * @param string|null $error
     * @return self
     */
    protected function setError(?string $error): self
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Returns an empty Location entity.
     *
     * @param int|null $geonameId
     * @return Location
     */
    protected function getEmptyLocation(int|null $geonameId = null): Location
    {
        $location = new Location();

        if (!is_null($geonameId)) {
            $location->setGeonameId($geonameId);
        }

        return $location;
    }
}
