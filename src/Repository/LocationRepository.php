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

use App\Entity\Country;
use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Ixnode\PhpException\Type\TypeInvalidException;

/**
 * Class LocationRepository
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-27)
 * @since 0.1.0 (2023-06-27) First version.
 *
 * @method Location|null find($id, $lockMode = null, $lockVersion = null)
 * @method Location|null findOneBy(array $criteria, array $orderBy = null)
 * @method Location[]    findAll()
 * @method Location[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<Location>
 */
class LocationRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    /**
     * @param Location $entity
     * @param bool $flush
     * @return void
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function save(Location $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param Location $entity
     * @param bool $flush
     * @return void
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function remove(Location $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Returns a list of locations (location.id).
     *
     * @param string|null $featureClass
     * @param float $latitude
     * @param float $longitude
     * @param int $distanceMeter
     * @return array<int, int>
     */
    public function findLocationsByFeatureClassAndDistance(
        ?string $featureClass,
        float   $latitude,
        float   $longitude,
        int     $distanceMeter
    ): array
    {
        $queryBuilder = $this->createQueryBuilder('l');

        $queryBuilder
            ->select([
                'l.id',
            ])
            ->leftJoin('l.featureClass', 'fc')
        ;

        if (!is_null($featureClass)) {
            $queryBuilder
                ->andWhere('fc.class = :featureClass')
                ->setParameter('featureClass', $featureClass);
        }

        $queryBuilder
            ->andWhere('ST_DWithin(
                ST_MakePointPoint(l.coordinate(0), l.coordinate(1)),
                ST_MakePoint(:latitude, :longitude),
                :distance
            ) = TRUE')
            ->setParameter('latitude', $latitude)
            ->setParameter('longitude', $longitude)
            ->setParameter('distance', $distanceMeter)
        ;

        $ids = $queryBuilder
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_column($ids, 'id');
    }

    /**
     * Returns the number of locations from given country code.
     *
     * @param Country $country
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws TypeInvalidException
     */
    public function getNumberOfLocations(Country $country): int
    {
        $queryBuilder = $this->createQueryBuilder('l');

        $queryBuilder
            ->select('COUNT(l)')
            ->leftJoin('l.imports', 'i')
            ->where('i.country = :country')
            ->setParameter('country', $country)
        ;

        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        if (!is_int($count)) {
            throw new TypeInvalidException('int', gettype($count));
        }

        return $count;
    }
}
