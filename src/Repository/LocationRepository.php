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

use App\Constants\DB\CountryConfig;
use App\Constants\DB\FeatureClass;
use App\Entity\Country;
use App\Entity\Location;
use App\Entity\Location as LocationEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Ixnode\PhpChecker\Checker;
use Ixnode\PhpChecker\CheckerArray;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Parser\ParserException;
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
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
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
     * @param array<int, string>|string|null $featureClass
     * @param array<int, string>|string|null $featureCodes
     * @param Country|null $country
     * @param array{a1?: string, a2?: string, a3?: string, a4?: string}|null $adminCodes
     * @param bool|null $withPopulation
     * @param bool $sortByFeatureClasses
     * @param bool $sortByFeatureCodes
     * @param int|null $limit
     * @return array<int, Location>
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function findLocationsByCoordinate(
        Coordinate $coordinate,
        int|null $distanceMeter = null,
        array|string|null $featureClass = null,
        array|string|null $featureCodes = null,
        Country|null $country = null,
        array|null $adminCodes = [],
        bool|null $withPopulation = null,
        bool $sortByFeatureClasses = false,
        bool $sortByFeatureCodes = false,
        int|null $limit = null
    ): array
    {
        $queryBuilder = $this->createQueryBuilder('l');

        /* Limit result by given distance. */
        if (is_int($distanceMeter)) {
            $queryBuilder
                /* Attention: PostGIS uses lon/lat not lat/lon! */
                ->andWhere('ST_DWithin(
                l.coordinate,
                ST_MakePoint(:longitude, :latitude),
                :distance,
                TRUE
            ) = TRUE')
                ->setParameter('latitude', $coordinate->getLatitude())
                ->setParameter('longitude', $coordinate->getLongitude())
                ->setParameter('distance', $distanceMeter)
            ;
        }

        /* Limit result by feature class. */
        $featureClass = is_string($featureClass) ? [$featureClass] : $featureClass;
        if (is_array($featureClass)) {
            $queryBuilder
                ->leftJoin('l.featureClass', 'fcl')
                ->andWhere('fcl.class IN (:featureClasses)')
                ->setParameter('featureClasses', $featureClass);
        }

        /* Limit result by feature code. */
        $featureCodes = is_string($featureCodes) ? [$featureCodes] : $featureCodes;
        if (is_array($featureCodes)) {
            $queryBuilder
                ->leftJoin('l.featureCode', 'fco')
                ->andWhere('fco.code IN (:featureCodes)')
                ->setParameter('featureCodes', $featureCodes)
            ;
        }

        /* Limit result by country. */
        if ($country instanceof Country) {
            $queryBuilder
                ->andWhere('l.country = :country')
                ->setParameter('country', $country);
        }

        /* Limit with admin codes. */
        if (!is_null($adminCodes)) {

            $queryBuilder
                ->leftJoin('l.adminCode', 'a');

            foreach (CountryConfig::A_ALL as $adminCode) {
                if (array_key_exists($adminCode, $adminCodes)) {
                    $adminName = match ($adminCode) {
                        CountryConfig::A1 => 'admin1Code',
                        CountryConfig::A2 => 'admin2Code',
                        CountryConfig::A3 => 'admin3Code',
                        CountryConfig::A4 => 'admin4Code',
                    };

                    $valueAdminCode = $adminCodes[$adminCode];

                    match ($valueAdminCode) {
                        CountryConfig::NOT_NULL => $queryBuilder
                            ->andWhere(sprintf('a.%s IS NOT NULL', $adminName)),
                        CountryConfig::NULL => $queryBuilder
                            ->andWhere(sprintf('a.%s IS NULL', $adminName)),
                        default => $queryBuilder
                            ->andWhere(sprintf('a.%s = :%s', $adminName, $adminCode))
                            ->setParameter($adminCode, $valueAdminCode),
                    };
                }
            }
        }

        /* Limit with population. */
        if (!is_null($withPopulation)) {
            $queryBuilder->andWhere(sprintf('l.population %s 0', $withPopulation ? '>' : '<='));
        }

        /* Order by given feature codes. */
        if ($sortByFeatureCodes) {
            if (!is_array($featureCodes)) {
                throw new CaseUnsupportedException('$sortByFeatureCodes is used, but no $featureCodes are given.');
            }

            $fieldName = 'fco.code';
            $sortName = 'sortByFeatureCode';
            $queryBuilder
                ->addSelect($this->getCaseString($featureCodes, $fieldName, $sortName))
                ->addOrderBy($sortName, 'ASC')
            ;
        }

        /* Order result by distance (uses <-> for performance reasons). */
        $queryBuilder
            ->addSelect(sprintf(
                'DistanceOperator(l.coordinate, %f, %f) AS HIDDEN distance',
                $coordinate->getLatitude(),
                $coordinate->getLongitude()
            ))
            ->addOrderBy('distance', 'ASC')
        ;

        /* Limit result by number of entities. */
        if (is_int($limit)) {
            $queryBuilder
                ->setMaxResults($limit)
            ;
        }

        /* Returns the result. */
        return array_values(
            (new CheckerArray($queryBuilder->getQuery()->getResult()))
                ->checkClass(Location::class)
        );
    }

    /**
     * Finds the next location given by coordinate.
     *
     * @param Coordinate $coordinate
     * @param string|null $featureClass
     * @param string|array<int, string>|null $featureCodes
     * @param Country|null $country
     * @param array{a1?: string, a2?: string, a3?: string, a4?: string}|null $adminCodes
     * @param bool|null $withPopulation
     * @param bool $sortByFeatureClasses
     * @param bool $sortByFeatureCodes
     * @return Location|null
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function findNextLocationByCoordinate(
        Coordinate $coordinate,
        string|null $featureClass = null,
        string|array|null $featureCodes = null,
        Country|null $country = null,
        array|null $adminCodes = [],
        bool|null $withPopulation = null,
        bool $sortByFeatureClasses = false,
        bool $sortByFeatureCodes = false
    ): Location|null
    {
        $location = $this->findLocationsByCoordinate(
            coordinate: $coordinate,
            featureClass: $featureClass,
            featureCodes: $featureCodes,
            country: $country,
            adminCodes: $adminCodes,
            withPopulation: $withPopulation,
            sortByFeatureClasses: $sortByFeatureClasses,
            sortByFeatureCodes: $sortByFeatureCodes,
            limit: 1
        );

        if (count($location) <= 0) {
            return null;
        }

        return $location[0];
    }

    /**
     * Finds the city given by location.
     *
     * @param LocationEntity $location
     * @return LocationEntity|null
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @throws ParserException
     */
    public function findDistrictByLocation(Location $location): Location|null
    {
        $point = $location->getCoordinate();

        if (is_null($point)) {
            throw new CaseUnsupportedException('Unable to get point from location.');
        }

        $coordinate = new Coordinate(
            $point->getLatitude(),
            $point->getLongitude()
        );

        $countryCode = $location->getCountry()?->getCode();

        if (is_null($countryCode)) {
            throw new CaseUnsupportedException('Unable to get country code from location.');
        }

        return $this->findNextLocationByCoordinate(
            coordinate: $coordinate,
            featureClass: $this->getDistrictFeatureClass($countryCode),
            featureCodes: $this->getDistrictFeatureCodes($countryCode),
            country: $location->getCountry(),
            adminCodes: $this->getDistrictAdminCodes($countryCode, $location),
            withPopulation: $this->getDistrictWithPopulation(),
            sortByFeatureCodes: $this->isDistrictSortByFeatureCodes()
        );
    }

    /**
     * Finds the city given by location.
     *
     * @param LocationEntity $location
     * @return LocationEntity|null
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @throws ParserException
     */
    public function findCityByLocation(Location $location): Location|null
    {
        $point = $location->getCoordinate();

        if (is_null($point)) {
            throw new CaseUnsupportedException('Unable to get point from location.');
        }

        $coordinate = new Coordinate(
            $point->getLatitude(),
            $point->getLongitude()
        );

        $countryCode = $location->getCountry()?->getCode();

        if (is_null($countryCode)) {
            throw new CaseUnsupportedException('Unable to get country code from location.');
        }

        return $this->findNextLocationByCoordinate(
            coordinate: $coordinate,
            featureClass: $this->getCityFeatureClass($countryCode),
            featureCodes: $this->getCityFeatureCodes($countryCode),
            country: $location->getCountry(),
            adminCodes: $this->getCityAdminCodes($countryCode, $location),
            withPopulation: $this->getCityWithPopulation(),
            sortByFeatureCodes: $this->isCitySortByFeatureCodes()
        );
    }

    /**
     * Finds the state given by location.
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
     * Finds the country given by location.
     *
     * @param LocationEntity|null $location
     * @return Location|null
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws TypeInvalidException
     */
    public function findCountryByLocation(Location|null $location): Location|null
    {
        if (is_null($location)) {
            return null;
        }

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
            ->leftJoin('l.adminCode', 'a')
        ;

        switch ($country->getCode()) {
            /* de, etc. */
            default:
                $queryBuilder
                    ->andWhere('fco.code = :featureCode')
                    ->setParameter('featureCode', FeatureClass::FEATURE_CODE_A_PCLI)
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

    /**
     * Builds the case string for order query.
     *
     * @param array<int, string> $caseValues
     * @param string $fieldName
     * @param string $sortName
     * @return string
     */
    protected function getCaseString(array $caseValues, string $fieldName, string $sortName): string
    {
        $whenCases = [];
        $value = 0;
        foreach ($caseValues as $caseValue) {
            $whenCases[] = sprintf('WHEN \'%s\' THEN %d', $caseValue, $value++);
        }

        return sprintf(
            'CASE %s %s ELSE %d END AS HIDDEN %s',
            $fieldName,
            implode(' ', $whenCases),
            $value,
            $sortName
        );
    }

    /**
     * Returns the admin codes for the given location (Country).
     *
     * @param LocationEntity $location
     * @return array{a1?: string, a2?: string, a3?: string, a4?: string}
     * @throws CaseUnsupportedException
     */
    public function getAdminCodesGeneral(LocationEntity $location): array
    {
        $countryCode = $location->getCountry()?->getCode();

        if (is_null($countryCode)) {
            throw new CaseUnsupportedException('Unable to get country code from location.');
        }

        $adminCode = array_key_exists($countryCode, CountryConfig::ADMIN_CODES_CITY_DEFAULT) ?
            CountryConfig::ADMIN_CODES_CITY_DEFAULT[$countryCode] :
            CountryConfig::A4
        ;

        return match ($adminCode) {
            CountryConfig::A1 => [CountryConfig::A1 => (string) $location->getAdminCode()?->getAdmin1Code()],
            CountryConfig::A2 => [CountryConfig::A2 => (string) $location->getAdminCode()?->getAdmin2Code()],
            CountryConfig::A3 => [CountryConfig::A3 => (string) $location->getAdminCode()?->getAdmin3Code()],
            default => [CountryConfig::A4 => (string) $location->getAdminCode()?->getAdmin4Code()]
        };
    }

    /**
     * Returns the admin codes for district.
     *
     * @param string $countryCode
     * @param LocationEntity $location
     * @return array{a1?: string, a2?: string, a3?: string, a4?: string}
     * @throws CaseUnsupportedException
     */
    public function getDistrictAdminCodes(string $countryCode, LocationEntity $location): array
    {
        $districtConfig = array_key_exists($countryCode, CountryConfig::DISTRICT_PLACES) ?
            CountryConfig::DISTRICT_PLACES[$countryCode] :
            CountryConfig::DEFAULT_DISTRICT_CONFIG;

        if (!array_key_exists(CountryConfig::NAME_ADMIN_CODES, $districtConfig)) {
            return $this->getAdminCodesGeneral($location);
        }

        /* if no admin codes are given, use the city admin codes */
        if (is_null($districtConfig[CountryConfig::NAME_ADMIN_CODES])) {
            return $this->getAdminCodesGeneral($location);
        }

        return $districtConfig[CountryConfig::NAME_ADMIN_CODES];
    }

    /**
     * Returns the admin codes for city.
     *
     * @param string $countryCode
     * @param LocationEntity $location
     * @return array{a1?: string, a2?: string, a3?: string, a4?: string}
     * @throws CaseUnsupportedException
     */
    public function getCityAdminCodes(string $countryCode, LocationEntity $location): array
    {
        $cityConfig = array_key_exists($countryCode, CountryConfig::CITY_PLACES) ?
            CountryConfig::CITY_PLACES[$countryCode] :
            CountryConfig::DEFAULT_CITY_CONFIG;

        if (!array_key_exists(CountryConfig::NAME_ADMIN_CODES, $cityConfig)) {
            return $this->getAdminCodesGeneral($location);
        }

        /* if no admin codes are given, use the city admin codes */
        if (is_null($cityConfig[CountryConfig::NAME_ADMIN_CODES])) {
            return $this->getAdminCodesGeneral($location);
        }

        return $cityConfig[CountryConfig::NAME_ADMIN_CODES];
    }

    /**
     * Returns the district with population.
     *
     * @return bool|null
     */
    protected function getDistrictWithPopulation(): bool|null
    {
        return null;
    }

    /**
     * Returns the city with population.
     *
     * @return bool|null
     */
    protected function getCityWithPopulation(): bool|null
    {
        return true;
    }

    /**
     * Returns the district sort by feature codes.
     *
     * @return bool
     */
    protected function isDistrictSortByFeatureCodes(): bool
    {
        return false;
    }

    /**
     * Returns the city sort by feature codes.
     *
     * @return bool
     */
    protected function isCitySortByFeatureCodes(): bool
    {
        return true;
    }

    /**
     * Returns the district feature codes.
     *
     * @param string $countryCode
     * @return array<int, string>
     * @throws TypeInvalidException
     */
    protected function getDistrictFeatureCodes(string $countryCode): array
    {
        $districtConfig = array_key_exists($countryCode, CountryConfig::DISTRICT_PLACES) ?
            CountryConfig::DISTRICT_PLACES[$countryCode] :
            CountryConfig::DEFAULT_DISTRICT_CONFIG;

        if (!array_key_exists(CountryConfig::NAME_FEATURE_CODES, $districtConfig)) {
            return CountryConfig::DEFAULT_DISTRICT_CONFIG[CountryConfig::NAME_FEATURE_CODES];
        }

        $featureCodes = $districtConfig[CountryConfig::NAME_FEATURE_CODES];

//        if (is_null($featureCodes)) {
//            return CountryConfig::DEFAULT_DISTRICT_CONFIG[CountryConfig::NAME_FEATURE_CODES];
//        }

        if (!is_array($featureCodes)) {
            throw new TypeInvalidException('array', gettype($featureCodes));
        }

        return $featureCodes;
    }

    /**
     * Returns the city feature codes.
     *
     * @param string $countryCode
     * @return array<int, string>
     * @throws TypeInvalidException
     */
    protected function getCityFeatureCodes(string $countryCode): array
    {
        $cityConfig = array_key_exists($countryCode, CountryConfig::CITY_PLACES) ?
            CountryConfig::CITY_PLACES[$countryCode] :
            CountryConfig::DEFAULT_CITY_CONFIG;

        if (!array_key_exists(CountryConfig::NAME_FEATURE_CODES, $cityConfig)) {
            return CountryConfig::DEFAULT_CITY_CONFIG[CountryConfig::NAME_FEATURE_CODES];
        }

        $featureCodes = $cityConfig[CountryConfig::NAME_FEATURE_CODES];

//        if (is_null($featureCodes)) {
//            return CountryConfig::DEFAULT_CITY_CONFIG[CountryConfig::NAME_FEATURE_CODES];
//        }

        if (!is_array($featureCodes)) {
            throw new TypeInvalidException('array', gettype($featureCodes));
        }

        return $featureCodes;
    }

    /**
     * Returns the district feature class.
     *
     * @param string $countryCode
     * @return string
     */
    protected function getDistrictFeatureClass(string $countryCode): string
    {
        $districtConfig = array_key_exists($countryCode, CountryConfig::DISTRICT_PLACES) ?
            CountryConfig::DISTRICT_PLACES[$countryCode] :
            CountryConfig::DEFAULT_DISTRICT_CONFIG;

        if (!array_key_exists(CountryConfig::NAME_FEATURE_CLASS, $districtConfig)) {
            return FeatureClass::FEATURE_CLASS_P;
        }

        return $districtConfig[CountryConfig::NAME_FEATURE_CLASS];
    }

    /**
     * Returns the city feature class.
     *
     * @param string $countryCode
     * @return string
     */
    protected function getCityFeatureClass(string $countryCode): string
    {
        $cityConfig = array_key_exists($countryCode, CountryConfig::CITY_PLACES) ?
            CountryConfig::CITY_PLACES[$countryCode] :
            CountryConfig::DEFAULT_CITY_CONFIG;

        if (!array_key_exists(CountryConfig::NAME_FEATURE_CLASS, $cityConfig)) {
            return FeatureClass::FEATURE_CLASS_P;
        }

        return $cityConfig[CountryConfig::NAME_FEATURE_CLASS];
    }
}
