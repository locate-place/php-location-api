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

use App\Constants\DB\FeatureClass;
use App\Entity\Country;
use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Ixnode\PhpChecker\Checker;
use Ixnode\PhpChecker\CheckerArray;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpTimezone\Constants\CountryEurope;

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
     * Saves the given Location entity.
     *
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
     * Removes the given Location entity from DB.
     *
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
     * Finds the locations from given latitude and longitude ordered by distance.
     *
     * Query example:
     * --------------
     * SELECT id, name, ST_Distance(ST_MakePoint(coordinate[0], coordinate[1])::geography, ST_MakePoint(51.3, 13.3)::geography) AS distance
     * FROM location
     * ORDER BY distance
     * LIMIT 50;
     *
     * @param Coordinate $coordinate
     * @param int|null $distanceMeter
     * @param string|null $featureClass
     * @param string|array<int, string>|null $featureCodes
     * @param int|null $limit
     * @return array<int, Location>
     * @throws TypeInvalidException
     * @throws ClassInvalidException
     */
    public function findLocationsByCoordinate(
        Coordinate $coordinate,
        int|null $distanceMeter = null,
        string|null $featureClass = null,
        string|array|null $featureCodes = null,
        int|null $limit = null
    ): array
    {
        $queryBuilder = $this->createQueryBuilder('l');

        /* Convert $featureCodes to an array. */
        $featureCodes = is_string($featureCodes) ? [$featureCodes] : $featureCodes;

        if (is_string($featureClass)) {
            $queryBuilder
                ->leftJoin('l.featureClass', 'fcl')
                ->andWhere('fcl.class = :featureClass')
                ->setParameter('featureClass', $featureClass);
        }

        if (is_array($featureCodes)) {
            $queryBuilder
                ->leftJoin('l.featureCode', 'fco')
                ->andWhere('fco.code IN (:featureCodes)')
                ->setParameter('featureCodes', $featureCodes)
            ;
        }

        /* Order result by distance. */
        $queryBuilder
            ->addSelect('ST_Distance(
                ST_MakePointPoint(l.coordinate(0), l.coordinate(1)),
                ST_MakePoint(:latitude, :longitude),
                TRUE
            ) AS HIDDEN distance')
            ->setParameter('latitude', $coordinate->getLatitude())
            ->setParameter('longitude', $coordinate->getLongitude())
            ->orderBy('distance', 'ASC')
        ;

        /* Limit result by given distance. */
        if (is_int($distanceMeter)) {
            $queryBuilder
                ->andWhere('ST_DWithin(
                ST_MakePointPoint(l.coordinate(0), l.coordinate(1)),
                ST_MakePoint(:latitude, :longitude),
                :distance
            ) = TRUE')
                ->setParameter('latitude', $coordinate->getLatitude())
                ->setParameter('longitude', $coordinate->getLongitude())
                ->setParameter('distance', $distanceMeter)
            ;
        }

        /* Limit result by number of entities. */
        if (is_int($limit)) {
            $queryBuilder
                ->setMaxResults($limit)
            ;
        }

        return array_values(
            (new CheckerArray($queryBuilder->getQuery()->getResult()))
                ->checkClass(Location::class)
        );
    }

    /**
     * Finds the admin places from given latitude and longitude ordered by distance.
     *
     * @param Coordinate $coordinate
     * @param int|null $distanceMeter
     * @param int|null $limit
     * @return array<int, Location>
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    public function findAdminLocationsByCoordinate(
        Coordinate $coordinate,
        int|null $distanceMeter = null,
        int|null $limit = null
    ): array
    {
        return $this->findLocationsByCoordinate(
            $coordinate,
            $distanceMeter,
            null,
            FeatureClass::FEATURE_CODES_P_ADMIN_PLACES,
            $limit
        );
    }

    /**
     * Finds the first admin places from given latitude and longitude ordered by distance.
     *
     * @param Coordinate $coordinate
     * @param int|null $distanceMeter
     * @return Location|null
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    public function findFirstAdminLocationByCoordinate(
        Coordinate $coordinate,
        int|null $distanceMeter = null
    ): Location|null
    {
        $place = $this->findAdminLocationsByCoordinate(
            $coordinate,
            $distanceMeter,
            1
        );

        if (count($place) <= 0) {
            return null;
        }

        return $place[0];
    }

    /**
     * Finds the city from given district (FeatureCode PPL or PPLX).
     *
     * @param Location $district
     * @return Location|null
     * @throws NonUniqueResultException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    public function findCityByLocationDistrict(Location $district): Location|null
    {
        $featureCode = $district->getFeatureCode()?->getCode();

        if (!in_array($featureCode, FeatureClass::FEATURE_CODES_P_DISTRICT_PLACES)) {
            throw new CaseUnsupportedException(sprintf(
                'FeatureCode "%s" is not supported (expected: "%s").',
                $featureCode,
                implode(', ', FeatureClass::FEATURE_CODES_P_DISTRICT_PLACES)
            ));
        }

        $country = $district->getCountry();

        if (!$country instanceof Country) {
            throw new ClassInvalidException(Country::class, Country::class);
        }

        $queryBuilder = $this->createQueryBuilder('l');

        $queryBuilder
            ->andWhere('l.country = :country')
            ->setParameter('country', $country);

        $queryBuilder
            ->leftJoin('l.featureClass', 'fcl')
            ->andWhere('fcl.class = :featureClass')
            ->setParameter('featureClass', FeatureClass::FEATURE_CLASS_A);

        $queryBuilder
            ->leftJoin('l.featureCode', 'fco')
        ;

        $queryBuilder
            ->leftJoin('l.adminCode', 'a')
        ;

        match ($country->getCode()) {
            CountryEurope::COUNTRY_CODE_AT,
            CountryEurope::COUNTRY_CODE_CH,
            CountryEurope::COUNTRY_CODE_ES,
            CountryEurope::COUNTRY_CODE_PL =>
                $queryBuilder
                    ->andWhere('fco.code = :featureCode')
                    ->setParameter('featureCode', FeatureClass::FEATURE_CODE_A_ADM3)
                    ->andWhere('a.admin3Code = :admin3Code')
                    ->setParameter('admin3Code', $district->getAdminCode()?->getAdmin3Code()),
            default =>
                $queryBuilder
                    ->andWhere('fco.code = :featureCode')
                    ->setParameter('featureCode', FeatureClass::FEATURE_CODE_A_ADM4)
                    ->andWhere('a.admin4Code = :admin4Code')
                    ->setParameter('admin4Code', $district->getAdminCode()?->getAdmin4Code()),
        };

        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        if (is_null($result)) {
            return null;
        }

        return (new Checker($result))->checkClass(Location::class);
    }

    /**
     * Finds the first admin places from given latitude and longitude ordered by distance.
     *
     * @param Location $location
     * @return Location|null
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws TypeInvalidException
     */
    public function findStateByLocation(Location $location): Location|null
    {
        $country = $location->getCountry();

        if (!$country instanceof Country) {
            throw new ClassInvalidException(Country::class, Country::class);
        }

        $queryBuilder = $this->createQueryBuilder('l');

        $queryBuilder
            ->andWhere('l.country = :country')
            ->setParameter('country', $country);

        $queryBuilder
            ->leftJoin('l.featureClass', 'fcl')
            ->andWhere('fcl.class = :featureClass')
            ->setParameter('featureClass', FeatureClass::FEATURE_CLASS_A);

        $queryBuilder
            ->leftJoin('l.featureCode', 'fco')
        ;

        $queryBuilder
            ->leftJoin('l.adminCode', 'a')
        ;

        switch ($country->getCode()) {
            /* de, etc. */
            default:
                $queryBuilder
                    ->andWhere('fco.code = :featureCode')
                    ->setParameter('featureCode', FeatureClass::FEATURE_CODE_A_ADM1)
                    ->andWhere('a.admin1Code = :admin1Code')
                    ->setParameter('admin1Code', $location->getAdminCode()?->getAdmin1Code())
                ;
        }

        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        if (is_null($result)) {
            return null;
        }

        return (new Checker($result))->checkClass(Location::class);
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
