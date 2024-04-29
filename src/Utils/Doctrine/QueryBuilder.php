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
use App\Constants\Language\CountryCode;
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
     * @param string|null $country
     * @param int $page
     * @param Coordinate|null $coordinate
     * @param int|null $distance
     * @param string $sortBy
     * @return NativeQuery
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getQueryLocationSearch(
        /* Search */
        string|array|null $search,

        /* Search filter */
        array|string|null $featureClass = null,
        array|string|null $featureCode = null,
        int|null $limit = Limit::LIMIT_10,
        string|null $country = CountryCode::US,
        int $page = LocationService::PAGE_FIRST,

        /* Configuration */
        Coordinate|null $coordinate = null,
        int|null $distance = null,

        /* Sort configuration */
        string $sortBy = LocationService::SORT_BY_RELEVANCE,
    ): NativeQuery
    {
        if (is_string($search)) {
            $search = [$search];
        }

        $searchFilter = $this->getSearchFilter($search);
        $search = $this->getSearch($search);

        $longitude = is_null($coordinate) ? null : $coordinate->getLongitude();
        $latitude = is_null($coordinate) ? null : $coordinate->getLatitude();

        $sortBy = match ($sortBy) {
            LocationService::SORT_BY_DISTANCE,
            LocationService::SORT_BY_DISTANCE_USER => 'closest_distance ASC',

            LocationService::SORT_BY_RELEVANCE,
            LocationService::SORT_BY_RELEVANCE_USER => 'relevance_score DESC',

            LocationService::SORT_BY_NAME => 'l.name ASC',
            LocationService::SORT_BY_GEONAME_ID => 'l.geoname_id ASC',

            default => throw new LogicException(sprintf('Invalid sort by "%s".', $sortBy)),
        };

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
        $sql = str_replace('%(name_search)s', $this->getContainFilter($searchFilter), $sql);

        return ($this->entityManager->createNativeQuery($sql, $rsm))
            ->setParameter('longitude', $longitude)
            ->setParameter('latitude', $latitude)
            ->setParameter('search', $search)
            ->setParameter('distance', $distance)
        ;
    }

    /**
     * Returns the native query for count for location "find by search".
     *
     * @param string|string[] $search
     * @param array<int, string>|string|null $featureClass
     * @param array<int, string>|string|null $featureCode
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
        string|null $country = CountryCode::US,

        /* Configuration */
        Coordinate|null $coordinate = null,
        int|null $distance = null,
    ): NativeQuery
    {
        if (is_string($search)) {
            $search = [$search];
        }

        $searchFilter = $this->getSearchFilter($search);
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
        $sql = str_replace('%(name_search)s', $this->getContainFilter($searchFilter), $sql);

        return ($this->entityManager->createNativeQuery($sql, $rsm))
            ->setParameter('longitude', $longitude)
            ->setParameter('latitude', $latitude)
            ->setParameter('search', $search)
            ->setParameter('distance', $distance)
        ;
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
    private function getContainFilter(array|null $search): string
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
    private function getSearchFilter(array|null $search = null): array|null
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
}
