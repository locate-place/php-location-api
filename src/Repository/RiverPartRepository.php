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
use App\Entity\Country;
use App\Entity\RiverPart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use Ixnode\PhpNamingConventions\NamingConventions;
use LogicException;

/**
 * Class RiverPartRepository
 *
 * @method RiverPart|null find($id, $lockMode = null, $lockVersion = null)
 * @method RiverPart|null findOneBy(array $criteria, array $orderBy = null)
 * @method RiverPart[]    findAll()
 * @method RiverPart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<RiverPart>
 */
class RiverPartRepository extends ServiceEntityRepository
{
    private const PRELOAD_MULTIPLIER = 100;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RiverPart::class);
    }

    /**
     * Finds the zip codes from given latitude and longitude ordered by distance.
     *
     * Query example:
     * --------------
     * SELECT
     *   id,
     *   name,
     *   length,
     *   ST_Distance(coordinates, ST_Point(13.739364, 51.060459)) AS distance_meter,
     *   ST_AsText(ST_ClosestPoint(coordinates::geometry, ST_MakePoint(13.739364, 51.060459)::geography::geometry)) AS closest_point_on_river,
     *   object_id,
     *   continua,
     *   european_segment_code,
     *   flow_direction,
     *   work_area_code,
     *   country_id
     * FROM river_part
     * WHERE ST_DWithin(coordinates, ST_MakePoint(13.739364, 51.060459), 10000)
     * ORDER BY distance_meter ASC
     * LIMIT 10;
     *
     * --------------
     * Show indices:
     * -------------
     * SELECT
     *   i.relname AS index_name,
     *   am.amname AS index_type,
     *   idx.indisprimary AS is_primary,
     *   idx.indisunique AS is_unique,
     *   pg_get_indexdef(idx.indexrelid) AS index_definition
     * FROM
     *   pg_index AS idx
     * JOIN
     *   pg_class AS i ON i.oid = idx.indexrelid
     * JOIN
     *   pg_am AS am ON i.relam = am.oid
     * WHERE
     *   idx.indrelid = 'river_part'::regclass
     * ORDER BY
     *   index_name;
     *
     * @param Coordinate|null $coordinate
     * @param int|null $distanceMeter
     * @param Country|null $country
     * @param int|null $limit
     * @return array<int, RiverPart>
     * @throws FunctionReplaceException
     * @throws TypeInvalidException
     */
    public function findRiverPartsByCoordinate(
        Coordinate|null $coordinate = null,
        int|null $distanceMeter = null,
        Country|null $country = null,
        int|null $limit = null
    ): array
    {
        $queryBuilder = $this->createQueryBuilder('rp');

        $limitPrediction = $limit;

        /* Limit result by country. */
        if ($country instanceof Country) {
            $queryBuilder
                ->andWhere('rp.country = :country')
                ->setParameter('country', $country);
        }

        /* Limit result by given distance. */
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
                    //'ST_AsText(rp.coordinates) AS closest_point',
                    $coordinate->getLongitude(),
                    $coordinate->getLatitude()
                ))
            ;

            $limitPrediction = is_null($limit) ? null : ($limit * self::PRELOAD_MULTIPLIER);
        }

        /* Order result by distance (uses <-> for performance reasons). */
        if (!is_null($coordinate)) {
            $queryBuilder
                ->addSelect(sprintf(
                    'DistanceOperator(rp.coordinates, %f, %f) distance',
                    $coordinate->getLatitude(),
                    $coordinate->getLongitude()
                ))
                ->addOrderBy('distance', 'ASC')
            ;

            $limitPrediction = is_null($limit) ? null : ($limit * self::PRELOAD_MULTIPLIER);
        }

        /* Limit result by number of entities. */
        if (is_int($limitPrediction)) {
            $queryBuilder
                ->setMaxResults($limitPrediction)
            ;
        }

        $result = $queryBuilder->getQuery()->getResult();

        if (!is_array($result)) {
            throw new LogicException(sprintf('Result must be an array. "%s" given.', gettype($result)));
        }

        /* Returns the result. */
        return $this->hydrateObjects($result, $limit, ['name', 'workAreaCode']);
    }

    /**
     * Returns the first zip code from given latitude and longitude ordered by distance.
     *
     * @param Coordinate|null $coordinate
     * @param int|null $distanceMeter
     * @param Country|null $country
     * @return RiverPart|null
     * @throws FunctionReplaceException
     * @throws TypeInvalidException
     */
    public function findRiverPartByCoordinate(
        Coordinate|null $coordinate = null,
        int|null $distanceMeter = null,
        Country|null $country = null
    ): RiverPart|null
    {
        $riverParts = $this->findRiverPartsByCoordinate(
            coordinate: $coordinate,
            distanceMeter: $distanceMeter,
            country: $country,
            limit: 1
        );

        if (count($riverParts) <= 0) {
            return null;
        }

        return $riverParts[0];
    }

    /**
     * Hydrates the given object.
     *
     * @param RiverPart|array<int|string, mixed> $object
     * @return RiverPart
     * @throws TypeInvalidException
     */
    public function hydrateObject(RiverPart|array $object): RiverPart
    {
        /* No hidden fields, etc. were given. */
        if ($object instanceof RiverPart) {
            return $object;
        }

        $riverPart = null;

        foreach ($object as $property => $value) {
            /* The first result should be a RiverPart entity. */
            if ($value instanceof RiverPart) {
                $riverPart = $value;
                continue;
            }

            /* The first result should be a RiverPart entity. */
            if (is_null($riverPart)) {
                throw new LogicException('River part was not found within db result.');
            }

            if (!is_string($value)) {
                throw new LogicException('$value expected to be a string.');
            }

            match ($property) {
                'closest_point' => $riverPart->setClosestCoordinate((new ValueToPoint($value))->get()),
                'distance' => $riverPart->setDistance((float) sprintf('%.3f', ((float) $value) / 1000)),
                default => throw new LogicException(sprintf('Unknown property "%s".', $property)),
            };
        }

        if (is_null($riverPart)) {
            throw new LogicException('River part was not found within db result.');
        }

        return $riverPart;
    }

    /**
     * Hydrates the given objects.
     *
     * @param array<int, RiverPart|array<int|null, mixed>> $objects
     * @param int|null $limit
     * @param string[] $groupBy
     * @return RiverPart[]
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     */
    public function hydrateObjects(array $objects, int|null $limit, array $groupBy = []): array
    {
        $objects = array_map(fn(RiverPart|array $object) => $this->hydrateObject($object), $objects);

        if (count($groupBy) <= 0) {
            return $objects;
        }

        $groupBy = array_map(fn(string $name) => sprintf('get%s', (new NamingConventions($name))->getPascalCase()), $groupBy);

        $riverParts = [];

        foreach ($objects as $object) {
            $index = [];

            foreach ($groupBy as $groupByProperty) {
                $index[] = $object->{$groupByProperty}();
            }

            $index = implode('_', $index);

            if (array_key_exists($index, $riverParts)) {
                continue;
            }

            $riverParts[$index] = $object;
        }

        $riverParts = array_values($riverParts);

        if (is_null($limit) || count($riverParts) <= $limit) {
            return $riverParts;
        }

        return array_slice($riverParts, 0, $limit);
    }

    /**
     * Returns an array of all river codes from table river_part that does not contain "unknown" within the name.
     *
     * @return array<int, string|null>
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getUniqueRiverCodes(): array
    {
        $qb = $this->createQueryBuilder('rp')
            ->select('rp.riverCode, MIN(rp.name) AS name')
            ->groupBy('rp.riverCode')
            ->getQuery();

        $results = $qb->getArrayResult();

        $formattedResults = [];
        foreach ($results as $result) {
            if (!is_array($result)) {
                throw new LogicException(sprintf('Result must be an array. "%s" given.', gettype($result)));
            }

            if (!array_key_exists('riverCode', $result)) {
                throw new LogicException('Result must contain "riverCode" key.');
            }

            $riverCode = $result['riverCode'];

            if (!is_string($riverCode) && !is_int($riverCode) && !is_float($riverCode)) {
                throw new LogicException('Result must contain "riverCode" key with string, integer, float.');
            }

            $riverCode = (int) $riverCode;

            if (!array_key_exists($riverCode, $formattedResults)) {
                $formattedResults[$riverCode] = null;
            }

            if (!array_key_exists('name', $result)) {
                throw new LogicException('Result must contain "name" key.');
            }

            $name = $result['name'];

            if (!is_string($name)) {
                throw new LogicException('$name expected to be a string.');
            }

            if (is_null($formattedResults[$riverCode]) && $name !== 'unbekannt') {
                $formattedResults[$riverCode] = $name;
            }
        }

        return $formattedResults;
    }
}
