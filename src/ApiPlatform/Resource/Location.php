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
use App\Constants\Key\KeyArray;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * Class Location
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 */

/* Get resources via geoname id: /api/v1/location */
#[GetCollection(
    openapiContext: [
        'description' => LocationRoute::DESCRIPTION_COLLECTION_GET,
        'parameters' => [
            Parameter::QUERY,
            Parameter::DISTANCE,
            Parameter::LIMIT,
            Parameter::LANGUAGE,
            Parameter::COUNTRY,
            Parameter::COORDINATE_SHORT,
        ],
    ],
    provider: LocationProvider::class
)]
/* Get resources via geoname id: /api/v1/location/examples */
#[GetCollection(
    uriTemplate: 'location/examples.{_format}',
    openapiContext: [
        'description' => LocationRoute::DESCRIPTION_COLLECTION_GET,
        'parameters' => [
            Parameter::LANGUAGE,
            Parameter::COUNTRY,
            Parameter::COORDINATE_SHORT,
        ],
    ],
    provider: LocationProvider::class
)]
/* Get resources via geoname id: /api/v1/location/countries */
#[GetCollection(
    uriTemplate: 'location/countries.{_format}',
    openapiContext: [
        'description' => LocationRoute::DESCRIPTION_COLLECTION_GET,
        'parameters' => [
            Parameter::LANGUAGE,
            Parameter::COUNTRY,
            Parameter::COORDINATE_SHORT,
        ],
    ],
    provider: LocationProvider::class
)]
/* Get resources via geoname id: /api/v1/location/airports */
#[GetCollection(
    uriTemplate: 'location/airports.{_format}',
    openapiContext: [
        'description' => LocationRoute::DESCRIPTION_COLLECTION_GET,
        'parameters' => [
            Parameter::LANGUAGE,
            Parameter::COUNTRY,
            Parameter::COORDINATE_SHORT,
        ],
    ],
    provider: LocationProvider::class
)]
/* Get resource via geoname id: /api/v1/location/{geoname_id} */
#[Get(
    uriVariables: [
        Name::GEONAME_ID,
    ],
    openapiContext: [
        'description' => LocationRoute::DESCRIPTION,
        'parameters' => [
            Parameter::GEONAME_ID,
            Parameter::NEXT_PLACES,
            Parameter::LANGUAGE,
            Parameter::COUNTRY,
        ]
    ],
    provider: LocationProvider::class
)]
/* Get resource via location: /api/v1/location/coordinate/{coordinate} */
#[Get(
    uriTemplate: 'location/coordinate.{_format}',
    openapiContext: [
        'description' => LocationRoute::DESCRIPTION,
        'parameters' => [
            Parameter::QUERY,
            Parameter::NEXT_PLACES,
            Parameter::LANGUAGE,
            Parameter::COUNTRY,
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
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     */
    #[Ignore]
    public function getMeters(): ?float
    {
        $coordinate = $this->getCoordinate();

        $path = [KeyArray::DISTANCE, KeyArray::METERS, KeyArray::VALUE];

        if (!$coordinate->hasKey($path)) {
            return null;
        }

        return $coordinate->getKeyFloat($path);
    }
}
