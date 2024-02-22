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
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class LocationContainer
 *
 * Location wrapper class for the following places (XXX):
 * - district
 * - borough
 * - city
 * - state
 * - country
 *
 * Offers the following setter and getter methods:
 * - getXXX (App\Entity\Location)
 * - setXXX (App\Entity\Location)
 * - hasXXX (bool)
 * - getXXXName (string)
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

    final public const ALLOWED_LOCATION_TYPES = [
        self::TYPE_DISTRICT,
        self::TYPE_BOROUGH,
        self::TYPE_CITY,
        self::TYPE_STATE,
        self::TYPE_COUNTRY,
    ];

    protected Location|null $district = null;

    protected Location|null $borough = null;

    protected Location|null $city = null;

    protected Location|null $state = null;

    protected Location|null $country = null;



    /**
     * @param LocationServiceAlternateName|null $locationServiceAlternateName
     */
    public function __construct(protected LocationServiceAlternateName|null $locationServiceAlternateName = null)
    {
    }



    /**
     * @return Location|null
     */
    public function getDistrict(): ?Location
    {
        return $this->district;
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
     * @return bool
     */
    public function hasDistrict(): bool
    {
        return $this->district instanceof Location;
    }

    /**
     * Returns the district name.
     *
     * @param string $isoLanguage
     * @return string|null
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
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
     * @return Location|null
     */
    public function getBorough(): ?Location
    {
        return $this->borough;
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
     * @return bool
     */
    public function hasBorough(): bool
    {
        return $this->borough instanceof Location;
    }

    /**
     * Returns the borough name.
     *
     * @param string $isoLanguage
     * @return string|null
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
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
     * @return Location|null
     */
    public function getCity(): ?Location
    {
        return $this->city;
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
     * @return bool
     */
    public function hasCity(): bool
    {
        return $this->city instanceof Location;
    }

    /**
     * Returns the city name.
     *
     * @param string $isoLanguage
     * @return string|null
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
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
     * @return Location|null
     */
    public function getState(): ?Location
    {
        return $this->state;
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
     * @return bool
     */
    public function hasState(): bool
    {
        return $this->state instanceof Location;
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
     * @return Location|null
     */
    public function getCountry(): ?Location
    {
        return $this->country;
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
     * @return bool
     */
    public function hasCountry(): bool
    {
        return $this->country instanceof Location;
    }

    /**
     * @param string $isoLanguage
     * @return string|null
     * @throws ClassInvalidException
     * @throws TypeInvalidException
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
     * Returns the alternate name of the given location type and language.
     *
     * @param Location|null $location
     * @param string $isoLanguage
     * @param bool $useLocationName
     * @param string|null $language
     * @return string|null
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function getAlternateName(Location|null $location, string $isoLanguage, bool $useLocationName = false, string $language = null): string|null
    {
        if (is_null($location)) {
            return null;
        }

        if (is_null($this->locationServiceAlternateName)) {
            return $useLocationName ? $location->getName() : null;
        }

        return $this->locationServiceAlternateName->getNameByIsoLanguage($location, $isoLanguage, $language);
    }
}
