-- PCLI -> l.feature_code_id = 175
-- ADM1 -> l.feature_code_id = 107
-- ADM2 -> l.feature_code_id = 104
-- ADM3 -> l.feature_code_id = 11
-- ADM4 -> l.feature_code_id = 68
-- ADM5 -> l.feature_code_id = 119
--
--  PPL   -> l.feature_code_id = 14
--  PPLA  -> l.feature_code_id = 81
--  PPLA2 -> l.feature_code_id = 62
--  PPLA3 -> l.feature_code_id = 27
--  PPLA4 -> l.feature_code_id = 15
--  PPLA5 -> l.feature_code_id = 347
--  PPLC  -> l.feature_code_id = 192
--  PPLCH -> l.feature_code_id = 340
--  PPLF  -> l.feature_code_id = 145
--  PPLG  -> l.feature_code_id = 275
--  PPLH  -> l.feature_code_id = 32
--  PPLL  -> l.feature_code_id = 20
--  PPLQ  -> l.feature_code_id = 71
--  PPLR  -> l.feature_code_id = 249
--  PPLS  -> l.feature_code_id = 160
--  PPLW  -> l.feature_code_id = 31
--  PPLX  -> l.feature_code_id = 2
--  STLMT -> l.feature_code_id = 465
--
-- A    -> l.feature_class_id = 6
-- P    -> l.feature_class_id = 2

SELECT
    ac.id,
    l.name,
    fcl.class,
    fco.code,
    ac.admin1_code,
    ac.admin2_code,
    ac.admin3_code,
    ac.admin4_code,
    ST_Distance(l.coordinate, ST_SetSRID(ST_MakePoint(13.73832, 51.05089), 4326)) AS distance_meters,
    CASE
        -- ADM Area
        WHEN l.feature_code_id = 175 AND ac.admin1_code = '00' AND ac.admin2_code IS NULL AND ac.admin3_code IS NULL AND ac.admin4_code IS NULL THEN 1
        WHEN l.feature_code_id = 107 AND ac.admin1_code = '13' AND ac.admin2_code IS NULL AND ac.admin3_code IS NULL AND ac.admin4_code IS NULL THEN 2
        WHEN l.feature_code_id = 104 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code IS NULL AND ac.admin4_code IS NULL THEN 3
        WHEN l.feature_code_id = 11 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code = '14612' AND ac.admin4_code IS NULL THEN 4
        WHEN l.feature_code_id = 68 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code = '14612' AND ac.admin4_code = '14612000' THEN 5
        WHEN l.feature_code_id = 119 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code = '14612' AND ac.admin4_code = '14612000' THEN 6

        -- Place Area
        WHEN l.feature_code_id = 81 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code = '14612' AND ac.admin4_code = '14612000' THEN 7
        WHEN l.feature_code_id IN (14, 2) AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code = '14612' AND ac.admin4_code = '14612000' THEN 8

        -- Unknown
        ELSE 11
        END AS order_column
FROM
    admin_code ac
        INNER JOIN
    location l ON l.admin_code_id = ac.id
        INNER JOIN
    feature_class fcl ON l.feature_class_id = fcl.id
        INNER JOIN
    feature_code fco ON l.feature_code_id = fco.id
WHERE
    l.country_id = 1 AND
    (
        -- country
        (l.feature_code_id = 175 AND ac.admin1_code = '00' AND ac.admin2_code IS NULL AND ac.admin3_code IS NULL AND ac.admin4_code IS NULL) OR

            -- ADM 1 - 5
        (l.feature_code_id = 107 AND ac.admin1_code = '13' AND ac.admin2_code IS NULL AND ac.admin3_code IS NULL AND ac.admin4_code IS NULL) OR
        (l.feature_code_id = 104 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code IS NULL AND ac.admin4_code IS NULL) OR
        (l.feature_code_id = 11 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code = '14612' AND ac.admin4_code IS NULL) OR
        (l.feature_code_id = 68 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code = '14612' AND ac.admin4_code = '14612000') OR
        (l.feature_code_id = 119 AND l.coordinate <-> ST_SetSRID(ST_MakePoint(13.73832, 51.05089), 4326) <= 10000 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code = '14612' AND ac.admin4_code = '14612000') OR

            -- Place
            --(l.feature_class_id = 2 AND ac.admin1_code = '13' AND ac.admin2_code IS NULL AND ac.admin3_code IS NULL AND ac.admin4_code IS NULL) OR
            --(l.feature_class_id = 2 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code IS NULL AND ac.admin4_code IS NULL) OR
            --(l.feature_class_id = 2 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code = '14612' AND ac.admin4_code IS NULL) OR
            --(l.feature_class_id = 2 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code = '14612' AND ac.admin4_code = '14612000')
        (l.feature_code_id = 81 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code = '14612' AND ac.admin4_code = '14612000') OR
        (l.feature_code_id IN (14, 2) AND l.coordinate <-> ST_SetSRID(ST_MakePoint(13.73832, 51.05089), 4326) <= 10000 AND ac.admin1_code = '13' AND ac.admin2_code = '00' AND ac.admin3_code = '14612' AND ac.admin4_code = '14612000')

        )
ORDER BY
    order_column, distance_meters;