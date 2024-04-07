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

namespace App\Utils\Doctrine;

use App\DBAL\GeoLocation\Converter\ValueToPoint;
use App\Entity\Location;
use Ixnode\PhpException\Type\TypeInvalidException;
use LogicException;

/**
 * Class ResultProcessor
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-04-07)
 * @since 0.1.0 (2024-04-07) First version.
 */
readonly class ResultProcessor
{
    /**
     * Hydrates the given object.
     *
     * @param Location|array<int|string, mixed> $object
     * @return Location
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function hydrateObject(Location|array $object): Location
    {
        /* No hidden fields, etc. were given. */
        if ($object instanceof Location) {
            return $object;
        }

        $location = null;

        foreach ($object as $property => $value) {
            /* The first result should be a Location entity. */
            if ($value instanceof Location) {
                $location = $value;
                continue;
            }

            /* The first result should be a Location entity. */
            if (is_null($location)) {
                throw new LogicException('Location was not found within db result.');
            }

            switch ($property) {
                /* Set the alternate names. */
                case 'alternateNames':
                    if (!is_string($value) && !is_null($value)) {
                        throw new LogicException(sprintf('$value expected to be a string or null. "%s" given.', gettype($value)));
                    }
                    $location->setNames(
                        explode(
                            Location::NAME_SEPARATOR,
                            (
                            is_null($value) ? '' : $value.Location::NAME_SEPARATOR
                            ).$location->getName()
                        )
                    );
                    break;

                /* Set the closest point. */
                case 'closest_point':
                case 'closestPoint':
                    if (is_null($value)) {
                        continue 2;
                    }
                    if (!is_string($value)) {
                        throw new LogicException(sprintf('$value expected to be a string or null. "%s" given.', gettype($value)));
                    }
                    $location->setCoordinate(
                        (new ValueToPoint($value))->get()
                    );
                    break;

                /* Set the closest distance. */
                case 'closest_distance':
                case 'closestDistance':
                    if (is_null($value)) {
                        continue 2;
                    }
                    if (is_string($value)) {
                        $value = (float) $value;
                    }
                    if (!is_float($value)) {
                        throw new LogicException(sprintf('$value expected to be a float or null. "%s" given.', gettype($value)));
                    }
                    $location->setClosestDistance($value);
                    break;

                /* Unknown property. */
                default:
                    throw new LogicException(sprintf('Unknown property "%s".', $property));
            }
        }

        if (is_null($location)) {
            throw new LogicException('Location was not found within db result.');
        }

        return $location;
    }

    /**
     * Hydrates the given objects.
     *
     * @param array<int, Location|array<int, mixed>> $objects
     * @return Location[]
     * @throws TypeInvalidException
     */
    public function hydrateObjects(array $objects): array
    {
        return array_map(fn(Location|array $object) => $this->hydrateObject($object), $objects);
    }
}
