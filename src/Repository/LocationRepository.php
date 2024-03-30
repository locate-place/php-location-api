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

use App\Constants\DB\FeatureClass as DbFeatureClass;
use App\Constants\DB\FeatureCode as DbFeatureCode;
use App\Constants\Language\LanguageCode;
use App\Entity\AlternateName;
use App\Entity\Country;
use App\Entity\FeatureCode;
use App\Entity\Location;
use App\Entity\River;
use App\Repository\Base\BaseCoordinateRepository;
use App\Service\LocationServiceConfig;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpChecker\Checker;
use Ixnode\PhpChecker\CheckerArray;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use LogicException;
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
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocationRepository extends BaseCoordinateRepository
{
    /**
     * @param ManagerRegistry $registry
     * @param EntityManagerInterface $entityManager
     * @param LocationServiceConfig $locationCountryService
     * @param ParameterBagInterface $parameterBag
     * @param RiverRepository $riverRepository
     */
    public function __construct(
        protected ManagerRegistry $registry,
        protected EntityManagerInterface $entityManager,
        protected LocationServiceConfig $locationCountryService,
        protected ParameterBagInterface $parameterBag,
        protected RiverRepository $riverRepository,
    )
    {
        parent::__construct($registry, $parameterBag);
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
     * Translates the given feature codes to ids.
     *
     * @param string[]|string|null $featureCodes
     * @return int[]
     */
    private function translateFeatureCodesToIds(array|string $featureCodes = null): array
    {
        $featureCodes = is_string($featureCodes) ? explode(',', $featureCodes) : $featureCodes;

        $repository = $this->getEntityManager()->getRepository(FeatureCode::class);

        $featureCodeEntities = $repository->findBy(['code' => $featureCodes]);

        $featureCodeIds = [];

        foreach ($featureCodeEntities as $featureCodeEntity) {
            $featureCodeIds[] = (int) $featureCodeEntity->getId();
        }

        return $featureCodeIds;
    }

    /**
     * Finds the locations from given geoname ids.
     *
     * @param int[] $geonameIds
     * @return array<int, Location>
     */
    public function findLocationsByGeonameIds(array $geonameIds): array
    {
        return $this->findBy(['geonameId' => $geonameIds]);
    }

    /**
     * Finds the locations from given search string.
     *
     * @param string|string[] $search
     * @param int|null $limit
     * @return array<int, Location>
     */
    public function findBySearch(string|array $search, int|null $limit = null): array
    {
        if (is_string($search)) {
            $search = [$search];
        }

        $queryBuilder = $this->createQueryBuilder('l')
            ->join('l.alternateNames', 'a')
            ->setMaxResults($limit)
        ;

        /* Loop through each search term and add it as an AND condition */
        foreach ($search as $index => $term) {
            $queryBuilder->andWhere('ILIKE(a.alternateName, :name'.$index.') = true')
                ->setParameter('name'.$index, '%'.$term.'%');
        }

        $locations = $queryBuilder->getQuery()->execute();

        if (!is_array($locations)) {
            throw new LogicException('Unsupported query type.');
        }

        $locationsResult = [];

        foreach ($locations as $location) {
            if (!$location instanceof Location) {
                continue;
            }

            $locationsResult[] = $location;
        }

        return $locationsResult;
    }

    /**
     * Finds the locations from given latitude and longitude ordered by distance.
     *
     * Query example:
     * --------------
       SELECT
         id,
         name,
         ST_Distance(coordinate, ST_MakePoint(13.741351013208588, 51.06115159751123)::geography) AS distance
       FROM location
       WHERE ST_DWithin(coordinate, ST_MakePoint(13.741351013208588, 51.06115159751123)::geography, 100000)
       ORDER BY distance
       LIMIT 50;
     *
     * Show indices:
     * -------------
       SELECT
         i.relname AS index_name,
         am.amname AS index_type,
         idx.indisprimary AS is_primary,
         idx.indisunique AS is_unique,
         pg_get_indexdef(idx.indexrelid) AS index_definition
       FROM
         pg_index AS idx
       JOIN
         pg_class AS i ON i.oid = idx.indexrelid
       JOIN
         pg_am AS am ON i.relam = am.oid
       WHERE
         idx.indrelid = 'location'::regclass
       ORDER BY
         index_name;
     *
     * @param Coordinate|null $coordinate
     * @param int|null $distanceMeter
     * @param array<int, string>|string|null $featureClasses
     * @param array<int, string>|string|null $featureCodes
     * @param Country|null $country
     * @param array{a1?: string, a2?: string, a3?: string, a4?: string}|null $adminCodes
     * @param bool|null $withPopulation
     * @param bool $sortByFeatureClasses
     * @param bool $sortByFeatureCodes
     * @param bool $sortByPopulation
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
        Coordinate|null $coordinate = null,
        int|null $distanceMeter = null,
        array|string|null $featureClasses = null,
        array|string|null $featureCodes = null,
        Country|null $country = null,
        array|null $adminCodes = [],
        bool|null $withPopulation = null,
        bool $sortByFeatureClasses = false,
        bool $sortByFeatureCodes = false,
        bool $sortByPopulation = false,
        int|null $limit = null
    ): array
    {
        $queryBuilder = $this->createQueryBuilder('l');

        /* Limit result by given distance. */
        if (!is_null($coordinate) && is_int($distanceMeter)) {
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
        if (!is_null($featureCodes)) {
            $featureCodesIds = $this->translateFeatureCodesToIds($featureCodes);
            $queryBuilder
                ->andWhere('l.featureCode IN (:featureCodes)')
                ->setParameter('featureCodes', $featureCodesIds)
            ;
        }

        /* Limit result by country. */
        if ($country instanceof Country) {
            $queryBuilder
                ->andWhere('l.country = :country')
                ->setParameter('country', $country);
        }

        /* Limit with admin codes. */
        $this->limitAdminCodes($queryBuilder, 'l', $adminCodes);

        /* Limit with the given population. */
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
            $featureCodes = is_string($featureCodes) ? [$featureCodes] : $featureCodes;

            if (!is_array($featureCodes)) {
                throw new CaseUnsupportedException('$sortByFeatureCodes is used, but no $featureCodes are given.');
            }

            if (count($featureCodes) > 1) {
                $fieldName = 'fco.code';
                $sortName = 'sortByFeatureCode';
                $queryBuilder
                    ->leftJoin('l.featureCode', 'fco')
                    ->addSelect($this->getCaseString($featureCodes, $fieldName, $sortName))
                    ->addOrderBy($sortName, 'ASC')
                ;
            }
        }

        /* Order by given population. */
        if ($sortByPopulation) {
            $fieldName = 'l.population';
            $sortName = 'sortByPopulation';
            $queryBuilder
                ->addSelect(sprintf('CASE WHEN %s IS NOT NULL THEN %s ELSE 0 END AS HIDDEN %s', $fieldName, $fieldName, $sortName))
                ->addOrderBy($sortName, 'DESC')
            ;
        }

        /* Order result by distance (uses <-> for performance reasons). */
        if (!is_null($coordinate)) {
            $queryBuilder
                ->addSelect(sprintf(
                    'DistanceOperator(l.coordinate, %f, %f) AS HIDDEN distance',
                    $coordinate->getLatitude(),
                    $coordinate->getLongitude()
                ))
                ->addOrderBy('distance', 'ASC')
            ;
        }

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
     * @param Coordinate|null $coordinate
     * @param array<int, string>|string|null $featureClasses
     * @param array<int, string>|string|null $featureCodes
     * @param Country|null $country
     * @param array{a1?: string, a2?: string, a3?: string, a4?: string}|null $adminCodes
     * @param bool|null $withPopulation
     * @param bool $sortByFeatureClasses
     * @param bool $sortByFeatureCodes
     * @param bool $sortByPopulation
     * @return Location|null
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function findNextLocationByCoordinate(
        Coordinate|null $coordinate = null,
        array|string|null $featureClasses = null,
        array|string|null $featureCodes = null,
        Country|null $country = null,
        array|null $adminCodes = [],
        bool|null $withPopulation = null,
        bool $sortByFeatureClasses = false,
        bool $sortByFeatureCodes = false,
        bool $sortByPopulation = false
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
            sortByPopulation: $sortByPopulation,
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
        array|string|null $featureClasses = DbFeatureClass::A,
        array|string|null $featureCodes = DbFeatureClass::A,
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
     * @param Location $location
     * @param Coordinate|null $coordinate
     * @return Location|null
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function findDistrictByLocation(Location $location, ?Coordinate $coordinate = null): Location|null
    {
        $coordinate = $coordinate ?: $location->getCoordinateIxnode();

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
     * Finds the borough given by location.
     *
     * @param Location $location
     * @param Coordinate|null $coordinate
     * @return Location|null
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function findBoroughByLocation(Location $location, ?Coordinate $coordinate = null): Location|null
    {
        $coordinate = $coordinate ?: $location->getCoordinateIxnode();

        $countryCode = $location->getCountry()?->getCode();

        if (is_null($countryCode)) {
            throw new CaseUnsupportedException('Unable to get country code from location.');
        }

        return $this->findNextLocationByCoordinate(
            coordinate: $coordinate,
            featureClasses: $this->locationCountryService->getBoroughFeatureClass($location),
            featureCodes: $this->locationCountryService->getBoroughFeatureCodes($location),
            country: $location->getCountry(),
            adminCodes: $this->locationCountryService->getBoroughAdminCodes($location),
            withPopulation: $this->locationCountryService->getBoroughWithPopulation($location),
            sortByFeatureCodes: $this->locationCountryService->isBoroughSortByFeatureCodes($location)
        );
    }

    /**
     * Finds the city given by location.
     *
     * @param Location $location
     * @param Coordinate|null $coordinate
     * @return Location|null
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function findCityByLocation(Location $location, ?Coordinate $coordinate = null): Location|null
    {
        $coordinate = $coordinate ?: $location->getCoordinateIxnode();

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
            sortByFeatureCodes: $this->locationCountryService->isCitySortByFeatureCodes($location),
            sortByPopulation: $this->locationCountryService->isCitySortByPopulation($location)
        );
    }

    /**
     * Finds the state given by location.
     *
     * @param Location $location
     * @return Location|null
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function findStateByLocation(Location $location): Location|null
    {
        $coordinate = $this->locationCountryService->isStateUseCoordinate($location) ? $location->getCoordinateIxnode() : null;

        return $this->findNextLocationByCoordinate(
            coordinate: $coordinate,
            featureClasses: $this->locationCountryService->getStateFeatureClass($location),
            featureCodes: $this->locationCountryService->getStateFeatureCodes($location),
            country: $location->getCountry(),
            adminCodes: $this->locationCountryService->getStateAdminCodes($location),
            withPopulation: $this->locationCountryService->getStateWithPopulation($location),
            sortByFeatureCodes: $this->locationCountryService->isStateSortByFeatureCodes($location)
        );
    }

    /**
     * Finds the country given by location.
     *
     * @param Location|null $location
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
            ->setParameter('featureClass', DbFeatureClass::A);

        $queryBuilder
            ->leftJoin('l.featureCode', 'fco')
            ->leftJoin('l.adminCode', 'a')
        ;

        switch ($country->getCode()) {
            /* de, etc. */
            default:
                $queryBuilder
                    ->andWhere('fco.code = :featureCode')
                    ->setParameter('featureCode', 'PCLI')
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
     * Finds locations with at least one iso_language == $isoLanguage.
     *
     * @param string $isoLanguage
     * @param bool $typeMustBeNull
     * @param int|null $id
     * @param int|null $limit
     * @return array<int, Location>
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function findLocationsWithLinkIsoLanguage(
        string $isoLanguage,
        bool $typeMustBeNull = false,
        int|null $id = null,
        int|null $limit = null
    ): array
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->leftJoin('l.alternateNames', 'a')

            ->where('a.isoLanguage = :isoLanguage')
            ->setParameter('isoLanguage', $isoLanguage)
        ;

        if ($typeMustBeNull) {
            $queryBuilder->andWhere('a.type IS NULL');
        }

        if (!is_null($id)) {
            $queryBuilder
                ->andWhere('l.id = :id')
                ->setParameter('id', $id)
            ;
        }

        if (!is_null($limit)) {
            $queryBuilder->setMaxResults($limit);
        }

        return array_values(
            (new CheckerArray($queryBuilder->getQuery()->getResult()))
                ->checkClass(Location::class)
        );
    }

    /**
     * Returns all capital cities.
     *
     * @return Location[]
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    public function findCapitals(): array
    {
        return $this->findLocationsByCoordinate(
            featureClasses: DbFeatureClass::P,
            featureCodes: DbFeatureCode::PPLC,
        );
    }

    /**
     * Returns all airports.
     *
     * @return Location[]
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    public function findAirports(): array
    {
        return $this->findLocationsByCoordinate(
            featureClasses: DbFeatureClass::S,
            featureCodes: DbFeatureCode::AIRP,
            limit: 100,
        );
    }

    /**
     * Returns the number of locations with given iso language.
     *
     * @param string $isoLanguage
     * @return int
     * @throws NonUniqueResultException
     * @throws TypeInvalidException
     * @throws NoResultException
     */
    public function getNumberOfLocationsIsoLanguage(string $isoLanguage): int
    {
        $queryBuilder = $this->createQueryBuilder('l');

        $queryBuilder
            ->select('COUNT(l)')
            ->leftJoin('l.alternateNames', 'a')
            ->where('a.isoLanguage = :isoLanguage')
            ->setParameter('isoLanguage', $isoLanguage)
            ->andWhere('a.type IS NULL')
        ;

        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        if (!is_int($count)) {
            throw new TypeInvalidException('int', gettype($count));
        }

        return $count;
    }

    /**
     * Finds rivers within location table that are not mapped to river table.
     *
     * @param int|null $limit
     * @param Country|null $country
     * @param bool $ignoreIgnored
     * @return array<int, Location>
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function findRiversWithoutMapping(
        int|null $limit = null,
        Country|null $country = null,
        bool $ignoreIgnored = false
    ): array
    {
        $featureCodes = [DbFeatureCode::STM];

        $featureCodesIds = $this->translateFeatureCodesToIds($featureCodes);

        $queryBuilder = $this->createQueryBuilder('l');

        $queryBuilder
            ->leftJoin(
                AlternateName::class,
                'a',
                'WITH',
                'l.id = a.location AND (a.isoLanguage IS NULL OR a.isoLanguage IN (:isoLanguages))'
            )
            ->setParameter('isoLanguages', [LanguageCode::DE, LanguageCode::EN])
        ;

        $queryBuilder
            ->addSelect(sprintf('string_agg(a.alternateName, \'%s\' ORDER BY a.alternateName) AS alternateNames', Location::NAME_SEPARATOR))
            ->addGroupBy('l.id')
        ;

        /* Only find given feature codes. */
        $queryBuilder
            ->andWhere('l.featureCode IN (:featureCodes)')
            ->setParameter('featureCodes', $featureCodesIds)
        ;

        /* Limit result by country. */
        if ($country instanceof Country) {
            $queryBuilder
                ->andWhere('l.country = :country')
                ->setParameter('country', $country);
        }

        /* Only find locations that are not mapped to river table. */
        $queryBuilder->leftJoin('l.rivers', 'r');
        match ($ignoreIgnored) {
            true => $queryBuilder->andWhere('r.id IS NULL'),
            false => $queryBuilder->andWhere(
                $queryBuilder->expr()->andX(
                    'r.id IS NULL',
                    'l.mappingRiverIgnore = :mappingRiverIgnore'
                )
            )->setParameter('mappingRiverIgnore', false),
        };

//        /* 127142 = Elbe */
//        $queryBuilder
//            ->andWhere('l.id = 127142')
//        ;

//        /* 48372 = Prießnitz */
//        $queryBuilder
//            ->andWhere('l.id = 48372')
//        ;

        /* Limit the result by number of entities. */
        if (is_int($limit)) {
            $queryBuilder
                ->setMaxResults($limit)
            ;
        }

        $result = $queryBuilder->getQuery()->getResult();

        if (!is_array($result)) {
            throw new LogicException(sprintf('Result must be an array. "%s" given.', gettype($result)));
        }

        return $this->hydrateObjects($result);
    }

    /**
     * Find river locations.
     *
     * @param Coordinate|null $coordinate
     * @param int|null $distanceMeter
     * @param Country|null $country
     * @param int|null $limit
     * @return Location[]
     * @throws TypeInvalidException
     * @throws ORMException
     */
    public function findRiversAsLocations(
        Coordinate|null $coordinate,
        int|null $distanceMeter = null,
        Country|null $country = null,
        int|null $limit = null
    ): array
    {
        $rivers = $this->riverRepository->findRivers(
            coordinate: $coordinate,
            distanceMeter: $distanceMeter,
            country: $country,
            limit: $limit
        );

        $riverLocations = [];

        foreach ($rivers as $river) {
            /* Use doctrine proxy because findRivers returns "non-persistent" doctrine objects. */
            $riverProxy = $this->entityManager->getReference(River::class, $river->getId());

            if (is_null($riverProxy)) {
                throw new LogicException(sprintf('Could not find river with id "%s".', $river->getId()));
            }

            /** @var Location[] $locations */
            $locations = $riverProxy->getLocations();

            /* This river does not exist within location table. */
            if (count($locations) <= 0) {
                continue;
            }

            $location = $locations[0];

            $closestCoordinate = $river->getClosestCoordinate();

            if (is_null($closestCoordinate)) {
                throw new LogicException(sprintf('Could not find closest coordinate for river with id "%s".', $river->getId()));
            }

            /* Set the new closest coordinate. */
            $location->setCoordinate($closestCoordinate);

            $riverLocations[] = $location;
        }

        return $riverLocations;
    }

    /**
     * Hydrates the given object.
     *
     * @param Location|array<int|string, mixed> $object
     * @return Location
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

            if (!is_string($value) && !is_null($value)) {
                throw new LogicException(sprintf('$value expected to be a string or null. "%s" given.', gettype($value)));
            }

            match ($property) {
                'alternateNames' => $location->setNames(
                    explode(
                        Location::NAME_SEPARATOR,
                        (
                            is_null($value) ? '' : $value.Location::NAME_SEPARATOR
                        ).$location->getName()
                    )
                ),
                default => throw new LogicException(sprintf('Unknown property "%s".', $property)),
            };
        }

        if (is_null($location)) {
            throw new LogicException('Location was not found within db result.');
        }

        return $location;
    }

    /**
     * Hydrates the given objects.
     *
     * @param array<int, Location|array<int|null, mixed>> $objects
     * @return Location[]
     */
    public function hydrateObjects(array $objects): array
    {
        return array_map(fn(Location|array $object) => $this->hydrateObject($object), $objects);
    }
}
