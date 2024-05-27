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
use App\Entity\ZipCodeArea;
use App\Repository\Base\BaseCoordinateRepository;
use App\Service\LocationServiceConfig;
use Doctrine\Persistence\ManagerRegistry;
use Ixnode\PhpChecker\CheckerArray;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ZipCodeAreaRepository
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-03-16)
 * @since 0.1.0 (2024-03-16) First version.
 *
 * @method ZipCodeArea|null find($id, $lockMode = null, $lockVersion = null)
 * @method ZipCodeArea|null findOneBy(array $criteria, array $orderBy = null)
 * @method ZipCodeArea[]    findAll()
 * @method ZipCodeArea[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ZipCodeAreaRepository extends BaseCoordinateRepository
{
    /**
     * @param ManagerRegistry $registry
     * @param LocationServiceConfig $locationCountryService
     * @param ParameterBagInterface $parameterBag
     * @param TranslatorInterface $translator
     * @param LocationRepository $locationRepository
     */
    public function __construct(
        protected ManagerRegistry $registry,
        protected LocationServiceConfig $locationCountryService,
        protected ParameterBagInterface $parameterBag,
        protected TranslatorInterface $translator,
        protected LocationRepository $locationRepository
    )
    {
        parent::__construct($registry, $parameterBag, $translator, $locationRepository);
    }

    /**
     * Finds the zip codes from given latitude and longitude ordered by distance.
     *
     * Query example:
     * --------------
       SELECT id, zip_code, place_name
       FROM zip_code_area
       WHERE ST_Intersects(
         coordinates,
         ST_GeomFromText('POINT(13.752894 51.071870)', 4326)::geography
       );

       SELECT id, zip_code, place_name
       FROM zip_code_area
       WHERE ST_Intersects(
         coordinates,
         ST_MakePoint(13.752894, 51.071870)::geography
       );
     *
     * --------------
     *
     * Show indices:
     * -------------
       SELECT
         i.relname AS index_name,
         am.amname AS index_type,
         idx.indisprimary AS is_primary,
         idx.indisunique AS is_unique,
         pg_get_indexdef(idx.indexrelid) AS index_definition
       FROM
         pg_index AS idx
       JOIN
         pg_class AS i ON i.oid = idx.indexrelid
       JOIN
         pg_am AS am ON i.relam = am.oid
       WHERE
         idx.indrelid = 'zip_code_area'::regclass
       ORDER BY
         index_name;
     *
     * @param Coordinate|null $coordinate
     * @param Country|null $country
     * @param int|null $limit
     * @return array<int, ZipCodeArea>
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    public function findZipCodesByCoordinate(
        Coordinate|null $coordinate = null,
        Country|null $country = null,
        int|null $limit = null
    ): array
    {
        $queryBuilder = $this->createQueryBuilder('zca');

        /* Limit result by given distance. */
        if (!is_null($coordinate)) {
            $queryBuilder
                /* Attention: PostGIS uses lon/lat not lat/lon! */
                ->andWhere('ST_Intersects(
                    zca.coordinates,
                    ST_MakePoint(:longitude, :latitude)
                ) = TRUE')
                ->setParameter('latitude', $coordinate->getLatitude())
                ->setParameter('longitude', $coordinate->getLongitude())
            ;
        }

        /* Limit result by country. */
        if ($country instanceof Country) {
            $queryBuilder
                ->andWhere('zca.country = :country')
                ->setParameter('country', $country);
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
                ->checkClass(ZipCodeArea::class)
        );
    }

    /**
     * Returns the first zip code from given latitude and longitude ordered by distance.
     *
     * @param Coordinate|null $coordinate
     * @param Country|null $country
     * @param int|null $limit
     * @return ZipCodeArea|null
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    public function findZipCodeByCoordinate(
        Coordinate|null $coordinate = null,
        Country|null $country = null,
        int|null $limit = null
    ): ZipCodeArea|null
    {
        $zipCodes = $this->findZipCodesByCoordinate(
            coordinate: $coordinate,
            country: $country,
            limit: $limit
        );

        if (count($zipCodes) <= 0) {
            return null;
        }

        return $zipCodes[0];
    }
}
