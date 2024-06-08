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
        'summary' => LocationRoute::SUMMARY_GET_COLLECTION_SEARCH,
        'description' => LocationRoute::DESCRIPTION_GET_COLLECTION_SEARCH,
        'parameters' => [
            Parameter::QUERY,
            Parameter::LOCALE,
            Parameter::POSITION,
            Parameter::DISTANCE,
            Parameter::LIMIT,
            Parameter::PAGE,
        ],
    ],
    provider: LocationProvider::class
)]
/* Get resources via geoname id: /api/v1/location/airports */
#[GetCollection(
    uriTemplate: 'location/airports{._format}',
    openapiContext: [
        'summary' => LocationRoute::SUMMARY_GET_COLLECTION_AIRPORTS,
        'description' => LocationRoute::DESCRIPTION_GET_COLLECTION_AIRPORTS,
        'parameters' => [
            Parameter::LOCALE,
            Parameter::POSITION,
            Parameter::DISTANCE,
            Parameter::LIMIT,
            Parameter::PAGE,
        ],
    ],
    provider: LocationProvider::class
)]
/* Get resources via geoname id: /api/v1/location/capitals */
#[GetCollection(
    uriTemplate: 'location/capitals{._format}',
    openapiContext: [
        'summary' => LocationRoute::SUMMARY_GET_COLLECTION_CAPITOLS,
        'description' => LocationRoute::DESCRIPTION_GET_COLLECTION_CAPITOLS,
        'parameters' => [
            Parameter::LOCALE,
            Parameter::POSITION,
            Parameter::DISTANCE,
            Parameter::LIMIT,
            Parameter::PAGE,
        ],
    ],
    provider: LocationProvider::class
)]
/* Get resources via geoname id: /api/v1/location/examples */
#[GetCollection(
    uriTemplate: 'location/examples{._format}',
    openapiContext: [
        'summary' => LocationRoute::SUMMARY_GET_COLLECTION_EXAMPLES,
        'description' => LocationRoute::DESCRIPTION_GET_COLLECTION_EXAMPLES,
        'parameters' => [
            Parameter::LOCALE,
        ],
    ],
    paginationEnabled: false,
    provider: LocationProvider::class
)]
/* Get resource via location: /api/v1/location/coordinate/{coordinate} */
#[Get(
    uriTemplate: 'location/coordinate{._format}',
    openapiContext: [
        'summary' => LocationRoute::SUMMARY_GET_COORDINATE,
        'description' => LocationRoute::DESCRIPTION_GET_COORDINATE,
        'parameters' => [
            Parameter::QUERY_COORDINATE,
            Parameter::NEXT_PLACES,
            Parameter::LOCALE,
        ],
        'responses' => [
            '200' => [
                'description' => 'Location resource',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => "#/components/schemas/Location"
                        ]
                    ]
                ]
            ]
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
        'summary' => LocationRoute::SUMMARY_GET_GEONAME_ID,
        'description' => LocationRoute::DESCRIPTION_GET_GEONAME_ID,
        'parameters' => [
            Parameter::GEONAME_ID,
            Parameter::NEXT_PLACES,
            Parameter::LOCALE,
        ],
        'responses' => [
            '200' => [
                'description' => 'Location resource',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => "#/components/schemas/Location"
                        ]
                    ]
                ]
            ]
        ],
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
