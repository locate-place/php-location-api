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
        SELECT
            l.*,
            ST_AsEWKT(l.coordinate) as coordinate,
            si.relevance_score AS relevance_score
        FROM
            location l
        LEFT JOIN search_index si ON si.location_id = l.id
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
                        si.search_text_simple @@ to_tsquery('simple', :search) OR
                        si.search_text_de @@ to_tsquery('german', :search)
                    )
            END
        ORDER BY
            %(sort_by)s
        %(limit)s;
SQL;

    final public const SEARCH_COUNT = <<<SQL
        SELECT
            COUNT(l.id) AS count
        FROM
            location l
        LEFT JOIN search_index si ON si.location_id = l.id
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
                        si.search_text_simple @@ to_tsquery('simple', :search) OR
                        si.search_text_de @@ to_tsquery('german', :search)
                    )
            END
        ;
SQL;

    final public const SEARCH_COORDINATE = <<<SQL
        SELECT
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
        LEFT JOIN search_index si ON si.location_id = l.id
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
                        si.search_text_simple @@ to_tsquery('simple', :search) OR
                        si.search_text_de @@ to_tsquery('german', :search)
                    )
            END
        ORDER BY
            %(sort_by)s
        %(limit)s;
SQL;

    final public const SEARCH_COORDINATE_COUNT = <<<SQL
        SELECT
            COUNT(l.id) AS count
        FROM
            location l
        LEFT JOIN search_index si ON si.location_id = l.id
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
                        si.search_text_simple @@ to_tsquery('simple', :search) OR
                        si.search_text_de @@ to_tsquery('german', :search)
                    )
            END
        ;
SQL;

    final public const SEARCH_COORDINATE_DISTANCE = <<<SQL
        SELECT
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
        LEFT JOIN search_index si ON si.location_id = l.id
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
                        si.search_text_simple @@ to_tsquery('simple', :search) OR
                        si.search_text_de @@ to_tsquery('german', :search)
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
        ORDER BY
            %(sort_by)s
        %(limit)s;
SQL;

    final public const SEARCH_COORDINATE_DISTANCE_COUNT = <<<SQL
        SELECT
            COUNT(l.id) AS count
        FROM
            location l
        LEFT JOIN search_index si ON si.location_id = l.id
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
                        si.search_text_simple @@ to_tsquery('simple', :search) OR
                        si.search_text_de @@ to_tsquery('german', :search)
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
        ;
SQL;
}
