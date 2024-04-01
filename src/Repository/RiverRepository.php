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
use App\Entity\River;
use App\Entity\RiverPart;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Laminas\Hydrator\DoctrineObject as DoctrineHydrator;
use Doctrine\ORM\EntityManagerInterface;
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

    final public const LIMIT_PREDICTION = 100;

    /**
     * @param ManagerRegistry $registry
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        ManagerRegistry $registry,
        protected readonly EntityManagerInterface $entityManager
    )
    {
        parent::__construct($registry, River::class);
    }

    /**
     * Finds rivers.
     *
     * @param Coordinate|null $coordinate
     * @param string[]|null $riverNames
     * @param int|null $distanceMeter
     * @param Country|null $country
     * @param int|null $limit
     * @param bool $createRealDoctrineObject
     * @param bool $onlyMapped
     * @return array<int, River>
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function findRivers(
        Coordinate|null $coordinate,
        array|null $riverNames = null,
        int|null $distanceMeter = null,
        Country|null $country = null,
        int|null $limit = null,
        bool $onlyMapped = false,
        bool $createRealDoctrineObject = false
    ): array
    {
        $queryBuilder = $this->createQueryBuilder('r');

        $queryBuilder
            ->join(RiverPart::class, 'rp', 'WITH', 'r.id = rp.river')
        ;

        if (!is_null($country)) {
            $queryBuilder
                ->andWhere('rp.country = :country')
                ->setParameter('country', $country);
        }

        $queryBuilder
            ->select([
                'DISTINCT_ON(r.id) AS r_id',
                'r'
            ])
            ->addOrderBy('r_id')
        ;

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

        /* Show only rivers that are mapped to locations */
        if ($onlyMapped) {
            $queryBuilder
                ->leftJoin('r.locations', 'l')
                ->andWhere('l.id IS NOT NULL')
            ;
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

        $result = $queryBuilder->getQuery()->getScalarResult();

        if (!is_array($result)) {
            throw new LogicException(sprintf('Result must be an array. "%s" given.', gettype($result)));
        }

        /* Sort result by distance. */
        usort($result, fn(array $riverA, array $riverB) => $riverA['distance'] <=> $riverB['distance']);

        if (is_int($limit)) {
            $result = array_slice($result, 0, $limit);
        }

        return $this->hydrateObjects(
            objects: $result,
            createRealDoctrineObject: $createRealDoctrineObject
        );
    }

    /**
     * Finds one river.
     *
     * @param Coordinate $coordinate
     * @param string[]|null $riverNames
     * @param int|null $distanceMeter
     * @param Country|null $country
     * @param bool $onlyMapped
     * @return River|null
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function findRiver(
        Coordinate $coordinate,
        array|null $riverNames = null,
        int|null $distanceMeter = null,
        Country|null $country = null,
        bool $onlyMapped = false
    ): River|null
    {
        $rivers = $this->findRivers(
            coordinate: $coordinate,
            riverNames: $riverNames,
            distanceMeter: $distanceMeter,
            country: $country,
            limit: 2,
            onlyMapped: $onlyMapped
        );

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
    public function hydrateObjects(
        array $objects,
        bool $distinct = false,
        bool $createRealDoctrineObject = false
    ): array
    {
        $this->riversIds = [];

        $hydratedObjects = array_map(fn(array $object) => $this->hydrateObject($object, $distinct, $createRealDoctrineObject), $objects);

        return array_filter($hydratedObjects);
    }

    /**
     * Returns the doctrine array from the given db scalar object.
     *
     * @param array<int|string, mixed> $object
     * @return array{id: int, riverCode: string, name: string, length: string, createdAt: DateTimeImmutable, updatedAt: DateTimeImmutable}
     */
    private function getDoctrineArray(array $object): array
    {
        /* River entity configuration. */
        $mapping = [
            'r_id' => ['target' => 'id', 'type' => 'int'],
            'r_riverCode' => ['target' => 'riverCode', 'type' => 'string'],
            'r_name' => ['target' => 'name', 'type' => 'string'],
            'r_length' => ['target' => 'length', 'type' => 'string'],
            'r_createdAt' => ['target' => 'createdAt', 'type' => DateTimeImmutable::class],
            'r_updatedAt' => ['target' => 'updatedAt', 'type' => DateTimeImmutable::class],
        ];

        $data = [];
        foreach ($object as $property => $value) {
            /* Ignore properties which are not a component from the River entity. */
            if (!isset($mapping[$property])) {
                continue;
            }

            $expectedType = $mapping[$property]['type'];
            $targetField = $mapping[$property]['target'];

            /* Check the given value type. */
            $isValid = match ($expectedType) {
                'int' => is_int($value),
                'string' => is_string($value),
                default => $value instanceof $expectedType,
            };

            if (!$isValid) {
                throw new LogicException(sprintf('%s must be a %s.', $property, $expectedType));
            }

            $data[$targetField] = $value;
        }

        /** @phpstan-ignore-next-line: All properties are checked with $isValid. */
        return $data;
    }

    /**
     * Hydrates the given data to a River entity.
     *
     * Attention: $realDoctrineObject == true produces a real Doctrine entity, but it's slow!
     *
     * @param array{id: int, riverCode: string, name: string, length: string, createdAt: DateTimeImmutable, updatedAt: DateTimeImmutable} $data
     * @param bool $createRealDoctrineObject
     * @return River
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function doHydrate(
        array $data,
        bool $createRealDoctrineObject = false
    ): River
    {
        $river = new River();

        if (!$createRealDoctrineObject) {
            $river->setId($data['id']);
            $river->setRiverCode($data['riverCode']);
            $river->setName($data['name']);
            $river->setLength($data['length']);
            $river->setCreatedAt($data['createdAt']);
            $river->setUpdatedAt($data['updatedAt']);

            return $river;
        }

        $hydrator = new DoctrineHydrator($this->entityManager);
        $river = $hydrator->hydrate($data, $river);

        if (!$river instanceof River) {
            throw new LogicException(sprintf('River entity must be an instance of %s.', River::class));
        }

        return $river;
    }

    /**
     * Hydrates the given object.
     *
     * @param array<int|string, mixed> $object
     * @param bool $distinct
     * @param bool $createRealDoctrineObject
     * @return River|null
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function hydrateObject(
        array $object,
        bool $distinct = false,
        bool $createRealDoctrineObject = false
    ): River|null
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

        $river = $this->doHydrate(
            $this->getDoctrineArray($object),
            $createRealDoctrineObject
        );

        foreach ($object as $property => $value) {
            switch ($property) {
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
                    $river->setClosestDistance((float) sprintf('%.3f', ((float)$value) / 1000));
                    break;
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
