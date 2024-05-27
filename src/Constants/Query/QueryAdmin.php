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
 * Class QueryAdmin
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-05-26)
 * @since 0.1.0 (2024-05-26) First version.
 */
class QueryAdmin
{
    final public const ADMIN = <<<SQL
        WITH ranked_locations AS (
            SELECT
                --ac.id,
                --l.name,
                --fcl.class,
                --fco.code,
                --l.population,
                --ac.admin1_code,
                --ac.admin2_code,
                --ac.admin3_code,
                --ac.admin4_code,
                l.*,
                ST_AsEWKT(l.coordinate) as coordinate,
                l.coordinate <-> 'SRID=4326;POINT(%(longitude)s %(latitude)s)' AS distance_meters,
                CASE
                    -- ADM Area
                    WHEN l.feature_code_id IN (%(feature_code_admin2)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code IS NULL AND ac.admin4_code IS NULL THEN 10
                    WHEN l.feature_code_id IN (%(feature_code_admin3)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code IS NULL THEN 11
                    WHEN l.feature_code_id IN (%(feature_code_admin4)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s THEN 12
                    WHEN l.feature_code_id IN (%(feature_code_admin5)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s THEN 13
        
                    -- Place Area
                    WHEN l.feature_code_id IN (%(feature_code_cities)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s THEN 20
                    WHEN l.feature_code_id IN (%(feature_code_districts)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s THEN 21
                    WHEN l.feature_code_id IN (%(feature_code_cities_districts)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s THEN 29
        
                    -- Unknown
                    ELSE 90
                END AS location_type,

                -- rank city
                CASE code
                    %(feature_code_cities_case_when)s
                    ELSE null
                END AS rank_city,

                -- rank district
                CASE code
                    %(feature_code_districts_case_when)s
                    ELSE null
                END AS rank_district,
                
                -- counted index grouped by location_type, sorted by feature_code_id & distance
                ROW_NUMBER() OVER (
                    PARTITION BY CASE
        
                        -- ADM Area
                        WHEN l.feature_code_id IN (%(feature_code_admin2)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code IS NULL AND ac.admin4_code IS NULL THEN 10
                        WHEN l.feature_code_id IN (%(feature_code_admin3)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code IS NULL THEN 11
                        WHEN l.feature_code_id IN (%(feature_code_admin4)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s THEN 12
                        WHEN l.feature_code_id IN (%(feature_code_admin5)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s THEN 13
        
                        -- Place Area
                        WHEN l.feature_code_id IN (%(feature_code_cities)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s THEN 20
                        WHEN l.feature_code_id IN (%(feature_code_districts)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s THEN 21
                        WHEN l.feature_code_id IN (%(feature_code_cities_districts)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s THEN 29
        
                        -- Unknown
                        ELSE 90
                    END
        
                    ORDER BY
                        -- sort by feature code
                        CASE 
                            -- ADM Area
                            WHEN l.feature_code_id IN (%(feature_code_admin2)s) THEN
                                CASE l.feature_code_id
                                    %(feature_code_admin2_when)s
                                END
                            WHEN l.feature_code_id IN (%(feature_code_admin3)s) THEN
                                CASE l.feature_code_id
                                    %(feature_code_admin3_when)s
                                END
                            WHEN l.feature_code_id IN (%(feature_code_admin4)s) THEN
                                CASE l.feature_code_id
                                    %(feature_code_admin4_when)s
                                END
                            WHEN l.feature_code_id IN (%(feature_code_admin5)s) THEN
                                CASE l.feature_code_id
                                    %(feature_code_admin5_when)s
                                END
            
                            -- Place Area
                            WHEN l.feature_code_id IN (%(feature_code_cities)s) THEN 
                                CASE l.feature_code_id
                                    %(feature_code_cities_when)s
                                END
                            WHEN l.feature_code_id IN (%(feature_code_districts)s) THEN
                                CASE l.feature_code_id
                                    %(feature_code_districts_when)s
                                END
                            WHEN l.feature_code_id IN (%(feature_code_cities_districts)s) THEN
                                CASE l.feature_code_id
                                    %(feature_code_cities_districts_when)s
                                END
            
                            -- Unknown
                            ELSE 99
                        END,
                        
                        -- sort by population
                        CASE 
                            -- ADM Area
                            WHEN l.feature_code_id IN (%(feature_code_admin2)s) THEN NULL
                            WHEN l.feature_code_id IN (%(feature_code_admin3)s) THEN NULL
                            WHEN l.feature_code_id IN (%(feature_code_admin4)s) THEN NULL
                            WHEN l.feature_code_id IN (%(feature_code_admin5)s) THEN NULL
            
                            -- Place Area
                            WHEN l.feature_code_id IN (%(feature_code_cities)s) THEN %(sort_by_population_cities)s
                            WHEN l.feature_code_id IN (%(feature_code_districts)s) THEN %(sort_by_population_districts)s
                            WHEN l.feature_code_id IN (%(feature_code_cities_districts)s) THEN %(sort_by_population_cities_districts)s
            
                            -- Unknown
                            ELSE NULL
                        END DESC,
                        
                        -- sort by distance
                        l.coordinate <-> 'SRID=4326;POINT(%(longitude)s %(latitude)s)'
                ) AS rn
            FROM
                admin_code ac
            INNER JOIN
                location l ON l.admin_code_id = ac.id
            INNER JOIN
                feature_class fcl ON l.feature_class_id = fcl.id
            INNER JOIN
                feature_code fco ON l.feature_code_id = fco.id
            WHERE
                ST_DWithin(l.coordinate, ST_SetSRID(ST_MakePoint(%(longitude)s, %(latitude)s), 4326)::geography, :distance) AND
                l.country_id = 1 AND
                (
                    -- ADM 1 - 5
                    (l.feature_code_id IN (%(feature_code_admin2)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code IS NULL AND ac.admin4_code IS NULL) OR
                    (l.feature_code_id IN (%(feature_code_admin3)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code IS NULL) OR
                    (l.feature_code_id IN (%(feature_code_admin4)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s) OR
                    (l.feature_code_id IN (%(feature_code_admin5)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s) OR
        
                    -- Place
                    (l.feature_code_id IN (%(feature_code_cities)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s) OR
                    (l.feature_code_id IN (%(feature_code_districts)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s) OR
                    (l.feature_code_id IN (%(feature_code_cities_districts)s) AND ac.admin1_code %(admin1_code)s AND ac.admin2_code %(admin2_code)s AND ac.admin3_code %(admin3_code)s AND ac.admin4_code %(admin4_code)s)
                )
        )
        SELECT
            *
        FROM
            ranked_locations
        WHERE
            rn = 1 OR location_type = 29
        ORDER BY
            location_type;
SQL;
}
