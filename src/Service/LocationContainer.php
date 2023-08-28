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
    protected Location|null $district = null;

    protected Location|null $borough = null;

    protected Location|null $city = null;

    protected Location|null $state = null;

    protected Location|null $country = null;

    /**
     * @return Location|null
     */
    public function getDistrict(): ?Location
    {
        return $this->district;
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
}
