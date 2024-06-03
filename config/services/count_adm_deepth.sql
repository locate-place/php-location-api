WITH scenario_counts AS (
    SELECT
        c.code as country,
        ac.admin1_code as admin1_code,
        CASE
            WHEN ac.admin2_code IS NOT NULL AND ac.admin3_code IS NOT NULL AND ac.admin4_code IS NOT NULL THEN 'a4'
            WHEN ac.admin2_code IS NOT NULL AND ac.admin3_code IS NOT NULL AND ac.admin4_code IS NULL THEN 'a3'
            WHEN ac.admin2_code IS NOT NULL AND ac.admin3_code IS NULL AND ac.admin4_code IS NULL THEN 'a2'
            WHEN ac.admin2_code IS NULL AND ac.admin3_code IS NULL AND ac.admin4_code IS NULL THEN 'a1'
            ELSE 'a0'
            END AS scenario,
        COUNT(*) AS count
    FROM
        location l
            INNER JOIN
        admin_code ac ON l.admin_code_id = ac.id
            INNER JOIN
        country c ON l.country_id = c.id
    WHERE
      -- only places with feature class P
        l.feature_class_id = 2 AND
        l.country_id = 7 AND
        ac.admin1_code IS NOT NULL
    GROUP BY
        c.code, ac.admin1_code, scenario
),
     max_scenarios AS (
         SELECT
             country,
             admin1_code,
             scenario,
             count,
             ROW_NUMBER() OVER (PARTITION BY country, admin1_code ORDER BY count DESC) AS row_num
         FROM
             scenario_counts
     ),
     counts AS (
         SELECT
             country,
             admin1_code,
             SUM(CASE WHEN scenario = 'a0' THEN count ELSE 0 END) AS count_a0,
             SUM(CASE WHEN scenario = 'a1' THEN count ELSE 0 END) AS count_a1,
             SUM(CASE WHEN scenario = 'a2' THEN count ELSE 0 END) AS count_a2,
             SUM(CASE WHEN scenario = 'a3' THEN count ELSE 0 END) AS count_a3,
             SUM(CASE WHEN scenario = 'a4' THEN count ELSE 0 END) AS count_a4
         FROM
             scenario_counts
         GROUP BY
             country, admin1_code
     )
SELECT
    m.country,
    m.admin1_code,
    m.scenario,
    m.count,
    c.count_a0,
    c.count_a1,
    c.count_a2,
    c.count_a3,
    c.count_a4
FROM
    max_scenarios m
        INNER JOIN
    counts c ON m.country = c.country AND m.admin1_code = c.admin1_code
WHERE
    m.row_num = 1
ORDER BY
    m.country,
    m.admin1_code;