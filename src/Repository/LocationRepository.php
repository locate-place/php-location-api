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
use App\Entity\Location as LocationEntity;
use App\Service\LocationCountryService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpChecker\Checker;
use Ixnode\PhpChecker\CheckerArray;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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
     * @param LocationCountryService $locationCountryService
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(
        protected ManagerRegistry $registry,
        protected LocationCountryService $locationCountryService,
        protected ParameterBagInterface $parameterBag
    )
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
     * @param array<int, string>|string|null $featureClasses
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
        array|string|null $featureClasses = null,
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
        $featureClasses = is_string($featureClasses) ? [$featureClasses] : $featureClasses;
        if (is_array($featureClasses)) {
            $queryBuilder
                ->leftJoin('l.featureClass', 'fcl')
                ->andWhere('fcl.class IN (:featureClasses)')
                ->setParameter('featureClasses', $featureClasses);
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
        $this->limitAdminCodes($queryBuilder, $adminCodes);

        /* Limit with population. */
        if (!is_null($withPopulation)) {
            $queryBuilder->andWhere(sprintf('l.population %s 0', $withPopulation ? '>' : '<='));
        }

        /* Order by given feature classes. */
        if ($sortByFeatureClasses) {
            if (!is_array($featureClasses)) {
                throw new CaseUnsupportedException('$sortByFeatureClasses is used, but no $sortByFeatureClasses are given.');
            }

            $fieldName = 'fcl.class';
            $sortName = 'sortByFeatureClass';
            $queryBuilder
                ->addSelect($this->getCaseString($featureClasses, $fieldName, $sortName))
                ->addOrderBy($sortName, 'ASC')
            ;
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
     * @param array<int, string>|string|null $featureClasses
     * @param array<int, string>|string|null $featureCodes
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
        array|string|null $featureClasses = null,
        array|string|null $featureCodes = null,
        Country|null $country = null,
        array|null $adminCodes = [],
        bool|null $withPopulation = null,
        bool $sortByFeatureClasses = false,
        bool $sortByFeatureCodes = false
    ): Location|null
    {
        $location = $this->findLocationsByCoordinate(
            coordinate: $coordinate,
            featureClasses: $featureClasses,
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
     * Finds the next admin configuration given by coordinate.
     *
     * @param Coordinate $coordinate
     * @param array<int, string>|string|null $featureClasses
     * @param array<int, string>|string|null $featureCodes
     * @return array{a1?: string, a2?: string, a3?: string, a4?: string}
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    public function findNextAdminConfiguration(
        Coordinate $coordinate,
        array|string|null $featureClasses = FeatureClass::FEATURE_CLASS_A,
        array|string|null $featureCodes = FeatureClass::FEATURE_CLASS_A,
    ): array
    {
        $location = $this->findNextLocationByCoordinate(
            coordinate: $coordinate,
            featureClasses: $featureClasses,
            featureCodes: $featureCodes,
        );

        $notNull = (new TypeCastingHelper($this->parameterBag->get('db_not_null')))->strval();
        $null = (new TypeCastingHelper($this->parameterBag->get('db_null')))->strval();

        $adminConfiguration = [
            'a1' => $location?->getAdminCode()?->getAdmin1Code() ?: $null,
            'a2' => $location?->getAdminCode()?->getAdmin2Code() ?: $null,
            'a3' => $location?->getAdminCode()?->getAdmin3Code() ?: $null,
            'a4' => $location?->getAdminCode()?->getAdmin4Code() ?: $null,
        ];

        foreach ($adminConfiguration as $key => &$value) {
            if ($key === 'a1') {
                continue;
            }

            if ($value === $null) {
                continue;
            }

            if ($value === $notNull) {
                continue;
            }

            $value = sprintf('?%s', $value);
        }

        return $adminConfiguration;
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
            featureClasses: $this->locationCountryService->getDistrictFeatureClass($location),
            featureCodes: $this->locationCountryService->getDistrictFeatureCodes($location),
            country: $location->getCountry(),
            adminCodes: $this->locationCountryService->getDistrictAdminCodes($location),
            withPopulation: $this->locationCountryService->getDistrictWithPopulation($location),
            sortByFeatureCodes: $this->locationCountryService->isDistrictSortByFeatureCodes($location)
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
            featureClasses: $this->locationCountryService->getCityFeatureClass($location),
            featureCodes: $this->locationCountryService->getCityFeatureCodes($location),
            country: $location->getCountry(),
            adminCodes: $this->locationCountryService->getCityAdminCodes($location),
            withPopulation: $this->locationCountryService->getCityWithPopulation($location),
            sortByFeatureCodes: $this->locationCountryService->isCitySortByFeatureCodes($location)
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
    private function getCaseString(array $caseValues, string $fieldName, string $sortName): string
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
     * Limit admin codes.
     *
     * @param QueryBuilder $queryBuilder
     * @param array{a1?: string, a2?: string, a3?: string, a4?: string}|null $adminCodes
     * @return void
     * @throws CaseUnsupportedException
     */
    private function limitAdminCodes(QueryBuilder $queryBuilder, array|null $adminCodes): void
    {
        if (is_null($adminCodes)) {
            return;
        }

        $notNull = $this->parameterBag->get('db_not_null');
        $null = $this->parameterBag->get('db_null');

        $queryBuilder
            ->leftJoin('l.adminCode', 'a');

        $adminCodesAll = $this->parameterBag->get('admin_codes_all');

        if (!is_array($adminCodesAll)) {
            throw new CaseUnsupportedException('The given admin_codes_all configuration is not an array.');
        }

        foreach ($adminCodesAll as $adminCode) {
            if (array_key_exists($adminCode, $adminCodes)) {
                $adminName = match ($adminCode) {
                    'a1' => 'admin1Code',
                    'a2' => 'admin2Code',
                    'a3' => 'admin3Code',
                    'a4' => 'admin4Code',
                };

                $valueAdminCode = $adminCodes[$adminCode];

                match (true) {
                    $valueAdminCode === $notNull => $queryBuilder
                        ->andWhere(sprintf('a.%s IS NOT NULL', $adminName)),
                    $valueAdminCode === $null => $queryBuilder
                        ->andWhere(sprintf('a.%s IS NULL', $adminName)),
                    str_starts_with($valueAdminCode, '?') => $queryBuilder
                        ->andWhere(sprintf('a.%s = :%s OR a.%s IS NULL', $adminName, $adminCode, $adminName))
                        ->setParameter($adminCode, substr($valueAdminCode, 1)),
                    default => $queryBuilder
                        ->andWhere(sprintf('a.%s = :%s', $adminName, $adminCode))
                        ->setParameter($adminCode, $valueAdminCode),
                };
            }
        }
    }
}
