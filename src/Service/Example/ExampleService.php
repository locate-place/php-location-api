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

namespace App\Service\Example;

use App\Constants\Key\KeyArray;
use App\Constants\Language\CountryCode;
use App\Constants\Place\Location;
use App\Constants\Place\Search;
use App\Service\LocationServiceConfig;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class ExampleService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 */
final class ExampleService
{
    private const ENDPOINT_COORDINATE = '/api/v1/location/coordinate.json';

    private const ENDPOINT_LIST = '/api/v1/location.json';

    /**
     * @param ParameterBagInterface $parameterBag
     * @param LocationServiceConfig $locationServiceConfig
     */
    public function __construct(
        protected ParameterBagInterface $parameterBag,
        protected LocationServiceConfig $locationServiceConfig
    )
    {
    }

    /**
     * Returns the name from given location (Build name from location parts).
     *
     * @param Json $json
     * @param string $key
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getName(Json $json, string $key): string
    {
        $path = [$key, KeyArray::NAME];

        if ($json->hasKey($path)) {
            return $json->getKeyString($path);
        }

        return $this->getLocation($json, $key);
    }

    /**
     * Returns the name from given location (Build name from location parts).
     *
     * @param Json $json
     * @param string $key
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getLocation(Json $json, string $key): string
    {
        $nameParts = [];
        foreach (Location::ALL as $area) {
            $path = [$key, KeyArray::LOCATION, $area];

            if ($json->hasKey($path)) {
                $nameParts[] = $json->getKeyString($path);
            }
        }

        return implode(', ', $nameParts);
    }

    /**
     * Returns the examples.
     *
     * @return array<string, array<string, mixed>>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function getExamples(): array
    {
        $json = new Json(Search::VALUES);

        $examples = [];

        foreach ($json->getArray() as $key => $value) {
            $key = (string) $key;

            if (!is_array($value)) {
                throw new LogicException(sprintf('Value for key "%s" must be an array.', $key));
            }

            $country = array_key_exists('country', $value) && is_string($value['country']) ? $value['country'] : CountryCode::DEFAULT;

            $coordinate = new Coordinate(
                $json->getKeyFloat([$key, KeyArray::COORDINATE, KeyArray::LATITUDE]),
                $json->getKeyFloat([$key, KeyArray::COORDINATE, KeyArray::LONGITUDE])
            );

            /* Initialize the array */
            $examples[$key] = [
                /* Add name and country. */
                KeyArray::NAME => $this->getName($json, $key),
                KeyArray::LOCATION => $this->getLocation($json, $key),
                KeyArray::COUNTRY => $country,

                /* Add coordinates. */
                KeyArray::COORDINATE => [
                    KeyArray::LATITUDE => [
                        KeyArray::DECIMAL => $coordinate->getLatitudeDecimal(),
                        KeyArray::DMS => $coordinate->getLatitudeDMS(),
                    ],
                    KeyArray::LONGITUDE => [
                        KeyArray::DECIMAL => $coordinate->getLongitudeDecimal(),
                        KeyArray::DMS => $coordinate->getLongitudeDMS(),
                    ],
                ],

                /* Add links */
                KeyArray::LINKS => [
                    KeyArray::LINK_GOOGLE => $coordinate->getLinkGoogle(),
                    KeyArray::LINK_OPEN_STREET_MAP => $coordinate->getLinkOpenStreetMap(),
                ],

                /* Add endpoints. */
                KeyArray::ENDPOINTS => [
                    KeyArray::COORDINATE => self::ENDPOINT_COORDINATE,
                    KeyArray::LIST => self::ENDPOINT_LIST,
                ],

                /* Add map links. */
                KeyArray::MAP_LINKS => [
                    KeyArray::LINK_GOOGLE => $coordinate->getLinkGoogle(),
                    KeyArray::LINK_OPEN_STREET_MAP => $coordinate->getLinkOpenStreetMap(),
                ],

                /* Add next places */
                KeyArray::NEXT_PLACES => $this->locationServiceConfig->getConfigNextPlacesGroups($country),
            ];
        }

        return $examples;
    }
}
