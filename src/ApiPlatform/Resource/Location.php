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
use App\ApiPlatform\Resource\Base\LocationBase;
use App\ApiPlatform\Route\LocationRoute;
use App\ApiPlatform\State\LocationProvider;
use Symfony\Component\Serializer\Annotation\Ignore;

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
            Parameter::LIMIT,
            Parameter::FEATURE_CLASS,
        ],
    ],
    provider: LocationProvider::class
)]
/* Get ressource via geoname id: /api/v1/location/{geoname_id} */
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
/* Get ressource via location: /api/v1/location/coordinate/{coordinate} */
#[Get(
    uriTemplate: 'location/coordinate.{_format}',
    openapiContext: [
        'description' => LocationRoute::DESCRIPTION,
        'parameters' => [
            Parameter::COORDINATE,
            Parameter::LANGUAGE,
        ]
    ],
    provider: LocationProvider::class
)]
class Location extends LocationBase
{
    /**
     * Returns the distance in meters.
     *
     * @return float|null
     */
    #[Ignore]
    public function getMeters(): ?float
    {
        $coordinate = $this->getCoordinate();

        $distance = array_key_exists('distance', $coordinate) ? $coordinate['distance'] : null;

        if (is_null($distance)) {
            return null;
        }

        return $distance['meters'];
    }
}
