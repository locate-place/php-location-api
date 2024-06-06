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

namespace App\ApiPlatform\Type;

/**
 * Class AutocompleteLocation
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-01)
 * @since 0.1.0 (2024-06-01) First version.
 */
class AutocompleteLocation
{
    private int $id;

    private string $name;

    private string|null $country = null;

    private string|null $countryName = null;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return AutocompleteLocation
     */
    public function setId(int $id): AutocompleteLocation
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return AutocompleteLocation
     */
    public function setName(string $name): AutocompleteLocation
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     * @return AutocompleteLocation
     */
    public function setCountry(?string $country): AutocompleteLocation
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    /**
     * @param string|null $countryName
     * @return AutocompleteLocation
     */
    public function setCountryName(?string $countryName): AutocompleteLocation
    {
        $this->countryName = $countryName;
        return $this;
    }
}
