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

namespace App\Utils\Doctrine;

use App\Constants\DB\FeatureCode;
use App\Constants\DB\Limit;
use App\Constants\DB\StopWord;
use App\Constants\Language\CountryCode;
use App\Constants\Language\LanguageCode;
use App\Constants\Place\AdminType;
use App\Constants\Query\Query;
use App\Constants\Query\QueryAdmin;
use App\Entity\Location;
use App\Repository\LocationRepository;
use App\Service\LocationService;
use App\Service\LocationServiceConfig;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query\ResultSetMapping;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use LogicException;

/**
 * Class QueryBuilder
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-04-04)
 * @since 0.1.0 (2024-04-04) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
readonly class QueryBuilder
{
    private const DISTANCE_NAME_FILTER = 28;

    private const DISTANCE_ADMIN_CODES = 20000;

    private const DISTANCE_WHEN_IDS = 36;

    private const DISTANCE_WHEN_STRINGS = 20;

    private const INDEX_NO_SORT = 1;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LocationRepository $locationRepository
     * @param LocationServiceConfig $locationServiceConfig
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LocationRepository $locationRepository,
        private LocationServiceConfig $locationServiceConfig
    )
    {
    }

    /**
     * Returns the native query for finding the administrative areas.
     *
     * @param Location $location
     * @param Coordinate $coordinate
     * @return NativeQuery
     * @throws CaseUnsupportedException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.LongVariable)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getAdminQuery(
        Location $location,
        Coordinate $coordinate
    ): NativeQuery
    {
        /* Get feature codes. */
        $featureCodeAdmin2 = [FeatureCode::ADM2];
        $featureCodeAdmin3 = [FeatureCode::ADM3];
        $featureCodeAdmin4 = [FeatureCode::ADM4]; /* Admin4Code for cities. */
        $featureCodeAdmin5 = [FeatureCode::ADM5]; /* Admin4Code for districts. */
        $featureCodeCities = $this->locationServiceConfig->getCityFeatureCodes($location);
        $featureCodeDistricts = $this->locationServiceConfig->getDistrictFeatureCodes($location);

        $countryId = $location->getCountry()?->getId() ?? null;

        if (is_null($countryId)) {
            throw new LogicException('Country is not set.');
        }

        /* Get sort by feature codes. */
        $citySortByFeatureCodes = $this->locationServiceConfig->isCitySortByFeatureCodes($location);
        $districtSortByFeatureCodes = $this->locationServiceConfig->isDistrictSortByFeatureCodes($location);

        /* Get sort by population. */
        $citySortByPopulation = $this->locationServiceConfig->isCitySortByPopulation($location);
        $districtSortByPopulation = $this->locationServiceConfig->isDistrictSortByPopulation($location);

        /* Translate the feature codes to ids. */
        $featureCodeIdsAdmin2 = $this->locationRepository->translateFeatureCodesToIds($featureCodeAdmin2);
        $featureCodeIdsAdmin3 = $this->locationRepository->translateFeatureCodesToIds($featureCodeAdmin3);
        $featureCodeIdsAdmin4 = $this->locationRepository->translateFeatureCodesToIds($featureCodeAdmin4);
        $featureCodeIdsAdmin5 = $this->locationRepository->translateFeatureCodesToIds($featureCodeAdmin5);
        $featureCodeIdsCities = $this->locationRepository->translateFeatureCodesToIds($featureCodeCities);
        $featureCodeIdsDistricts = $this->locationRepository->translateFeatureCodesToIds($featureCodeDistricts);

        /* Calculate the intersected ids. */
        $featureCodeIdsCitiesDistricts = array_values(array_intersect($featureCodeIdsCities, $featureCodeIdsDistricts));

        /* Remove intersected ids. */
        $featureCodeIdsCities = array_values(array_diff($featureCodeIdsCities, $featureCodeIdsCitiesDistricts));
        $featureCodeIdsDistricts = array_values(array_diff($featureCodeIdsDistricts, $featureCodeIdsCitiesDistricts));

        /* Set non-existing id. */
        if (count($featureCodeIdsAdmin2) <= 0) {
            $featureCodeIdsAdmin2 = [9999];
        }
        if (count($featureCodeIdsAdmin3) <= 0) {
            $featureCodeIdsAdmin3 = [9999];
        }
        if (count($featureCodeIdsAdmin4) <= 0) {
            $featureCodeIdsAdmin4 = [9999];
        }
        if (count($featureCodeIdsAdmin5) <= 0) {
            $featureCodeIdsAdmin5 = [9999];
        }
        if (count($featureCodeIdsCities) <= 0) {
            $featureCodeIdsCities = [9999];
        }
        if (count($featureCodeIdsDistricts) <= 0) {
            $featureCodeIdsDistricts = [9999];
        }
        if (count($featureCodeIdsCitiesDistricts) <= 0) {
            $featureCodeIdsCitiesDistricts = [9999];
        }

        /* When cases with ids. */
        $featureCodeIdsAdmin2When = $this->buildWhen($featureCodeIdsAdmin2);
        $featureCodeIdsAdmin3When = $this->buildWhen($featureCodeIdsAdmin3);
        $featureCodeIdsAdmin4When = $this->buildWhen($featureCodeIdsAdmin4);
        $featureCodeIdsAdmin5When = $this->buildWhen($featureCodeIdsAdmin5);

        $featureCodeIdsCitiesWhen = $this->buildWhen($featureCodeIdsCities, $citySortByFeatureCodes);
        $featureCodeIdsDistrictsWhen = $this->buildWhen($featureCodeIdsDistricts, $districtSortByFeatureCodes);
        $featureCodeIdsCitiesDistrictsWhen = $this->buildWhen($featureCodeIdsCitiesDistricts, $citySortByFeatureCodes);

        /* When cases with strings. */
        $featureCodeCitiesWhen = $this->buildWhen($featureCodeCities, $citySortByFeatureCodes, self::DISTANCE_WHEN_STRINGS);
        $featureCodeDistrictsWhen = $this->buildWhen($featureCodeDistricts, $districtSortByFeatureCodes, self::DISTANCE_WHEN_STRINGS);

        $rsm = $this->getResultSetMapping(true);

        $sql = QueryAdmin::ADMIN;

        [
            AdminType::A1 => $admin1Code,
            AdminType::A2 => $admin2Code,
            AdminType::A3 => $admin3Code,
            AdminType::A4 => $admin4Code,
        ] = $this->locationServiceConfig->getAdminCodesMatch($location);

        $admin1CodeMatch = match (true) {
            is_null($admin1Code) => 'IS NULL',
            $admin1Code === false => 'IS NOT DISTINCT FROM ac.admin1_code',
            default => sprintf('= \'%s\'', $admin1Code),
        };
        $admin2CodeMatch = match (true) {
            is_null($admin2Code) => 'IS NULL',
            $admin2Code === false => 'IS NOT DISTINCT FROM ac.admin2_code',
            default => sprintf('= \'%s\'', $admin2Code),
        };
        $admin3CodeMatch = match (true) {
            is_null($admin3Code) => 'IS NULL',
            $admin3Code === false => 'IS NOT DISTINCT FROM ac.admin3_code',
            default => sprintf('= \'%s\'', $admin3Code),
        };
        $admin4CodeMatch = match (true) {
            is_null($admin4Code) => 'IS NULL',
            $admin4Code === false => 'IS NOT DISTINCT FROM ac.admin4_code',
            default => sprintf('= \'%s\'', $admin4Code),
        };

        $admin1CodeMatchNot = match (true) {
            $admin1Code === false => 'IS NOT DISTINCT FROM ac.admin1_code',
            default => 'IS NULL',
        };
        $admin2CodeMatchNot = match (true) {
            $admin2Code === false => 'IS NOT DISTINCT FROM ac.admin2_code',
            default => 'IS NULL',
        };
        $admin3CodeMatchNot = match (true) {
            $admin3Code === false => 'IS NOT DISTINCT FROM ac.admin3_code',
            default => 'IS NULL',
        };
        $admin4CodeMatchNot = match (true) {
            $admin4Code === false => 'IS NOT DISTINCT FROM ac.admin4_code',
            default => 'IS NULL',
        };

        $sql = str_replace('%(longitude)s', (string) $coordinate->getLongitude(), $sql);
        $sql = str_replace('%(latitude)s', (string) $coordinate->getLatitude(), $sql);
        $sql = str_replace('%(admin1_code)s', $admin1CodeMatch, $sql);
        $sql = str_replace('%(admin2_code)s', $admin2CodeMatch, $sql);
        $sql = str_replace('%(admin3_code)s', $admin3CodeMatch, $sql);
        $sql = str_replace('%(admin4_code)s', $admin4CodeMatch, $sql);
        $sql = str_replace('%(admin1_code_not)s', $admin1CodeMatchNot, $sql);
        $sql = str_replace('%(admin2_code_not)s', $admin2CodeMatchNot, $sql);
        $sql = str_replace('%(admin3_code_not)s', $admin3CodeMatchNot, $sql);
        $sql = str_replace('%(admin4_code_not)s', $admin4CodeMatchNot, $sql);
        $sql = str_replace('%(feature_code_admin2)s', implode(',', $featureCodeIdsAdmin2), $sql);
        $sql = str_replace('%(feature_code_admin3)s', implode(',', $featureCodeIdsAdmin3), $sql);
        $sql = str_replace('%(feature_code_admin4)s', implode(',', $featureCodeIdsAdmin4), $sql);
        $sql = str_replace('%(feature_code_admin5)s', implode(',', $featureCodeIdsAdmin5), $sql);
        $sql = str_replace('%(feature_code_cities)s', implode(',', $featureCodeIdsCities), $sql);
        $sql = str_replace('%(feature_code_districts)s', implode(',', $featureCodeIdsDistricts), $sql);
        $sql = str_replace('%(feature_code_cities_districts)s', implode(',', $featureCodeIdsCitiesDistricts), $sql);
        $sql = str_replace('%(feature_code_admin2_when)s', $featureCodeIdsAdmin2When, $sql);
        $sql = str_replace('%(feature_code_admin3_when)s', $featureCodeIdsAdmin3When, $sql);
        $sql = str_replace('%(feature_code_admin4_when)s', $featureCodeIdsAdmin4When, $sql);
        $sql = str_replace('%(feature_code_admin5_when)s', $featureCodeIdsAdmin5When, $sql);
        $sql = str_replace('%(feature_code_cities_when)s', $featureCodeIdsCitiesWhen, $sql);
        $sql = str_replace('%(feature_code_districts_when)s', $featureCodeIdsDistrictsWhen, $sql);
        $sql = str_replace('%(feature_code_cities_districts_when)s', $featureCodeIdsCitiesDistrictsWhen, $sql);
        $sql = str_replace('%(feature_code_cities_case_when)s', $featureCodeCitiesWhen, $sql);
        $sql = str_replace('%(feature_code_districts_case_when)s', $featureCodeDistrictsWhen, $sql);
        $sql = str_replace('%(sort_by_population_cities)s', $citySortByPopulation ? 'l.population' : 'NULL', $sql);
        $sql = str_replace('%(sort_by_population_districts)s', $districtSortByPopulation ? 'l.population' : 'NULL', $sql);
        $sql = str_replace('%(sort_by_population_cities_districts)s', $districtSortByPopulation ? 'l.population' : 'NULL', $sql);

        return ($this->entityManager->createNativeQuery($sql, $rsm))
            ->setParameter('distance', self::DISTANCE_ADMIN_CODES)
            ->setParameter('country_id', $countryId)
        ;
    }

    /**
     * Builds when condition for admin code search.
     *
     * @param int[]|string[] $featureCodes
     * @param bool $sortBy
     * @param int $distance
     * @return string
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function buildWhen(
        array $featureCodes,
        bool $sortBy = true,
        int $distance = self::DISTANCE_WHEN_IDS
    ): string
    {
        $when = [];
        foreach ($featureCodes as $index => $featureCode) {
            $nextIndex = $sortBy ? $index + 1 : self::INDEX_NO_SORT;

            $when[] = match(true) {
                is_int($featureCode) => sprintf('WHEN %d THEN %d', $featureCode, $nextIndex),
                is_string($featureCode) => sprintf('WHEN \'%s\' THEN %d', $featureCode, $nextIndex),
            };
        }

        return implode(PHP_EOL.str_repeat(' ', $distance), $when);
    }

    /**
     * Returns the native query for location "find by search".
     *
     * @param string|string[] $search
     * @param array<int, string>|string|null $featureClass
     * @param array<int, string>|string|null $featureCode
     * @param int|null $limit
     * @param string|null $isoLanguage
     * @param string|null $country
     * @param int $page
     * @param Coordinate|null $coordinate
     * @param int|null $distance
     * @param string $sortBy
     * @return NativeQuery
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function getQueryLocationSearch(
        /* Search */
        string|array|null $search,

        /* Search filter */
        array|string|null $featureClass = null,
        array|string|null $featureCode = null,
        int|null $limit = Limit::LIMIT_10,
        string|null $isoLanguage = LanguageCode::EN,
        string|null $country = CountryCode::US,
        int $page = LocationService::PAGE_FIRST,

        /* Configuration */
        Coordinate|null $coordinate = null,
        int|null $distance = null,

        /* Sort configuration */
        string $sortBy = LocationService::SORT_BY_RELEVANCE,
    ): NativeQuery
    {
        $search = $this->removeStopWords($search, $isoLanguage);

        $nameSearch = $this->getNameSearch($this->getSearchFilter($search));
        $nameFilter = $this->getNameFilter($search, $isoLanguage);
        $search = $this->getSearch($search);

        $longitude = is_null($coordinate) ? null : $coordinate->getLongitude();
        $latitude = is_null($coordinate) ? null : $coordinate->getLatitude();

        $sortBy = $this->getSortBy($sortBy);

        $rsm = $this->getResultSetMapping();

        /* Additional fields */
        if (!is_null($coordinate)) {
            $rsm
                ->addScalarResult('closest_distance', 'closestDistance')
                ->addScalarResult('closest_point', 'closestPoint')
            ;
        }

        $rsm
            ->addScalarResult('relevance_score', 'relevanceScore')
        ;

        $sql = match (true) {
            $coordinate instanceof Coordinate && !is_null($distance) => Query::SEARCH_COORDINATE_DISTANCE,
            $coordinate instanceof Coordinate => Query::SEARCH_COORDINATE,
            default => Query::SEARCH,
        };

        $sql = str_replace('%(sort_by)s', $sortBy, $sql);
        $sql = str_replace('%(limit)s', $this->getLimit($limit, $page), $sql);
        $sql = str_replace('%(feature_code)s', $this->getFeatureCodeLeftJoin($featureCode), $sql);
        $sql = str_replace('%(feature_class)s', $this->getFeatureClassLeftJoin($featureClass), $sql);
        $sql = str_replace('%(country)s', is_null($country) ? '' : 'AND c.code=\''.$country.'\'', $sql);
        $sql = str_replace('%(name_search)s', $nameSearch, $sql);
        $sql = str_replace('%(name_filter)s', $nameFilter, $sql);

        return ($this->entityManager->createNativeQuery($sql, $rsm))
            ->setParameter('longitude', $longitude)
            ->setParameter('latitude', $latitude)
            ->setParameter('search', $search)
            ->setParameter('distance', $distance);
    }

    /**
     * Returns the native query for count for location "find by search".
     *
     * @param string|string[] $search
     * @param array<int, string>|string|null $featureClass
     * @param array<int, string>|string|null $featureCode
     * @param string|null $isoLanguage
     * @param string|null $country
     * @param Coordinate|null $coordinate
     * @param int|null $distance
     * @return NativeQuery
     */
    public function getQueryCountLocationSearch(
        /* Search */
        string|array|null $search,

        /* Search filter */
        array|string|null $featureClass = null,
        array|string|null $featureCode = null,
        string|null $isoLanguage = LanguageCode::EN,
        string|null $country = CountryCode::US,

        /* Configuration */
        Coordinate|null $coordinate = null,
        int|null $distance = null,
    ): NativeQuery
    {
        $search = $this->removeStopWords($search, $isoLanguage);

        $nameSearch = $this->getNameSearch($this->getSearchFilter($search));
        $nameFilter = $this->getNameFilter($search, $isoLanguage);
        $search = $this->getSearch($search);

        $longitude = is_null($coordinate) ? null : $coordinate->getLongitude();
        $latitude = is_null($coordinate) ? null : $coordinate->getLatitude();

        $rsm = new ResultSetMapping();

        $rsm
            ->addScalarResult('count', 'count')
        ;

        $sql = match (true) {
            $coordinate instanceof Coordinate && !is_null($distance) => Query::SEARCH_COORDINATE_DISTANCE_COUNT,
            $coordinate instanceof Coordinate => Query::SEARCH_COORDINATE_COUNT,
            default => Query::SEARCH_COUNT,
        };

        $sql = str_replace('%(feature_code)s', $this->getFeatureCodeLeftJoin($featureCode), $sql);
        $sql = str_replace('%(feature_class)s', $this->getFeatureClassLeftJoin($featureClass), $sql);
        $sql = str_replace('%(country)s', is_null($country) ? '' : 'AND c.code=\''.$country.'\'', $sql);
        $sql = str_replace('%(name_search)s', $nameSearch, $sql);
        $sql = str_replace('%(name_filter)s', $nameFilter, $sql);

        return ($this->entityManager->createNativeQuery($sql, $rsm))
            ->setParameter('longitude', $longitude)
            ->setParameter('latitude', $latitude)
            ->setParameter('search', $search)
            ->setParameter('distance', $distance)
        ;
    }

    /**
     * Returns the native query for location "find by location ids".
     *
     * @param int[] $locationIds
     * @param int|null $limit
     * @param int $page
     * @param Coordinate|null $coordinate
     * @param string $sortBy
     * @return NativeQuery
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getQueryLocationIds(
        /* Search */
        array $locationIds,

        /* Search filter */
        int|null $limit = Limit::LIMIT_10,
        int $page = LocationService::PAGE_FIRST,

        /* Configuration */
        Coordinate|null $coordinate = null,

        /* Sort configuration */
        string $sortBy = LocationService::SORT_BY_RELEVANCE,
    ): NativeQuery
    {
        $longitude = is_null($coordinate) ? null : $coordinate->getLongitude();
        $latitude = is_null($coordinate) ? null : $coordinate->getLatitude();

        $sortBy = $this->getSortBy($sortBy);

        $rsm = $this->getResultSetMapping();

        /* Additional fields */
        if (!is_null($coordinate)) {
            $rsm
                ->addScalarResult('closest_distance', 'closestDistance')
                ->addScalarResult('closest_point', 'closestPoint')
            ;
        }

        $rsm
            ->addScalarResult('relevance_score', 'relevanceScore')
        ;

        $sql = match (true) {
            $coordinate instanceof Coordinate => Query::SEARCH_GEONAME_ID_COORDINATE,
            default => Query::SEARCH_GEONAME_ID,
        };

        $sql = str_replace('%(sort_by)s', $sortBy, $sql);
        $sql = str_replace('%(limit)s', $this->getLimit($limit, $page), $sql);
        $sql = str_replace('%(location_ids)s', implode(',', $locationIds), $sql);

        return ($this->entityManager->createNativeQuery($sql, $rsm))
            ->setParameter('longitude', $longitude)
            ->setParameter('latitude', $latitude);
    }

    /**
     * Returns the limit query part.
     *
     * @param int|null $limit
     * @param int $page
     * @return string
     */
    private function getLimit(
        int|null $limit = null,
        int $page = LocationService::PAGE_FIRST,
    ): string
    {
        if (is_null($limit)) {
            return '';
        }

        if ($page === LocationService::PAGE_FIRST) {
            return sprintf('LIMIT %d', $limit);
        }

        return sprintf('LIMIT %d OFFSET %d', $limit, ($page - 1) * $limit);
    }

    /**
     * Returns the feature code left join query part.
     *
     * @param array<int, string>|string|null $featureCode
     * @return string
     */
    private function getFeatureCodeLeftJoin(
        array|string|null $featureCode = null,
    ): string
    {
        $defaultLeftJoin = 'RIGHT JOIN feature_code fco ON fco.id = l.feature_code_id AND fco.code != \'\'';

        if (is_null($featureCode)) {
            return $defaultLeftJoin;
        }

        if (is_string($featureCode)) {
            $featureCode = [$featureCode];
        }

        if (count($featureCode) <= 0) {
            return $defaultLeftJoin;
        }

        return sprintf(
            'RIGHT JOIN feature_code fco ON fco.id = l.feature_code_id AND fco.code IN (\'%s\')',
            implode('\', \'', $featureCode)
        );
    }

    /**
     * Returns the feature class left join query part.
     *
     * @param array<int, string>|string|null $featureClass
     * @return string
     */
    private function getFeatureClassLeftJoin(
        array|string|null $featureClass = null,
    ): string
    {
        $defaultLeftJoin = 'RIGHT JOIN feature_class fcl ON fcl.id = l.feature_class_id AND fcl.class NOT IN (\'\', \'A\')';

        if (is_null($featureClass)) {
            return $defaultLeftJoin;
        }

        if (is_string($featureClass)) {
            $featureClass = [$featureClass];
        }

        if (count($featureClass) <= 0) {
            return $defaultLeftJoin;
        }

        return sprintf(
            'RIGHT JOIN feature_class fcl ON fcl.id = l.feature_class_id AND fcl.class IN (\'%s\')',
            implode('\', \'', $featureClass)
        );
    }

    /**
     * Returns the search filter.
     *
     * @param string[]|null $search
     * @return string
     */
    private function getNameSearch(array|null $search): string
    {
        if (is_null($search)) {
            return '';
        }

        $filter = [];

        foreach ($search as $term) {
            $filter[] = sprintf(
                'AND (UNACCENT(LOWER(l.name)) LIKE UNACCENT(LOWER(\'%s\')) ESCAPE \'!\' OR UNACCENT(LOWER(an.alternate_name)) LIKE UNACCENT(LOWER(\'%s\')) ESCAPE \'!\')',
                $term,
                $term
            );
        }

        return implode('', $filter);
    }

    /**
     * Returns the query filter.
     *
     * @param string[]|null $search
     * @return string[]|null
     */
    private function getSearchFilter(array|null $search): array|null
    {
        if (is_null($search)) {
            return null;
        }

        $searches = [];
        foreach ($search as $term) {
            $searches[] = sprintf('%%%s%%', $term);
        }

        return $searches;
    }

    /**
     * Returns the query for search (to_tsquery).
     *
     * @param string[]|null $search
     * @return string
     */
    private function getSearch(array|null $search = null): string
    {
        return match (true) {
            is_null($search), count($search) <= 0 => '',
            default => $search[0].Query::SEARCH_RIGHT_WILDCARD,
        };
    }

    /**
     * Returns the query for name_filter.
     *
     * @param string[]|null $search
     * @param string|null $isoLanguage
     * @return string
     */
    private function getNameFilter(array|null $search, string|null $isoLanguage): string
    {
        if (is_null($search) || count($search) <= 0) {
            $search = [''];
        }

        $isoLanguages = match (true) {
            is_null($isoLanguage) => ['simple'],
            default => [$isoLanguage],
        };

        $searches = [];
        foreach ($isoLanguages as $isoLanguageTerm) {
            $language = match ($isoLanguageTerm) {
                LanguageCode::DE => 'german',
                LanguageCode::EN => 'english',
                LanguageCode::ES => 'spanish',
                default => $isoLanguageTerm,
            };

            $languageSearch = [];
            foreach ($search as $searchTerm) {
                $languageSearch[] = sprintf(
                    'si.search_text_%s @@ to_tsquery(\'%s\', unaccent(\'%s%s\'))',
                    $isoLanguageTerm,
                    $language,
                    $searchTerm,
                    Query::SEARCH_RIGHT_WILDCARD
                );
            }

            $searches[] = sprintf('(%s)', implode(' AND ', $languageSearch));
        }

        return implode(' OR '.PHP_EOL.str_repeat(' ', self::DISTANCE_NAME_FILTER), $searches);
    }

    /**
     * @param string|string[]|null $search
     * @return string[]|null
     */
    private function removeStopWords(string|array|null $search, string|null $isoLanguage): null|array
    {
        if (is_null($search)) {
            return null;
        }

        if (is_string($search)) {
            $search = [$search];
        }

        if (is_null($isoLanguage)) {
            return $search;
        }

        $stopWords = match ($isoLanguage) {
            LanguageCode::DE => StopWord::DE,
            LanguageCode::EN => StopWord::EN,
            LanguageCode::ES => StopWord::ES,
            default => [],
        };

        $result = array_diff($search, $stopWords);

        $result = array_unique($result);

        return array_values($result);
    }

    /**
     * Returns the translated sql sort by string.
     *
     * @param string $sortBy
     * @return string
     */
    private function getSortBy(string $sortBy): string
    {
        return match ($sortBy) {
            LocationService::SORT_BY_DISTANCE,
            LocationService::SORT_BY_DISTANCE_USER => 'closest_distance ASC',

            LocationService::SORT_BY_RELEVANCE,
            LocationService::SORT_BY_RELEVANCE_USER => 'relevance_score DESC',

            LocationService::SORT_BY_NAME => 'name ASC',
            LocationService::SORT_BY_GEONAME_ID => 'geoname_id ASC',

            default => throw new LogicException(sprintf('Invalid sort by "%s".', $sortBy)),
        };
    }

    /**
     * Returns the result set mapping.
     *
     * @param bool $withLocationType
     * @return ResultSetMapping
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function getResultSetMapping(bool $withLocationType = false): ResultSetMapping
    {
        $rsm = new ResultSetMapping();

        /* location fields */
        $rsm
            ->addEntityResult(Location::class, 'l')
            ->addFieldResult('l', 'id', 'id')
            ->addFieldResult('l', 'geoname_id', 'geonameId')
            ->addFieldResult('l', 'name', 'name')
            ->addFieldResult('l', 'ascii_name', 'asciiName')
            ->addFieldResult('l', 'coordinate', 'coordinate')
            ->addFieldResult('l', 'cc2', 'cc2')
            ->addFieldResult('l', 'population', 'population')
            ->addFieldResult('l', 'elevation', 'elevation')
            ->addFieldResult('l', 'dem', 'dem')
            ->addFieldResult('l', 'modification_date', 'modificationDate')
            ->addFieldResult('l', 'source', 'source')
            ->addFieldResult('l', 'mapping_river_ignore', 'mappingRiverIgnore')
            ->addFieldResult('l', 'mapping_river_similarity', 'mappingRiverSimilarity')
            ->addFieldResult('l', 'mapping_river_distance', 'mappingRiverDistance')
            ->addFieldResult('l', 'created_at', 'createdAt')
            ->addFieldResult('l', 'updated_at', 'updatedAt')
        ;

        if ($withLocationType) {
            $rsm->addScalarResult('location_type', 'locationType');
            $rsm->addScalarResult('rank_city', 'rankCity');
            $rsm->addScalarResult('rank_district', 'rankDistrict');
            $rsm->addScalarResult('distance_meters', 'distanceMeters');
        }

        return $rsm;
    }
}
