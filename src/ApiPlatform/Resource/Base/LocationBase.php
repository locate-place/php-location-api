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

namespace App\ApiPlatform\Resource\Base;

use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use LogicException;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Class LocationBase
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-30)
 * @since 0.1.0 (2023-12-30) First version.
 */
abstract class LocationBase extends BasePublicResource
{
    #[SerializedName('geoname-id')]
    protected int $geonameId;

    protected string $name;

    /** @var array{class: string, class-name: string, code: string, code-name: string} $feature */
    protected array $feature;

    /** @var array{latitude: float, longitude: float, srid: int, distance?: null|array{meters: float, kilometers: float}, direction?: null|array{degree: float, direction: string}} $coordinate */
    protected array $coordinate;

    /** @var array{timezone: string|null, country: string|null, current-time: string, offset: string, latitude: double, longitude: double} $timezone */
    protected array $timezone;

    /** @var array{
     *      district-locality?: array{name: string|null, geoname-id: int|null}|null,
     *      borough-locality?: array{name: string|null, geoname-id: int|null}|null,
     *      city-municipality?: array{name: string|null, geoname-id: int|null}|null,
     *      state?: array{name: string|null, geoname-id: int|null}|null,
     *      country?: array{name: string|null, geoname-id: int|null}|null
     *  } $location */
    protected array $location;

    /** @var array{
     *      google?: string,
     *      openstreetmap?: string,
     *      wikipedia?: array<string, string>|null
     *  }
     */
    protected array $link;

    protected int $population;

    protected int $elevation;

    protected int $dem;

    /**
     * Gets the geoname ID.
     *
     * @return int
     */
    public function getGeonameId(): int
    {
        return $this->geonameId;
    }

    /**
     * Sets the geoname ID.
     *
     * @param int $geonameId
     * @return self
     */
    public function setGeonameId(int $geonameId): self
    {
        $this->geonameId = $geonameId;

        return $this;
    }

    /**
     * Gets the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name.
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the feature.
     *
     * @return array{class: string, class-name: string, code: string, code-name: string}
     */
    public function getFeature(): array
    {
        return $this->feature;
    }

    /**
     * Sets the feature.
     *
     * @param array{class: string, class-name: string, code: string, code-name: string} $feature
     * @return self
     */
    public function setFeature(array $feature): self
    {
        $this->feature = $feature;

        return $this;
    }

    /**
     * Gets the coordinate array.
     *
     * @return array{latitude: float, longitude: float, srid: int, distance?: null|array{meters: float, kilometers: float}, direction?: null|array{degree: float, direction: string}}
     */
    public function getCoordinate(): array
    {
        return $this->coordinate;
    }

    /**
     * Sets the coordinate array.
     *
     * @param array{
     *     latitude: float,
     *     longitude: float,
     *     srid: int,
     *     distance?: null|array{meters: float, kilometers: float},
     *     direction?: null|array{degree: float, direction: string},
     * } $coordinate
     * @return self
     */
    public function setCoordinate(array $coordinate): self
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * @return array{timezone: string|null, country: string|null, current-time: string, offset: string, latitude: double, longitude: double}
     */
    public function getTimezone(): array
    {
        return $this->timezone;
    }

    /**
     * @param array{timezone: string|null, country: string|null, current-time: string, offset: string, latitude: double, longitude: double} $timezone
     * @return self
     */
    public function setTimezone(array $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Returns the location array.
     *
     * @return array{
     *     district-locality?: array{name: string|null, geoname-id: int|null}|null,
     *     borough-locality?: array{name: string|null, geoname-id: int|null}|null,
     *     city-municipality?: array{name: string|null, geoname-id: int|null}|null,
     *     state?: array{name: string|null, geoname-id: int|null}|null,
     *     country?: array{name: string|null, geoname-id: int|null}|null
     * }
     */
    public function getLocation(): array
    {
        return $this->location;
    }

    /**
     * Sets the location array.
     *
     * @param array{
     *     district-locality?: array{name: string|null, geoname-id: int|null}|null,
     *     borough-locality?: array{name: string|null, geoname-id: int|null}|null,
     *     city-municipality?: array{name: string|null, geoname-id: int|null}|null,
     *     state?: array{name: string|null, geoname-id: int|null}|null,
     *     country?: array{name: string|null, geoname-id: int|null}|null
     * } $location
     * @return self
     */
    public function setLocation(array $location): self
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Gets the link array.
     *
     * @return array{
     *     google?: string,
     *     openstreetmap?: string,
     *     wikipedia?: array<string, string>|null
     * }
     */
    public function getLink(): array
    {
        return $this->link;
    }

    /**
     * Sets the link array.
     *
     * @param array{
     *     google?: string,
     *     openstreetmap?: string,
     *     wikipedia?: array<string, string>|null
     * } $link
     * @return self
     */
    public function setLink(array $link): self
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Adds a link to array.
     *
     * @param string[]|string $path
     * @param string $value
     * @return void
     */
    public function addLink(array|string $path, string $value): void
    {
        $path = is_string($path) ? [$path] : $path;

        if (count($path) <= 0) {
            throw new LogicException('Path must contain at least one element');
        }

        if (!isset($this->link)) {
            $this->link = [];
        }

        $linkLoop = &$this->link;
        foreach ($path as $key) {
            if (!is_array($linkLoop)) {
                throw new LogicException('Link must be an array');
            }

            if (!array_key_exists($key, $linkLoop)) {
                $linkLoop[$key] = [];
            }

            $linkLoop = &$linkLoop[$key];
        }

        $linkLoop = $value;
    }

    /**
     * @return int
     */
    public function getPopulation(): int
    {
        return $this->population;
    }

    /**
     * @param int $population
     * @return self
     */
    public function setPopulation(int $population): self
    {
        $this->population = $population;

        return $this;
    }

    /**
     * @return int
     */
    public function getElevation(): int
    {
        return $this->elevation;
    }

    /**
     * @param int $elevation
     * @return self
     */
    public function setElevation(int $elevation): self
    {
        $this->elevation = $elevation;

        return $this;
    }

    /**
     * @return int
     */
    public function getDem(): int
    {
        return $this->dem;
    }

    /**
     * @param int $dem
     * @return self
     */
    public function setDem(int $dem): self
    {
        $this->dem = $dem;

        return $this;
    }
}
