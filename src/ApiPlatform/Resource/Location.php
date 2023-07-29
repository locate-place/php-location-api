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

namespace App\ApiPlatform\Resource;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\ApiPlatform\OpenApiContext\Name;
use App\ApiPlatform\OpenApiContext\Parameter;
use App\ApiPlatform\Route\LocationRoute;
use App\ApiPlatform\State\LocationProvider;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Class Location
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 */
#[GetCollection(
    openapiContext: [
        'description' => LocationRoute::DESCRIPTION_COLLECTION_GET,
        'parameters' => [
            Parameter::COORDINATE,
            Parameter::DISTANCE,
            Parameter::FEATURE_CLASS,
        ],
    ],
    provider: LocationProvider::class
)]
#[Get(
    uriVariables: [
        Name::GEONAME_ID,
    ],
    openapiContext: [
        'description' => LocationRoute::DESCRIPTION,
        'parameters' => [
            Parameter::GEONAME_ID,
        ]
    ],
    provider: LocationProvider::class
)]
class Location extends BasePublicResource
{
    #[SerializedName('geoname-id')]
    protected int $geonameId;

    protected string $name;

    /** @var array{code: string, name: string} $country */
    protected array $country;

    /** @var array{class: string, class-name: string, code: string, code-name: string} $feature */
    protected array $feature;

    /** @var array{latitude: float, longitude: float, distance: null|array{meters: float, kilometers: float}} $coordinate */
    protected array $coordinate;

    /** @var array{
     *     district-locality: string|null,
     *     city-municipality: string|null,
     *     state: string|null,
     *     country: string|null
     * } $location */
    protected array $location;

    /** @var array{google: string, openstreetmap: string} */
    protected array $link;

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
     * Gets the country.
     *
     * @return array{code: string, name: string}
     */
    public function getCountry(): array
    {
        return $this->country;
    }

    /**
     * Sets the country.
     *
     * @param array{code: string, name: string} $country
     * @return Location
     */
    public function setCountry(array $country): Location
    {
        $this->country = $country;
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
     * @return array{latitude: float, longitude: float, distance: null|array{meters: float, kilometers: float}}
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
     *     distance: null|array{meters: float, kilometers: float},
     *     direction: null|array{degree: float, direction: string},
     * } $coordinate
     * @return self
     */
    public function setCoordinate(array $coordinate): self
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * Returns the location array.
     *
     * @return array{
     *     district-locality: string|null,
     *     city-municipality: string|null,
     *     state: string|null,
     *     country: string|null
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
     *     district-locality: string|null,
     *     city-municipality: string|null,
     *     state: string|null,
     *     country: string|null
     * } $location
     * @return Location
     */
    public function setLocation(array $location): Location
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Gets the link array.
     *
     * @return array{google: string, openstreetmap: string}
     */
    public function getLink(): array
    {
        return $this->link;
    }

    /**
     * Sets the link array.
     *
     * @param array{google: string, openstreetmap: string} $link
     * @return self
     */
    public function setLink(array $link): self
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Returns the distance in meters.
     *
     * @return float|null
     */
    #[Ignore]
    public function getMeters(): ?float
    {
        $coordinate = $this->getCoordinate();

        $distance = $coordinate['distance'];

        if (is_null($distance)) {
            return null;
        }

        return $distance['meters'];
    }
}
