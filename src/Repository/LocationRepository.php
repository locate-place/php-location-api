<?php

/*
 * This file is part of the locate-place/php-location-api project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Repository;

use App\Constants\Code\Prediction;
use App\Constants\DB\FeatureClass as DbFeatureClass;
use App\Constants\DB\FeatureCode as DbFeatureCode;
use App\Constants\DB\Limit;
use App\Constants\Key\KeyArray;
use App\Constants\Language\LanguageCode;
use App\Constants\Place\AdminType;
use App\Constants\Place\LocationType;
use App\Entity\AlternateName;
use App\Entity\Country;
use App\Entity\FeatureClass;
use App\Entity\Location;
use App\Entity\River;
use App\Entity\RiverPart;
use App\Repository\Base\BaseCoordinateRepository;
use App\Service\LocationService;
use App\Service\LocationServiceConfig;
use App\Utils\Doctrine\EntityProcessor;
use App\Utils\Doctrine\ResultProcessor;
use App\Utils\Feature\FeatureContainer;
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
use Symfony\Contracts\Translation\TranslatorInterface;

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
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocationRepository extends BaseCoordinateRepository
{
    private const INDEX_NAME_POPULATION = 9_999_999_999;

    /**
     * @param ManagerRegistry $registry
     * @param EntityManagerInterface $entityManager
     * @param LocationServiceConfig $locationCountryService
     * @param ParameterBagInterface $parameterBag
     * @param TranslatorInterface $translator
     * @param RiverRepository $riverRepository
     * @param EntityProcessor $entityProcessor
     * @param ResultProcessor $resultProcessor
     */
    public function __construct(
        protected ManagerRegistry $registry,
        protected EntityManagerInterface $entityManager,
        protected LocationServiceConfig $locationCountryService,
        protected ParameterBagInterface $parameterBag,
        protected TranslatorInterface $translator,
        protected RiverRepository $riverRepository,
        protected EntityProcessor $entityProcessor,
        protected ResultProcessor $resultProcessor
    )
    {
        parent::__construct(
            $registry,
            $parameterBag,
            $translator,
            $this
        );
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
    public function translateFeatureCodesToIds(array|string|null $featureCodes = null): array
    {
        if (is_null($featureCodes)) {
            return [];
        }

        $featureCodes = is_string($featureCodes) ? explode(',', $featureCodes) : $featureCodes;

        $featureCodesTranslated = [];

        foreach ($featureCodes as $featureCode) {
            $constantName = "\App\Constants\DB\FeatureCodeToId::$featureCode";

            if (!defined($constantName)) {
                continue;
            }

            $translated = constant($constantName);

            if (!is_int($translated)) {
                throw new LogicException(sprintf('Invalid feature code given: "%s"', $featureCode));
            }

            $featureCodesTranslated[] = $translated;
        }

        return $featureCodesTranslated;
    }

    /**
     * Translates the given feature classes to ids.
     *
     * @param string[]|string|null $featureClasses
     * @return int[]
     */
    private function translateFeatureClassesToIds(array|string $featureClasses = null): array
    {
        $featureClasses = is_string($featureClasses) ? explode(',', $featureClasses) : $featureClasses;

        $repository = $this->getEntityManager()->getRepository(FeatureClass::class);

        $featureClassEntities = $repository->findBy(['class' => $featureClasses]);

        $featureClassIds = [];

        foreach ($featureClassEntities as $featureClassEntity) {
            $featureClassIds[] = (int) $featureClassEntity->getId();
        }

        return $featureClassIds;
    }

    /**
     * Builds the index for given location.
     *
     * @param Location $location
     * @param int|null $rank
     * @param bool $sortByPopulation
     * @param bool $sortByFeatureCodes
     * @return string
     */
    private function getIndex(
        Location $location,
        int|null $rank,
        bool $sortByPopulation,
        bool $sortByFeatureCodes
    ): string
    {
        if (is_null($rank)) {
            throw new LogicException('Rank is null.');
        }

        return sprintf(
            '%010d-%010d-%010.2f',
            $sortByFeatureCodes ? $rank : 1,
            $sortByPopulation ? (self::INDEX_NAME_POPULATION - (int) ($location->getPopulation() ?? 0)) : self::INDEX_NAME_POPULATION,
            $location->getClosestDistance()
        );
    }

    /**
     * Returns the smallest index.
     *
     * @param array<string, Location> $indexes
     * @return string|null
     */
    private function getSmallestIndex(array $indexes): string|null
    {
        if (count($indexes) <= 0) {
            return null;
        }

        $keys = array_keys($indexes);

        return min($keys);
    }

    /**
     * Calculates the city, city adm, district, district adm from given locations.
     *
     * @param Location $currentLocation
     * @param Location[] $locations
     * @return array{city-adm: Location|null, city: Location|null, district-adm: Location|null, district: Location|null}
     * @throws CaseUnsupportedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getAdminLocations(Location $currentLocation, array $locations): array
    {
        $citySortByPopulation = $this->locationCountryService->isCitySortByPopulation($currentLocation);
        $districtSortByPopulation = $this->locationCountryService->isDistrictSortByPopulation($currentLocation);

        $citySortByFeatureCodes = $this->locationCountryService->isCitySortByFeatureCodes($currentLocation);
        $districtSortByFeatureCodes = $this->locationCountryService->isDistrictSortByFeatureCodes($currentLocation);

        $admin2 = null;
        $admin3 = null;
        $admin4 = null;
        $admin5 = null;

        $cities = [];
        $districts = [];

        foreach ($locations as $location) {
            switch ($location->getLocationType()) {
                case LocationType::ADM2: $admin2 = $location; break;
                case LocationType::ADM3: $admin3 = $location; break;
                case LocationType::ADM4: $admin4 = $location; break;
                case LocationType::ADM5: $admin5 = $location; break;

                case LocationType::CITY:
                    $cities[$this->getIndex($location, $location->getRankCity(), $citySortByPopulation, $citySortByFeatureCodes)] = $location;
                    break;

                case LocationType::DISTRICT:
                    $districts[$this->getIndex($location, $location->getRankDistrict(), $districtSortByPopulation, $districtSortByFeatureCodes)] = $location;
                    break;

                case LocationType::CITY_DISTRICT:
                    $rankCity = $location->getRankCity();
                    $rankDistrict = $location->getRankDistrict();

                    if (!is_null($rankCity)) {
                        $cities[$this->getIndex($location, $location->getRankCity(), $citySortByPopulation, $citySortByFeatureCodes)] = $location;
                    }
                    if (!is_null($rankDistrict)) {
                        $districts[$this->getIndex($location, $location->getRankDistrict(), $districtSortByPopulation, $districtSortByFeatureCodes)] = $location;
                    }
                    break;
            }
        }

        /* Find the next district. */
        $smallestIndexDistricts = $this->getSmallestIndex($districts);
        $district = is_null($smallestIndexDistricts) ? null : $districts[$smallestIndexDistricts];

        /* Find the next city. */
        $smallestIndexCities = $this->getSmallestIndex($cities);
        $city = is_null($smallestIndexCities) ? null : $cities[$smallestIndexCities];

        /* Use the next city if district and city is equal. */
        if (!is_null($city) && !is_null($district) && $city->getGeonameId() === $district->getGeonameId()) {
            unset($cities[$smallestIndexCities]);
            $smallestIndexCities = $this->getSmallestIndex($cities);

            $city = is_null($smallestIndexCities) ? null : $cities[$smallestIndexCities];
        }

        $adminArea = $this->locationCountryService->getAdminDistrictMatch($currentLocation);

        $cityAdm = match ($adminArea) {
            AdminType::A2 => $admin2,
            AdminType::A3 => $admin3,
            AdminType::A4 => $admin4,
            default => null,
        };
        $districtAdm = match ($adminArea) {
            AdminType::A1 => $admin2,
            AdminType::A2 => $admin3,
            AdminType::A3 => $admin4,
            AdminType::A4 => $admin5,
            default => null,
        };

        $this->debugAdminAreas(
            $adminArea,
            $admin2,
            $admin3,
            $admin4,
            $admin5,
            $cityAdm,
            $districtAdm,
            $city,
            $district
        );

        return [
            'city-adm' => $cityAdm,
            'city' => $city,
            'district-adm' => $districtAdm,
            'district' => $district,
        ];
    }

    /**
     * Finds all admin areas for the given location.
     *
     * @param Location $location
     * @param Coordinate $coordinate
     * @return array{city-municipality: Location|null, district-locality: Location|null}
     * @throws CaseUnsupportedException
     * @throws ORMException
     * @throws TypeInvalidException
     */
    public function findAdminAreas(
        Location $location,
        Coordinate $coordinate
    ): array
    {
        $query = $this->queryBuilder->getAdminQuery($location, $coordinate);
        $this->debugNativeQuery($query);

        /* @var array<int, Location|array<int, mixed>> $results */
        $results = $query->getResult();

        /* @phpstan-ignore-next-line -> getResult will give array<int, Location|array<int, mixed>> */
        $locations = $this->resultProcessor->hydrateObjects($results);

        /* Reload locations. */
        $locations = $this->entityProcessor->reloadLocations($locations);

        [
            'city-adm' => $cityAdm,
            'city' => $city,
            'district-adm' => $districtAdm,
            'district' => $district,
        ] = $this->getAdminLocations($location, $locations);

        $city ??= $cityAdm;
        $district ??= $districtAdm;

//        $this->extendName($city, $cityAdm?->getName() ?? null);
//        $this->extendName($district, $districtAdm?->getName() ?? null);

        if (!is_null($city) && !is_null($district) && $city->getId() === $district->getId()) {
            $district = null;
        }

        return [
            KeyArray::CITY_MUNICIPALITY => $city,
            KeyArray::DISTRICT_LOCALITY => $district,
        ];
    }

//    /**
//     * Function to extend the name.
//     *
//     * @param Location|null $location
//     * @param string|null $name
//     * @return void
//     */
//    private function extendName(Location|null $location, string|null $name): void
//    {
//        if (is_null($location)) {
//            return;
//        }
//        if (is_null($name)) {
//            return;
//        }
//
//        $nameOrigin = $location->getName();
//
//        if (is_null($nameOrigin)) {
//            $location->setName($name);
//            return;
//        }
//
//        if ($nameOrigin === $name) {
//            return;
//        }
//
//        $location->setName(sprintf('%s (%s)', $nameOrigin, $name));
//    }

    /**
     * Finds the locations from given geoname ids.
     *
     * @param int[] $geonameIds
     * @param int|null $limit
     * @param int $page
     * @param Coordinate|null $coordinate
     * @param string $sortBy
     * @return array<int, Location>
     * @throws ORMException
     * @throws TypeInvalidException
     */
    public function findLocationsByGeonameIds(
        /* Search term. */
        array $geonameIds,

        /* Filter configuration. */
        int|null $limit = Limit::LIMIT_10,
        int $page = LocationService::PAGE_FIRST,

        /* Configuration */
        Coordinate|null $coordinate = null,

        /* Sort configuration */
        string $sortBy = LocationService::SORT_BY_RELEVANCE,
    ): array
    {
        $locationIds = array_map(fn($location) => (int) $location->getId(), $this->findBy(['geonameId' => $geonameIds]));

        $query = match(true) {
            $coordinate instanceof Coordinate => $this->queryBuilder->getQueryLocationIds(
                locationIds: $locationIds,
                limit: $limit,
                page: $page,
                coordinate: $coordinate,
                sortBy: $sortBy
            ),
            default => $this->queryBuilder->getQueryLocationIds(
                locationIds: $locationIds,
                limit: $limit,
                page: $page,
                sortBy: $sortBy,
            )
        };

        /* @var array<int, Location|array<int, mixed>> $results */
        $results = $query->getResult();

        /* @phpstan-ignore-next-line -> getResult will give array<int, Location|array<int, mixed>> */
        $locations = $this->resultProcessor->hydrateObjects($results);

        return $this->entityProcessor->reloadLocations($locations);
    }

    /**
     * Finds the locations from given search string.
     *
     * @param string|string[] $search
     * @param int|null $distanceMeter
     * @param array<int, string>|string|null $featureClass
     * @param array<int, string>|string|null $featureCode
     * @param int|null $limit
     * @param string|null $isoLanguage
     * @param string|null $country
     * @param int $page
     * @param Coordinate|null $coordinate
     * @param string $sortBy
     * @return array<int, Location>
     * @throws ORMException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function findBySearch(
        /* Search term. */
        string|array|null $search,

        /* Search filter. */
        int|null $distanceMeter = null,
        array|string|null $featureClass = null,
        array|string|null $featureCode = null,

        /* Filter configuration. */
        int|null $limit = Limit::LIMIT_10,
        string|null $isoLanguage = null,
        string|null $country = null,
        int $page = LocationService::PAGE_FIRST,

        /* Configuration */
        Coordinate|null $coordinate = null,

        /* Sort configuration */
        string $sortBy = LocationService::SORT_BY_RELEVANCE,
    ): array
    {
        $query = match(true) {
            $coordinate instanceof Coordinate => $this->queryBuilder->getQueryLocationSearch(
                search: $search,
                featureClass: $featureClass,
                featureCode: $featureCode,
                limit: $limit,
                isoLanguage: $isoLanguage,
                country: $country,
                page: $page,
                coordinate: $coordinate,
                distance: $distanceMeter,
                sortBy: $sortBy
            ),
            default => $this->queryBuilder->getQueryLocationSearch(
                search: $search,
                featureClass: $featureClass,
                featureCode: $featureCode,
                limit: $limit,
                isoLanguage: $isoLanguage,
                country: $country,
                page: $page,
                distance: $distanceMeter,
                sortBy: $sortBy,
            )
        };

        /* @var array<int, Location|array<int, mixed>> $results */
        $results = $query->getResult();

        /* @phpstan-ignore-next-line -> getResult will give array<int, Location|array<int, mixed>> */
        $locations = $this->resultProcessor->hydrateObjects($results);

        return $this->entityProcessor->reloadLocations($locations);
    }

    /**
     * Finds the locations from given search string.
     *
     * @param string|string[] $search
     * @param int|null $distanceMeter
     * @param array<int, string>|string|null $featureClass
     * @param array<int, string>|string|null $featureCode
     * @param string|null $isoLanguage
     * @param string|null $country
     * @param Coordinate|null $coordinate
     * @return int
     */
    public function countBySearch(
        /* Search */
        string|array|null $search,

        /* Search filter */
        int|null $distanceMeter = null,
        array|string|null $featureClass = null,
        array|string|null $featureCode = null,
        string|null $isoLanguage = LanguageCode::EN,
        string|null $country = null,

        /* Configuration */
        Coordinate|null $coordinate = null
    ): int
    {
        $query = match(true) {
            $coordinate instanceof Coordinate => $this->queryBuilder->getQueryCountLocationSearch(
                search: $search,
                featureClass: $featureClass,
                featureCode: $featureCode,
                isoLanguage: $isoLanguage,
                country: $country,
                coordinate: $coordinate,
                distance: $distanceMeter
            ),
            default => $this->queryBuilder->getQueryCountLocationSearch(
                search: $search,
                featureClass: $featureClass,
                featureCode: $featureCode,
                isoLanguage: $isoLanguage,
                country: $country,
                distance: $distanceMeter
            )
        };

        /* @var array<int, Location|array<int, mixed>> $results */
        $results = $query->getResult();

        if (!is_array($results)) {
            throw new LogicException('Unexpected result type.');
        }

        $firstResult = $results[0] ?? [];

        if (!is_array($firstResult)) {
            throw new LogicException('Unexpected result type.');
        }

        return $firstResult['count'] ?? 0;
    }

    /**
     * Finds the locations from given latitude and longitude ordered by distance.
     *
     * Query example:
     * --------------
     * SELECT
     * id,
     * name,
     * ST_Distance(coordinate, ST_MakePoint(13.741351013208588, 51.06115159751123)::geography) AS distance
     * FROM location
     * WHERE ST_DWithin(coordinate, ST_MakePoint(13.741351013208588, 51.06115159751123)::geography, 100000)
     * ORDER BY distance
     * LIMIT 50;
     *
     * Show indices:
     * -------------
     * SELECT
     * i.relname AS index_name,
     * am.amname AS index_type,
     * idx.indisprimary AS is_primary,
     * idx.indisunique AS is_unique,
     * pg_get_indexdef(idx.indexrelid) AS index_definition
     * FROM
     * pg_index AS idx
     * JOIN
     * pg_class AS i ON i.oid = idx.indexrelid
     * JOIN
     * pg_am AS am ON i.relam = am.oid
     * WHERE
     * idx.indrelid = 'location'::regclass
     * ORDER BY
     * index_name;
     *
     * @param Coordinate|null $coordinate
     * @param int|null $distanceMeter
     * @param array<int, string>|string|null $featureClasses
     * @param array<int, string>|string|null $featureCodes
     * @param int|null $limit
     * @param Country|null $country
     * @param array{a1?: string, a2?: string, a3?: string, a4?: string}|null $adminCodes
     * @param bool|null $withPopulation
     * @param bool $sortByFeatureClasses
     * @param bool $sortByFeatureCodes
     * @param bool $sortByPopulation
     * @return array<int, Location>
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function findLocationsByCoordinate(
        /* Search term. */
        Coordinate|null $coordinate = null,

        /* Search filter. */
        int|null $distanceMeter = null,
        array|string|null $featureClasses = null,
        array|string|null $featureCodes = null,

        /* Filter configuration. */
        int|null $limit = null,
        Country|null $country = null,

        /* Configuration */
        array|null $adminCodes = [],
        bool|null $withPopulation = null,

        /* Sort configuration */
        bool $sortByFeatureClasses = false,
        bool $sortByFeatureCodes = false,
        bool $sortByPopulation = false
    ): array
    {
        $featureContainer = new FeatureContainer($featureClasses, $featureCodes);

        /* Special search for rivers, streams, lakes, etc. */
        if ($featureContainer->isGroupRiverLake()) {
            return $this->findRiversAndLakes(
                coordinate: $coordinate,
                distanceMeter: $distanceMeter,
                country: $country,
                featureCodes: $featureCodes,
                limit: $limit,
                useLocationPart: !$featureContainer->isRiver()
            );
        }

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

        /* Limit the result by number of entities. */
        if (is_int($limit)) {
            $queryBuilder
                ->setMaxResults($limit)
            ;
        }

        $this->debugQuery($queryBuilder);

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
     * @throws ParserException
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
            limit: 1,
            country: $country,
            adminCodes: $adminCodes,
            withPopulation: $withPopulation,
            sortByFeatureClasses: $sortByFeatureClasses,
            sortByFeatureCodes: $sortByFeatureCodes,
            sortByPopulation: $sortByPopulation
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
     * @throws ParserException
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
     * @throws ParserException
     */
    public function findCapitals(): array
    {
        return $this->findLocationsByCoordinate(
            featureClasses: DbFeatureClass::P,
            featureCodes: DbFeatureCode::PPLC,
            limit: 300,
        );
    }

    /**
     * Returns all airports.
     *
     * @return Location[]
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @throws ParserException
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
     * @throws TypeInvalidException
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

        return $this->resultProcessor->hydrateObjects($result);
    }

    /**
     * Finds the first location.
     *
     * @param Location[] $locations
     * @param River $river
     * @return Location
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    private function getFirstLocation(
        array $locations,
        River $river
    ): Location
    {
        $distances = [];
        foreach ($locations as $location) {
            $coordinate = $river->getClosestCoordinateIxnode();

            if (is_null($coordinate)) {
                throw new LogicException(sprintf('Unable to find closest coordinate for river "%s".', $river->getName()));
            }

            $distance = $coordinate->getDistance($location->getCoordinateIxnode(), Coordinate::RETURN_KILOMETERS);
            $distances[$distance] = $location;
        }
        ksort($distances);

        return $distances[array_key_first($distances)];
    }

    /**
     * Find river and lake locations.
     *
     * @param Coordinate|null $coordinate
     * @param int|null $distanceMeter
     * @param Country|null $country
     * @param array<int, string>|string|null $featureCodes
     * @param int|null $limit
     * @param bool $useRiverPart
     * @param bool $useLocationPart
     * @return Location[]
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function findRiversAndLakes(
        Coordinate|null $coordinate,
        int|null $distanceMeter = null,
        Country|null $country = null,
        array|string|null $featureCodes = null,
        int|null $limit = null,
        bool $useRiverPart = true,
        bool $useLocationPart = false,
    ): array
    {
        $limitPrediction = is_null($limit) ? null : $limit * Prediction::LIMIT;

        $featureContainer = new FeatureContainer(
            featureClasses: null,
            featureCodes: $featureCodes
        );

        if (!$featureContainer->containsRiver()) {
            $useRiverPart = false;
        }

        $locations = [];

        if ($useRiverPart) {
            $locations = [...$locations, ...$this->doFindRiversAndLakesDirect(
                coordinate: $coordinate,
                distanceMeter: $distanceMeter,
                country: $country,
                limit: $limitPrediction,
            )];
        }

        if ($useLocationPart) {
            $locations = [...$locations, ...$this->doFindRiversAndLakesDirect(
                coordinate: $coordinate,
                distanceMeter: $distanceMeter,
                country: $country,
                featureCodes: $featureContainer->getFeatureCodesWithoutRiver(),
                limit: $limitPrediction,
                useRiverPart: false,
                useLocationPart: true
            )];
        }

        if (count($locations) === 0) {
            return $locations;
        }

        /* Sort locations by distance. */
        usort($locations, fn(Location $locationA, Location $locationB) =>
            $locationA->getClosestDistance() <=>
            $locationB->getClosestDistance()
        );

        if (is_int($limit)) {
            $locations = array_slice($locations, 0, $limit);
        }

        return $locations;
    }

    /**
     * Find river and lake locations.
     *
     * @param Coordinate|null $coordinate
     * @param int|null $distanceMeter
     * @param Country|null $country
     * @param array<int, string>|string|null $featureCodes
     * @param int|null $limit
     * @param bool $useRiverPart
     * @param bool $useLocationPart
     * @return Location[]
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function doFindRiversAndLakesDirect(
        Coordinate|null $coordinate,
        int|null $distanceMeter = null,
        Country|null $country = null,
        array|string|null $featureCodes = null,
        int|null $limit = null,
        bool $useRiverPart = true,
        bool $useLocationPart = false,
    ): array
    {
        if (!$useRiverPart && !$useLocationPart) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('l');

        $riverFeatureCodes = [DbFeatureCode::STM];
        $riverFeatureClasses = [DbFeatureClass::H];

        $riverFeatureCodesIds = $this->translateFeatureCodesToIds($riverFeatureCodes);
        $riverFeatureClassesIds = $this->translateFeatureClassesToIds($riverFeatureClasses);

        match (true) {
            $useRiverPart => $queryBuilder
                ->select([
                    'DISTINCT_ON(r.id) AS HIDDEN r_id',
                    'l'
                ])
                ->addOrderBy('r_id'),

            $useLocationPart => $queryBuilder
                ->select([
                    'DISTINCT_ON(l.id) AS HIDDEN l_id',
                    'l'
                ])
                ->addOrderBy('l_id')
        };

        if (is_null($coordinate) || is_null($distanceMeter)) {
            throw new LogicException('Not supported yet.');
        }

        /* Add left joins to rivers. */
        $queryBuilder
            /* Left join to rivers. */
            ->leftJoin('l.rivers', 'r')
            /* Left join to river LineString (entity RiverPart). */
            ->leftJoin(RiverPart::class, 'rp', 'WITH', 'r.id = rp.river')
        ;

        /* Only find locations that are mapped to river table. */
        $queryBuilder
            ->addSelect(sprintf(
                'ST_AsText(ST_ClosestPoint(rp.coordinates, ST_MakePoint(%f, %f))) AS closest_point',
                $coordinate->getLongitude(),
                $coordinate->getLatitude()
            ))
            ->addSelect(sprintf(
                'DistanceOperator(rp.coordinates, %f, %f) AS HIDDEN distance_river',
                $coordinate->getLatitude(),
                $coordinate->getLongitude()
            ))
            ->addSelect(sprintf(
                'DistanceOperator(l.coordinate, %f, %f) AS HIDDEN distance_location',
                $coordinate->getLatitude(),
                $coordinate->getLongitude()
            ))
        ;

        $queryBuilder
            ->where(
                $queryBuilder->expr()->orX(
                    $useRiverPart ? $queryBuilder->expr()->andX(
                        'r.id IS NOT NULL',
                        'ST_DWithin(rp.coordinates, ST_MakePoint(:longitude, :latitude), :distance, TRUE) = TRUE',
                    ) : $queryBuilder->expr()->andX(),
                    $useLocationPart ? $queryBuilder->expr()->andX(
                        'l.featureClass IN (:riverFeatureClasses)',
                        'l.featureCode NOT IN (:riverFeatureCodes)',
                        'r.id IS NULL',
                        'ST_DWithin(l.coordinate, ST_MakePoint(:longitude, :latitude), :distance, TRUE) = TRUE'
                    ) : $queryBuilder->expr()->andX()
                )
            )

            ->setParameter('latitude', $coordinate->getLatitude())
            ->setParameter('longitude', $coordinate->getLongitude())
            ->setParameter('distance', $distanceMeter)

            ->addOrderBy('distance_river', 'ASC')
        ;

        if ($useLocationPart) {
            $queryBuilder
                ->setParameter('riverFeatureClasses', $riverFeatureClassesIds)
                ->setParameter('riverFeatureCodes', $riverFeatureCodesIds)

                ->addOrderBy('distance_location', 'ASC')
            ;
        }

        /* Limit result by country. */
        if ($country instanceof Country) {
            $queryBuilder
                ->andWhere('l.country = :country')
                ->setParameter('country', $country);
        }

        if (!is_null($featureCodes)) {
            $featureCodesIds = $this->translateFeatureCodesToIds(is_string($featureCodes) ? [$featureCodes] : $featureCodes);

            $queryBuilder
                ->andWhere('l.featureCode IN (:featureCodes)')
                ->setParameter('featureCodes', $featureCodesIds)
            ;
        }

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

        $locations = $this->resultProcessor->hydrateObjects($result);

        foreach ($locations as $location) {
            $location->setClosestDistance($location->getCoordinateIxnode()->getDistance($coordinate));
        }

        /* Sort locations by distance. */
        usort($locations, fn(Location $locationA, Location $locationB) =>
            $locationA->getClosestDistance() <=>
            $locationB->getClosestDistance()
        );

        return $locations;
    }

    /**
     * Find river and lake locations (via rivers).
     *
     * @param Coordinate|null $coordinate
     * @param int|null $distanceMeter
     * @param Country|null $country
     * @param int|null $limit
     * @return Location[]
     * @throws CaseUnsupportedException
     * @throws ORMException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    protected function doFindRiversAndLakesViaRivers(
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
            limit: $limit,
            onlyMapped: true
        );

        $riverLocations = [];

        foreach ($rivers as $river) {
            /* Use doctrine proxy because findRivers returns "non-persistent" doctrine objects. */
            $riverProxy = $this->entityManager->getReference(River::class, $river->getId());

            if (is_null($riverProxy)) {
                throw new LogicException(sprintf('Could not find river with id "%s".', $river->getId()));
            }

            /** @var Location[] $locations */
            $locations = $riverProxy->getLocations()->toArray();

            /* This river does not exist within location table. */
            if (count($locations) <= 0) {
                continue;
            }

            $location = $this->getFirstLocation(
                $locations,
                $river
            );

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
}
