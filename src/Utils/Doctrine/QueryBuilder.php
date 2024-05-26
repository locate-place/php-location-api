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

use App\Constants\DB\Limit;
use App\Constants\DB\StopWord;
use App\Constants\Language\CountryCode;
use App\Constants\Language\LanguageCode;
use App\Constants\Query\Query;
use App\Entity\Location;
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query\ResultSetMapping;
use Ixnode\PhpCoordinate\Coordinate;
use LogicException;

/**
 * Class QueryBuilder
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-04-04)
 * @since 0.1.0 (2024-04-04) First version.
 */
readonly class QueryBuilder
{
    private const DISTANCE_NAME_FILTER = 28;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {
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
     * @return ResultSetMapping
     */
    private function getResultSetMapping(): ResultSetMapping
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

        return $rsm;
    }
}
