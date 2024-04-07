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

use App\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;

/**
 * Class EntityProcessor
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-04-07)
 * @since 0.1.0 (2024-04-07) First version.
 */
readonly class EntityProcessor
{
    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * Reloads the given location entities from db with all foreign key assignments.
     *
     * @param Location[] $locations
     * @return Location[]
     * @throws ORMException
     */
    public function reloadLocations(array $locations): array
    {
        $locationsReloaded = [];

        foreach ($locations as &$location) {
            if (!$location instanceof Location) {
                continue;
            }

            /* Save the closest distance and coordinates */
            $closestDistance = $location->getClosestDistance();
            $coordinate = $location->getCoordinate();

            $location = $this->entityManager->getReference(Location::class, $location->getId());

            if (!$location instanceof Location) {
                continue;
            }

            $this->entityManager->refresh($location);

            /* Restore the closest distance and coordinates */
            if (!is_null($closestDistance)) {
                $location->setClosestDistance($closestDistance);
            }
            if (!is_null($coordinate)) {
                $location->setCoordinate($coordinate);
            }

            $locationsReloaded[] = $location;
        }

        return $locationsReloaded;
    }
}
