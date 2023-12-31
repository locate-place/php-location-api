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
use App\Constants\Place\Location;
use App\Constants\Place\Search;
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

/**
 * Class CoordinateController
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-04)
 * @since 0.1.0 (2024-01-04) First version.
 */
abstract class BaseCoordinateController extends AbstractController
{
    private const ENDPOINT_COORDINATE = 'https://www.location-api.localhost/api/v1/location/coordinate.json';

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

            $coordinates[$key] = $value;

            $name = [];

            foreach (Location::ALL as $area) {
                $path = [$key, 'location', $area];

                if ($json->hasKey($path)) {
                    $name[] = $json->getKeyString($path);
                }
            }

            $latitude = $json->getKeyFloat([$key, 'coordinate', 'latitude']);
            $longitude = $json->getKeyFloat([$key, 'coordinate', 'longitude']);

            $coordinate = new Coordinate($latitude, $longitude);

            $coordinates[$key][KeyArray::NAME] = implode(', ', $name);

            $coordinates[$key][KeyArray::LATITUDE_DMS] = $coordinate->getLatitudeDMS();
            $coordinates[$key][KeyArray::LONGITUDE_DMS] = $coordinate->getLongitudeDMS();

            $coordinates[$key][KeyArray::LINK] = sprintf(
                '%s?coordinate=%f,%%20%f',
                self::ENDPOINT_COORDINATE,
                $latitude,
                $longitude
            );

            $coordinates[$key][KeyArray::LINK_GOOGLE] = $coordinate->getLinkGoogle();
            $coordinates[$key][KeyArray::LINK_OPEN_STREET_MAP] = $coordinate->getLinkOpenStreetMap();
        }

        return $coordinates;
    }
}
