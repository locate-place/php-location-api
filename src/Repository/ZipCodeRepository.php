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

use App\Entity\Country;
use App\Entity\ZipCode;
use App\Repository\Base\BaseCoordinateRepository;
use App\Service\LocationServiceConfig;
use Doctrine\Persistence\ManagerRegistry;
use Ixnode\PhpChecker\CheckerArray;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class ZipCodeRepository
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-03-14)
 * @since 0.1.0 (2024-03-14) First version.
 *
 * @method ZipCode|null find($id, $lockMode = null, $lockVersion = null)
 * @method ZipCode|null findOneBy(array $criteria, array $orderBy = null)
 * @method ZipCode[]    findAll()
 * @method ZipCode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ZipCodeRepository extends BaseCoordinateRepository
{
    /**
     * @param ManagerRegistry $registry
     * @param LocationServiceConfig $locationCountryService
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(
        protected ManagerRegistry $registry,
        protected LocationServiceConfig $locationCountryService,
        protected ParameterBagInterface $parameterBag
    )
    {
        parent::__construct($registry, $parameterBag);
    }

    /**
     * Finds the zip codes from given latitude and longitude ordered by distance.
     *
     * Query example:
     * --------------
       SELECT
         id,
         ST_Y(coordinate::geometry) AS latitude,
         ST_X(coordinate::geometry) AS longitude,
         place_name,
         postal_code,
         ST_Distance(coordinate, ST_MakePoint(13.741351013208588, 51.06115159751123)::geography) AS distance_meter
       FROM zip_code
       WHERE ST_DWithin(coordinate, ST_MakePoint(13.741351013208588, 51.06115159751123)::geography, 10000)
       ORDER BY distance_meter
       LIMIT 50;
     * --------------
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
         * idx.indrelid = 'zip_code'::regclass
       * ORDER BY
         * index_name;
     *
     * @param Coordinate|null $coordinate
     * @param int|null $distanceMeter
     * @param Country|null $country
     * @param array{a1?: string, a2?: string, a3?: string, a4?: string}|null $adminCodes
     * @param int|null $limit
     * @return array<int, ZipCode>
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    public function findZipCodesByCoordinate(
        Coordinate|null $coordinate = null,
        int|null $distanceMeter = null,
        Country|null $country = null,
        array|null $adminCodes = [],
        int|null $limit = null
    ): array
    {
        $queryBuilder = $this->createQueryBuilder('zc');

        /* Limit result by given distance. */
        if (!is_null($coordinate) && is_int($distanceMeter)) {
            $queryBuilder
                /* Attention: PostGIS uses lon/lat not lat/lon! */
                ->andWhere('ST_DWithin(
                zc.coordinate,
                ST_MakePoint(:longitude, :latitude),
                :distance,
                TRUE
            ) = TRUE')
                ->setParameter('latitude', $coordinate->getLatitude())
                ->setParameter('longitude', $coordinate->getLongitude())
                ->setParameter('distance', $distanceMeter)
            ;
        }

        /* Limit result by country. */
        if ($country instanceof Country) {
            $queryBuilder
                ->andWhere('zc.country = :country')
                ->setParameter('country', $country);
        }

        /* Limit with admin codes. */
        $this->limitAdminCodes($queryBuilder, 'zc', $adminCodes);

        /* Order result by distance (uses <-> for performance reasons). */
        if (!is_null($coordinate)) {
            $queryBuilder
                ->addSelect(sprintf(
                    'DistanceOperator(zc.coordinate, %f, %f) AS HIDDEN distance',
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
                ->checkClass(ZipCode::class)
        );
    }

    /**
     * Returns the first zip code from given latitude and longitude ordered by distance.
     *
     * @param Coordinate|null $coordinate
     * @param int|null $distanceMeter
     * @param Country|null $country
     * @param array{a1?: string, a2?: string, a3?: string, a4?: string}|null $adminCodes
     * @param int|null $limit
     * @return ZipCode|null
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    public function findZipCodeByCoordinate(
        Coordinate|null $coordinate = null,
        int|null $distanceMeter = null,
        Country|null $country = null,
        array|null $adminCodes = [],
        int|null $limit = null
    ): ZipCode|null
    {
        $zipCodes = $this->findZipCodesByCoordinate(
            coordinate: $coordinate,
            distanceMeter: $distanceMeter,
            country: $country,
            adminCodes: $adminCodes,
            limit: $limit
        );

        if (count($zipCodes) <= 0) {
            return null;
        }

        return $zipCodes[0];
    }
}
