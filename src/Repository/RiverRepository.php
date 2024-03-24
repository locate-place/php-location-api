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

namespace App\Repository;

use App\DBAL\GeoLocation\Converter\ValueToPoint;
use App\Entity\River;
use App\Entity\RiverPart;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Type\TypeInvalidException;
use LogicException;

/**
 * Class RiverRepository
 *
 * @method River|null find($id, $lockMode = null, $lockVersion = null)
 * @method River|null findOneBy(array $criteria, array $orderBy = null)
 * @method River[]    findAll()
 * @method River[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<River>
 */
class RiverRepository extends ServiceEntityRepository
{
    /** @var int[] $riversIds */
    private array $riversIds = [];

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, River::class);
    }

    /**
     * Finds rivers.
     *
     * @return array<int, River>
     * @throws TypeInvalidException
     */
    public function findRivers(
        Coordinate $coordinate,
        int|null $distanceMeter = null,
        int|null $limit = null
    ): array
    {
        $queryBuilder = $this->createQueryBuilder('r');

        $queryBuilder
            ->join(RiverPart::class, 'rp', 'WITH', 'r.id = rp.river')
        ;

        $queryBuilder->select([
            'DISTINCT_ON(r.id) AS r_id',
            'r'
        ]);
        $queryBuilder->addOrderBy('r_id');

        if (is_int($distanceMeter)) {
            $queryBuilder
                /* Attention: PostGIS uses lon/lat not lat/lon! */
                ->andWhere('ST_DWithin(
                    rp.coordinates,
                    ST_MakePoint(:longitude, :latitude),
                    :distance,
                    TRUE
                ) = TRUE')
                ->setParameter('latitude', $coordinate->getLatitude())
                ->setParameter('longitude', $coordinate->getLongitude())
                ->setParameter('distance', $distanceMeter)
                ->addSelect(sprintf(
                    'ST_AsText(ST_ClosestPoint(rp.coordinates, ST_MakePoint(%f, %f))) AS closest_point',
                    $coordinate->getLongitude(),
                    $coordinate->getLatitude()
                ))
            ;
        }

        $queryBuilder
            ->addSelect(sprintf(
                'DistanceOperator(rp.coordinates, %f, %f) distance',
                $coordinate->getLatitude(),
                $coordinate->getLongitude()
            ))
            ->addOrderBy('distance', 'ASC')
        ;

        /* Limit the result by number of entities. */
        if (is_int($limit)) {
            $queryBuilder
                ->setMaxResults($limit)
            ;
        }

        $result = $queryBuilder->getQuery()->getScalarResult();

        if (!is_array($result)) {
            throw new LogicException(sprintf('Result must be an array. "%s" given.', gettype($result)));
        }

        return $this->hydrateObjects($result);
    }

    /**
     * Hydrates the given objects.
     *
     * @param array<int, array<int|null, mixed>> $objects
     * @return River[]
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function hydrateObjects(array $objects, bool $distinct = false): array
    {
        $this->riversIds = [];

        $hydratedObjects = array_map(fn(array $object) => $this->hydrateObject($object, $distinct), $objects);

        return array_filter($hydratedObjects);
    }

    /**
     * Hydrates the given object.
     *
     * @param array<int|string, mixed> $object
     * @param bool $distinct
     * @return River|null
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function hydrateObject(array $object, bool $distinct = false): River|null
    {
        $id = $object['r_id'] ?? null;

        if (is_null($id)) {
            throw new LogicException('River ID must not be null.');
        }

        if (!is_int($id)) {
            throw new LogicException('River ID must be integer.');
        }

        if ($distinct && in_array($id, $this->riversIds, true)) {
            return null;
        }

        $river = new River();

        foreach ($object as $property => $value) {
            switch ($property) {
                case 'r_id':
                    if (!is_int($value)) {
                        throw new LogicException('r_id must be an integer.');
                    }
                    $river->setId($value);
                    break;

                case 'r_riverCode':
                    if (!is_string($value)) {
                        throw new LogicException('r_riverCode must be a string.');
                    }
                    $river->setRiverCode($value);
                    break;

                case 'r_name':
                    if (!is_string($value)) {
                        throw new LogicException('r_name must be a string.');
                    }
                    $river->setName($value);
                    break;

                case 'r_length':
                    if (!is_string($value)) {
                        throw new LogicException('r_length must be a string.');
                    }
                    $river->setLength($value);
                    break;

                case 'r_ignoreMapping':
                    if (!is_bool($value)) {
                        throw new LogicException('r_ignoreMapping must be a boolean.');
                    }
                    $river->setIgnoreMapping($value);
                    break;

                case 'r_createdAt':
                    if (!$value instanceof DateTimeImmutable) {
                        throw new LogicException('r_createdAt must be an instance of DateTimeImmutable.');
                    }
                    $river->setCreatedAt($value);
                    break;

                case 'r_updatedAt':
                    if (!$value instanceof DateTimeImmutable) {
                        throw new LogicException('r_updatedAt must be an instance of DateTimeImmutable.');
                    }
                    $river->setUpdatedAt($value);
                    break;

                case 'closest_point':
                    if (!is_string($value)) {
                        throw new LogicException('closest_point must be a string.');
                    }
                    $river->setClosestCoordinate((new ValueToPoint($value))->get());
                    break;

                case 'distance':
                    if (!is_string($value)) {
                        throw new LogicException('distance must be a string.');
                    }
                    $river->setDistance((float) sprintf('%.3f', ((float)$value) / 1000));
                    break;

                default:
                    throw new LogicException(sprintf('Unknown property "%s".', $property));
            }
        }

        $this->riversIds[] = $id;

        return $river;
    }
}
