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
    protected int $geonameId;

    protected string $name;

    /** @var array{class: string, code: string} $feature */
    protected array $feature;

    /** @var array{latitude: float, longitude: float} $coordinate */
    protected array $coordinate;

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
     * @return array{class: string, code: string}
     */
    public function getFeature(): array
    {
        return $this->feature;
    }

    /**
     * Sets the feature.
     *
     * @param array{class: string, code: string} $feature
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
     * @return array{latitude: float, longitude: float}
     */
    public function getCoordinate(): array
    {
        return $this->coordinate;
    }

    /**
     * Sets the coordinate array.
     *
     * @param array{latitude: float, longitude: float} $coordinate
     * @return self
     */
    public function setCoordinate(array $coordinate): self
    {
        $this->coordinate = $coordinate;

        return $this;
    }
}
