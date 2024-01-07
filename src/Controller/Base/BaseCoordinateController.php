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

namespace App\Controller\Base;

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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CoordinateController
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 */
abstract class BaseCoordinateController extends AbstractController
{
    private const ENDPOINT_COORDINATE = '/api/v1/location/coordinate.json';

    private const ENDPOINT_LIST = '/api/v1/location.json';

    /**
     * @param TranslatorInterface $translator
     * @param LocationServiceConfig $locationServiceConfig
     */
    public function __construct(
        protected TranslatorInterface $translator,
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
        $nameParts = [];
        foreach (Location::ALL as $area) {
            $path = [$key, 'location', $area];

            if ($json->hasKey($path)) {
                $nameParts[] = $json->getKeyString($path);
            }
        }

        return implode(', ', $nameParts);
    }

    /**
     * Returns the coordinates for the twig template.
     *
     * @return array<string|int, mixed>
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
    protected function getCoordinates(): array
    {
        $json = new Json(Search::VALUES);

        $coordinates = [];

        foreach ($json->getArray() as $key => $value) {
            $key = (string) $key;

            if (!is_array($value)) {
                throw new LogicException(sprintf('Value for key "%s" must be an array.', $key));
            }

            $country = array_key_exists('country', $value) && is_string($value['country']) ? $value['country'] : CountryCode::DEFAULT;

            $latitude = $json->getKeyFloat([$key, 'coordinate', 'latitude']);
            $longitude = $json->getKeyFloat([$key, 'coordinate', 'longitude']);

            $coordinate = new Coordinate($latitude, $longitude);

            /* Initialize the array */
            $coordinates[$key] = [
                ...$value,

                /* Add name and country. */
                KeyArray::NAME => $this->getName($json, $key),
                KeyArray::COUNTRY => $country,

                /* Add coordinates. */
                KeyArray::LATITUDE_DMS => $coordinate->getLatitudeDMS(),
                KeyArray::LONGITUDE_DMS => $coordinate->getLongitudeDMS(),

                /* Add endpoints. */
                KeyArray::LINK_COORDINATE => self::ENDPOINT_COORDINATE,
                KeyArray::LINK_LIST => self::ENDPOINT_LIST,

                /* Add map links. */
                KeyArray::LINK_GOOGLE => $coordinate->getLinkGoogle(),
                KeyArray::LINK_OPEN_STREET_MAP => $coordinate->getLinkOpenStreetMap(),

                /* Add coordinate string. */
                KeyArray::COORDINATE_STRING => sprintf(
                    '%f,%%20%f',
                    $latitude,
                    $longitude
                ),

                /* Add next places */
                KeyArray::NEXT_PLACES => $this->locationServiceConfig->getConfigNextPlacesGroups($country),
            ];
        }

        return $coordinates;
    }
}
