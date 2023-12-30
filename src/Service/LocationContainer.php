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

/**
 * Class LocationContainer
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-28)
 * @since 0.1.0 (2023-08-28) First version.
 */
final class LocationContainer
{
    final public const TYPE_DISTRICT = 'district';

    final public const TYPE_BOROUGH = 'borough';

    final public const TYPE_CITY = 'city';

    final public const TYPE_STATE ='state';

    final public const TYPE_COUNTRY = 'country';

    protected Location|null $district = null;

    protected Location|null $borough = null;

    protected Location|null $city = null;

    protected Location|null $state = null;

    protected Location|null $country = null;

    public function __construct(protected LocationServiceAlternateName|null $locationServiceAlternateName = null)
    {
    }

    /**
     * @param LocationServiceAlternateName $locationServiceAlternateName
     * @return self
     */
    public function setLocationServiceAlternateName(LocationServiceAlternateName $locationServiceAlternateName): self
    {
        $this->locationServiceAlternateName = $locationServiceAlternateName;

        return $this;
    }

    /**
     * @return Location|null
     */
    public function getDistrict(): ?Location
    {
        return $this->district;
    }

    /**
     * @param string $isoLanguage
     * @return string|null
     */
    public function getDistrictName(string $isoLanguage): string|null
    {
        if (is_null($this->district)) {
            return null;
        }

        if (is_null($this->locationServiceAlternateName)) {
            return $this->district->getName();
        }

        return $this->locationServiceAlternateName->getNameByIsoLanguage($this->district, $isoLanguage);
    }

    /**
     * @return bool
     */
    public function hasDistrict(): bool
    {
        return $this->district instanceof Location;
    }

    /**
     * @param Location|null $district
     * @return self
     */
    public function setDistrict(?Location $district): self
    {
        $this->district = $district;

        return $this;
    }

    /**
     * @return Location|null
     */
    public function getBorough(): ?Location
    {
        return $this->borough;
    }

    /**
     * @param string $isoLanguage
     * @return string|null
     */
    public function getBoroughName(string $isoLanguage): string|null
    {
        if (is_null($this->borough)) {
            return null;
        }

        if (is_null($this->locationServiceAlternateName)) {
            return $this->borough->getName();
        }

        return $this->locationServiceAlternateName->getNameByIsoLanguage($this->borough, $isoLanguage);
    }

    /**
     * @return bool
     */
    public function hasBorough(): bool
    {
        return $this->borough instanceof Location;
    }

    /**
     * @param Location|null $borough
     * @return self
     */
    public function setBorough(?Location $borough): self
    {
        $this->borough = $borough;

        return $this;
    }

    /**
     * @return Location|null
     */
    public function getCity(): ?Location
    {
        return $this->city;
    }

    /**
     * @param string $isoLanguage
     * @return string|null
     */
    public function getCityName(string $isoLanguage): string|null
    {
        if (is_null($this->city)) {
            return null;
        }

        if (is_null($this->locationServiceAlternateName)) {
            return $this->city->getName();
        }

        return $this->locationServiceAlternateName->getNameByIsoLanguage($this->city, $isoLanguage);
    }

    /**
     * @return bool
     */
    public function hasCity(): bool
    {
        return $this->city instanceof Location;
    }

    /**
     * @param Location|null $city
     * @return self
     */
    public function setCity(?Location $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return Location|null
     */
    public function getState(): ?Location
    {
        return $this->state;
    }

    /**
     * @param string $isoLanguage
     * @return string|null
     */
    public function getStateName(string $isoLanguage): string|null
    {
        if (is_null($this->state)) {
            return null;
        }

        if (is_null($this->locationServiceAlternateName)) {
            return $this->state->getName();
        }

        return $this->locationServiceAlternateName->getNameByIsoLanguage($this->state, $isoLanguage);
    }

    /**
     * @return bool
     */
    public function hasState(): bool
    {
        return $this->state instanceof Location;
    }

    /**
     * @param Location|null $state
     * @return self
     */
    public function setState(?Location $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return Location|null
     */
    public function getCountry(): ?Location
    {
        return $this->country;
    }

    /**
     * @param string $isoLanguage
     * @return string|null
     */
    public function getCountryName(string $isoLanguage): string|null
    {
        if (is_null($this->country)) {
            return null;
        }

        if (is_null($this->locationServiceAlternateName)) {
            return $this->country->getName();
        }

        return $this->locationServiceAlternateName->getNameByIsoLanguage($this->country, $isoLanguage);
    }

    /**
     * @return bool
     */
    public function hasCountry(): bool
    {
        return $this->country instanceof Location;
    }

    /**
     * @param Location|null $country
     * @return self
     */
    public function setCountry(?Location $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Returns the alternate name of the given location type and language.
     *
     * @param Location|null $location
     * @param string $isoLanguage
     * @param bool $useLocationName
     * @return string|null
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function getAlternateName(Location|null $location, string $isoLanguage, bool $useLocationName = false): string|null
    {
        if (is_null($location)) {
            return null;
        }

        if (is_null($this->locationServiceAlternateName)) {
            return $useLocationName ? $location->getName() : null;
        }

        return $this->locationServiceAlternateName->getNameByIsoLanguage($location, $isoLanguage);
    }
}
