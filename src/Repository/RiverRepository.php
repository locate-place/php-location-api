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
use App\Utils\Db\DebugQuery;
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

    private const LIMIT_PREDICTION = 100;

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
     * @param Coordinate|null $coordinate
     * @param string[]|null $riverNames
     * @param int|null $distanceMeter
     * @param int|null $limit
     * @return array<int, River>
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function findRivers(
        Coordinate|null $coordinate,
        array|null $riverNames = null,
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

        if (!is_null($coordinate) && is_int($distanceMeter)) {
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

        /*  Filter by river names. */
        if (is_array($riverNames)) {
            $riverNames = $this->getRiverNames($riverNames);

            $orX = $queryBuilder->expr()->orX();
            foreach ($riverNames as $index => $riverName) {
                $orX->add($queryBuilder->expr()->like('r.name', ':name'.$index));
                $queryBuilder->setParameter('name'.$index, '%'.$riverName.'%');
            }

            $queryBuilder->andWhere($orX);
        }

        /* Add order by. */
        match (true) {
            !is_null($coordinate) => $queryBuilder
                ->addSelect(sprintf(
                    'DistanceOperator(rp.coordinates, %f, %f) distance',
                    $coordinate->getLatitude(),
                    $coordinate->getLongitude()
                ))
                ->addOrderBy('distance', 'ASC'),
            default => $queryBuilder
                ->addOrderBy('r.name', 'ASC'),
        };

        /* Limit the result by number of entities: if no distance was given. */
        if (is_null($distanceMeter) && is_int($limit)) {
            $queryBuilder
                ->setMaxResults($limit * self::LIMIT_PREDICTION)
            ;
        }

//        $debugQuery = new DebugQuery($queryBuilder);
//        print $debugQuery->getSqlRaw().PHP_EOL;
//        exit();

        $result = $queryBuilder->getQuery()->getScalarResult();

        if (!is_array($result)) {
            throw new LogicException(sprintf('Result must be an array. "%s" given.', gettype($result)));
        }

        $rivers = $this->hydrateObjects($result);

        usort($rivers, fn(River $riverA, River $riverB) => $riverA->getDistance() <=> $riverB->getDistance());

        if (is_int($limit)) {
            $rivers = array_slice($rivers, 0, $limit);
        }

        return $rivers;
    }

    /**
     * Finds one river.
     *
     * @param Coordinate $coordinate
     * @param string[]|null $riverNames
     * @param int|null $distanceMeter
     * @return River|null
     * @throws TypeInvalidException
     */
    public function findRiver(
        Coordinate $coordinate,
        array|null $riverNames = null,
        int|null $distanceMeter = null
    ): River|null
    {
        $rivers = $this->findRivers(
            coordinate: $coordinate,
            riverNames: $riverNames,
            distanceMeter: $distanceMeter,
            limit: 2
        );

//        if (count($rivers) > 1) {
//            print PHP_EOL;
//            print sprintf(
//                    'More than one river found within %d meters ("%s" - %s, %s): %s',
//                    $distanceMeter,
//                    implode(Location::NAME_SEPARATOR, $riverNames),
//                    $coordinate->getLatitude(),
//                    $coordinate->getLongitude(),
//                    implode(', ', array_map(fn(River $river) => sprintf('%s (%d - %f)', $river->getName(), $river->getId(), $river->getDistance()), $rivers))
//            );
//            print PHP_EOL;
//            return $rivers[0];
//        }

        /* No river was found. */
        if (count($rivers) <= 0) {
            return null;
        }

        /* Returns the onliest river. */
        return $rivers[0];
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

    /**
     * Use the preg_replace function and check for string.
     *
     * @param string $pattern
     * @param string $replacement
     * @param string $subject
     * @return string
     */
    private function pregReplace(string $pattern, string $replacement, string $subject): string
    {
        $value = preg_replace($pattern, $replacement, $subject);

        if (!is_string($value)) {
            throw new LogicException('Unable to replace the given pattern.');
        }

        return $value;
    }

    /**
     * Returns all combinations array of river names.
     *
     * @param string[] $riverNames
     * @return string[]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getRiverNames(array $riverNames): array
    {
        $combinations = [];

        foreach ($riverNames as $riverName) {
            $combinations[] = $riverName;

            /* Add some more combinations. */
            switch (true) {
                /* "graben" -> "" */
                case !empty(preg_match('~graben~i', $riverName)):
                    $combinations[] = $this->pregReplace('~graben~i', '', $riverName);
                    break;

                /* "Name Bach" -> "Namebach" */
                case !empty(preg_match('~ Bach~i', $riverName)):
                    $combinations[] = $this->pregReplace('~ Bach~i', 'bach', $riverName);
                    $combinations[] = $this->pregReplace('~ Bach~i', '-Bach', $riverName);
                    break;

                /* (Name[r])beek -> Name */
                case !empty(preg_match('~([a-z]+)rbeek~i', $riverName)):
                    $combinations[] = $this->pregReplace('~([a-z]+)rbeek~i', '$1', $riverName);
                    break;

                /* (Name)bach -> Name */
                case !empty(preg_match('~([a-z]+)bach~i', $riverName)):
                    $combinations[] = $this->pregReplace('~([a-z]+)bach~i', '$1', $riverName);
                    break;

                /* (Name)beek -> Name */
                case !empty(preg_match('~([a-z]+)beek~i', $riverName)):
                    $combinations[] = $this->pregReplace('~([a-z]+)beek~i', '$1', $riverName);
                    break;

                /* Wilde (Name) -> Name */
                case !empty(preg_match('~Wilde ~i', $riverName)):
                    $combinations[] = $this->pregReplace('~Wilde ~i', '', $riverName);
                    break;

                default:
                    break;
            };
        }

        /* Add some more combinations. */
        foreach ($combinations as $combination) {
            switch (true) {
                /* "ß" -> "ss" */
                case !empty(preg_match('~ß~i', $combination)):
                    $combinations[] = $this->pregReplace('~ß~i', 'ss', $combination);
                    break;

                default:
                    break;
            };
        }

        return $combinations;
    }
}
