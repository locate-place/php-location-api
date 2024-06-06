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

namespace App\Constants\Query;

/**
 * Class Query
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-04-07)
 * @since 0.1.0 (2024-04-07) First version.
 */
class Query
{
    final public const SEARCH_AND = ' & ';

    final public const SEARCH_RIGHT_WILDCARD = ':*';

    final public const SEARCH = <<<SQL
        SELECT *
        FROM (
            SELECT DISTINCT ON (l.id)
                l.*,
                ST_AsEWKT(l.coordinate) as coordinate,
                si.relevance_score AS relevance_score
            FROM
                location l
            -- INNER: Only show locations with search index
            INNER JOIN search_index si ON si.location_id = l.id
            -- INNER: Only show locations with country
            INNER JOIN country c ON l.country_id = c.id
            -- LEFT: Also show locations without alternate names; Used for %(name_search)s
            --LEFT JOIN alternate_name an ON l.id = an.location_id AND (an.iso_language IN ('de') OR an.iso_language IS NULL)
            %(feature_code)s
            %(feature_class)s
            WHERE
                CASE
                    WHEN
                        :search = ''
                    THEN
                        si.id IS NOT NULL
                    ELSE
                        (
                            %(name_filter)s
                        )
                END
                --%(name_search)s
                %(country)s
            ORDER BY
                l.id,
                %(sort_by)s
        ) sub
        ORDER BY
            %(sort_by)s
        %(limit)s;
SQL;

    final public const SEARCH_COUNT = <<<SQL
        SELECT
            COUNT(DISTINCT l.id) AS count
        FROM
            location l
        -- INNER: Only show locations with search index
        INNER JOIN search_index si ON si.location_id = l.id
        -- INNER: Only show locations with country
        INNER JOIN country c ON l.country_id = c.id
        -- LEFT: Also show locations without alternate names; Used for %(name_search)s
        --LEFT JOIN alternate_name an ON l.id = an.location_id AND (an.iso_language IN ('de') OR an.iso_language IS NULL)
        %(feature_code)s
        %(feature_class)s
        WHERE
            CASE
                WHEN
                    :search = ''
                THEN
                    si.id IS NOT NULL
                ELSE
                    (
                        %(name_filter)s
                    )
            END
            --%(name_search)s
            %(country)s
        ;
SQL;

    final public const SEARCH_COORDINATE = <<<SQL
        SELECT *
        FROM (
            SELECT DISTINCT ON (l.id)
                l.*,
                ST_AsEWKT(l.coordinate) as coordinate,
                CAST(si.relevance_score - COALESCE(rp.closest_distance_river, ST_Distance(l.coordinate, ST_MakePoint(:longitude, :latitude)::geography)) * 0.01 AS INTEGER) AS relevance_score,
                CASE 
                    WHEN rp.closest_distance_river IS NOT NULL THEN rp.closest_distance_river
                    ELSE ST_Distance(l.coordinate, ST_MakePoint(:longitude, :latitude)::geography)
                END AS closest_distance,
                ST_AsText(
                    CASE 
                        WHEN rp.closest_point_river IS NOT NULL THEN rp.closest_point_river
                        ELSE l.coordinate
                    END
                ) AS closest_point
            FROM
                location l
            -- INNER: Only show locations with search index
            INNER JOIN search_index si ON si.location_id = l.id
            -- LEFT: Also show locations without alternate names; Used for %(name_search)s
            --LEFT JOIN alternate_name an ON l.id = an.location_id AND (an.iso_language IN ('de') OR an.iso_language IS NULL)
            %(feature_code)s
            %(feature_class)s
            LEFT JOIN location_river lr ON lr.location_id = l.id
            LEFT JOIN river r ON lr.river_id = r.id
            -- INNER: Only show locations with country
            INNER JOIN country c ON l.country_id = c.id
            LEFT JOIN LATERAL (
                SELECT
                    rp.coordinates as coordinates_river,
                    ST_Distance(
                        rp.coordinates,
                        ST_MakePoint(:longitude, :latitude)::geography
                    ) AS closest_distance_river,
                    ST_AsText(ST_EndPoint(ST_ShortestLine(
                        rp.coordinates::geography,
                        ST_MakePoint(:longitude, :latitude)::geography
                    )::geometry))::geography AS closest_point_river
                FROM
                    river_part rp
                WHERE
                    rp.river_id = r.id
                ORDER BY
                    closest_distance_river ASC
                LIMIT 1
            ) rp ON TRUE
            WHERE
                CASE
                    WHEN
                        :search = ''
                    THEN
                        si.id IS NOT NULL
                    ELSE
                        (
                            %(name_filter)s
                        )
                END
                --%(name_search)s
                %(country)s
            ORDER BY
                l.id,
                %(sort_by)s
        ) sub
        ORDER BY
            %(sort_by)s
        %(limit)s;
SQL;

    final public const SEARCH_COORDINATE_COUNT = <<<SQL
        SELECT
            COUNT(DISTINCT l.id) AS count
        FROM
            location l
        -- INNER: Only show locations with search index
        INNER JOIN search_index si ON si.location_id = l.id
        -- INNER: Only show locations with country
        INNER JOIN country c ON l.country_id = c.id
        -- LEFT: Also show locations without alternate names; Used for %(name_search)s
        --LEFT JOIN alternate_name an ON l.id = an.location_id AND (an.iso_language IN ('de') OR an.iso_language IS NULL)
        %(feature_code)s
        %(feature_class)s
        LEFT JOIN location_river lr ON lr.location_id = l.id
        LEFT JOIN river r ON lr.river_id = r.id
        LEFT JOIN LATERAL (
            SELECT
                rp.coordinates as coordinates_river,
                ST_Distance(
                    rp.coordinates,
                    ST_MakePoint(:longitude, :latitude)::geography
                ) AS closest_distance_river,
                ST_AsText(ST_EndPoint(ST_ShortestLine(
                    rp.coordinates::geography,
                    ST_MakePoint(:longitude, :latitude)::geography
                )::geometry))::geography AS closest_point_river
            FROM
                river_part rp
            WHERE
                rp.river_id = r.id
            ORDER BY
                closest_distance_river ASC
            LIMIT 1
        ) rp ON TRUE
        WHERE
            CASE
                WHEN
                    :search = ''
                THEN
                    si.id IS NOT NULL
                ELSE
                    (
                        %(name_filter)s
                    )
            END
            --%(name_search)s
            %(country)s
        ;
SQL;

    final public const SEARCH_COORDINATE_DISTANCE = <<<SQL
        SELECT *
        FROM (
            SELECT DISTINCT ON (l.id)
                l.*,
                ST_AsEWKT(l.coordinate) as coordinate,
                CAST(si.relevance_score - COALESCE(rp.closest_distance_river, ST_Distance(l.coordinate, ST_MakePoint(:longitude, :latitude)::geography)) * 0.01 AS INTEGER) AS relevance_score,
                CASE 
                    WHEN rp.closest_distance_river IS NOT NULL THEN rp.closest_distance_river
                    ELSE ST_Distance(l.coordinate, ST_MakePoint(:longitude, :latitude)::geography)
                END AS closest_distance,
                ST_AsText(
                    CASE 
                        WHEN rp.closest_point_river IS NOT NULL THEN rp.closest_point_river
                        ELSE l.coordinate
                    END
                ) AS closest_point
            FROM
                location l
            -- INNER: Only show locations with search index
            INNER JOIN search_index si ON si.location_id = l.id
            -- LEFT: Also show locations without alternate names; Used for %(name_search)s
            --LEFT JOIN alternate_name an ON l.id = an.location_id AND (an.iso_language IN ('de') OR an.iso_language IS NULL)
            %(feature_code)s
            %(feature_class)s
            LEFT JOIN location_river lr ON lr.location_id = l.id
            LEFT JOIN river r ON lr.river_id = r.id
            -- INNER: Only show locations with country
            INNER JOIN country c ON l.country_id = c.id
            LEFT JOIN LATERAL (
                SELECT
                    rp.coordinates as coordinates_river,
                    ST_Distance(
                        rp.coordinates,
                        ST_MakePoint(:longitude, :latitude)::geography
                    ) AS closest_distance_river,
                    ST_AsText(ST_EndPoint(ST_ShortestLine(
                        rp.coordinates::geography,
                        ST_MakePoint(:longitude, :latitude)::geography
                    )::geometry))::geography AS closest_point_river
                FROM
                    river_part rp
                WHERE
                    rp.river_id = r.id AND ST_DWithin(
                        rp.coordinates,
                        ST_MakePoint(:longitude, :latitude)::geography,
                        :distance
                    )
                ORDER BY
                    closest_distance_river ASC
                LIMIT 1
            ) rp ON TRUE
            WHERE
                CASE
                    WHEN
                        :search = ''
                    THEN
                        si.id IS NOT NULL
                    ELSE
                        (
                            %(name_filter)s
                        )
                END AND
                ST_DWithin(
                    CASE
                        WHEN coordinates_river IS NOT NULL THEN ST_EndPoint(ST_ShortestLine(
                            coordinates_river,
                            ST_MakePoint(:longitude, :latitude)::geography
                        )::geometry)
                        ELSE l.coordinate
                    END,
                    ST_MakePoint(:longitude, :latitude)::geography, 
                    :distance
                )
                --%(name_search)s
                %(country)s
            ORDER BY
                l.id,
                %(sort_by)s
        ) sub
        ORDER BY
            %(sort_by)s
        %(limit)s;
SQL;

    final public const SEARCH_COORDINATE_DISTANCE_COUNT = <<<SQL
        SELECT
            COUNT(DISTINCT l.id) AS count
        FROM
            location l
        -- INNER: Only show locations with search index
        INNER JOIN search_index si ON si.location_id = l.id
        -- INNER: Only show locations with country
        INNER JOIN country c ON l.country_id = c.id
        -- LEFT: Also show locations without alternate names; Used for %(name_search)s
        --LEFT JOIN alternate_name an ON l.id = an.location_id AND (an.iso_language IN ('de') OR an.iso_language IS NULL)
        %(feature_code)s
        %(feature_class)s
        LEFT JOIN location_river lr ON lr.location_id = l.id
        LEFT JOIN river r ON lr.river_id = r.id
        LEFT JOIN LATERAL (
            SELECT
                rp.coordinates as coordinates_river,
                ST_Distance(
                    rp.coordinates,
                    ST_MakePoint(:longitude, :latitude)::geography
                ) AS closest_distance_river,
                ST_AsText(ST_EndPoint(ST_ShortestLine(
                    rp.coordinates::geography,
                    ST_MakePoint(:longitude, :latitude)::geography
                )::geometry))::geography AS closest_point_river
            FROM
                river_part rp
            WHERE
                rp.river_id = r.id AND ST_DWithin(
                    rp.coordinates,
                    ST_MakePoint(:longitude, :latitude)::geography,
                    :distance
                )
            ORDER BY
                closest_distance_river ASC
            LIMIT 1
        ) rp ON TRUE
        WHERE
            CASE
                WHEN
                    :search = ''
                THEN
                    si.id IS NOT NULL
                ELSE
                    (
                        %(name_filter)s
                    )
            END AND
            ST_DWithin(
                CASE
                    WHEN coordinates_river IS NOT NULL THEN ST_EndPoint(ST_ShortestLine(
                        coordinates_river,
                        ST_MakePoint(:longitude, :latitude)::geography
                    )::geometry)
                    ELSE l.coordinate
                END,
                ST_MakePoint(:longitude, :latitude)::geography, 
                :distance
            )
            --%(name_search)s
            %(country)s
        ;
SQL;



    final public const SEARCH_GEONAME_ID = <<<SQL
        SELECT *
        FROM (
            SELECT DISTINCT ON (l.id)
                l.*,
                ST_AsEWKT(l.coordinate) as coordinate,
                si.relevance_score AS relevance_score
            FROM
                location l
            LEFT JOIN search_index si ON si.location_id IN (%(location_ids)s) AND si.location_id = l.id
            WHERE
                l.id IN (%(location_ids)s)
            ORDER BY
                l.id,
                %(sort_by)s
        ) sub
        ORDER BY
            %(sort_by)s
        %(limit)s;
SQL;

    final public const SEARCH_GEONAME_ID_COORDINATE = <<<SQL
        SELECT *
        FROM (
            SELECT DISTINCT ON (l.id)
                l.*,
                ST_AsEWKT(l.coordinate) as coordinate,
                CAST(si.relevance_score - COALESCE(rp.closest_distance_river, ST_Distance(l.coordinate, ST_MakePoint(:longitude, :latitude)::geography)) * 0.01 AS INTEGER) AS relevance_score,
                CASE 
                    WHEN rp.closest_distance_river IS NOT NULL THEN rp.closest_distance_river
                    ELSE ST_Distance(l.coordinate, ST_MakePoint(:longitude, :latitude)::geography)
                END AS closest_distance,
                ST_AsText(
                    CASE 
                        WHEN rp.closest_point_river IS NOT NULL THEN rp.closest_point_river
                        ELSE l.coordinate
                    END
                ) AS closest_point
            FROM
                location l
            LEFT JOIN search_index si ON si.location_id IN (%(location_ids)s) AND si.location_id = l.id
            LEFT JOIN location_river lr ON lr.location_id = l.id
            LEFT JOIN river r ON lr.river_id = r.id
            LEFT JOIN LATERAL (
                SELECT
                    rp.coordinates as coordinates_river,
                    ST_Distance(
                        rp.coordinates,
                        ST_MakePoint(:longitude, :latitude)::geography
                    ) AS closest_distance_river,
                    ST_AsText(ST_EndPoint(ST_ShortestLine(
                        rp.coordinates::geography,
                        ST_MakePoint(:longitude, :latitude)::geography
                    )::geometry))::geography AS closest_point_river
                FROM
                    river_part rp
                WHERE
                    rp.river_id = r.id
                ORDER BY
                    closest_distance_river ASC
                LIMIT 1
            ) rp ON TRUE
            WHERE
                l.id IN (%(location_ids)s)
            ORDER BY
                l.id,
                %(sort_by)s
        ) sub
        ORDER BY
            %(sort_by)s
        %(limit)s;
SQL;
}
