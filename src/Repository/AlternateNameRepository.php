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

use App\Constants\DB\FeatureClass;
use App\Constants\DB\FeatureCode as DbFeatureCode;
use App\Constants\Language\LanguageCode;
use App\Constants\Property\Airport\IataIgnore;
use App\Entity\AlternateName;
use App\Entity\Location;
use App\Utils\Wikipedia\Wikipedia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Ixnode\PhpChecker\CheckerArray;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class AlternateNameRepository
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-27)
 * @since 0.1.0 (2023-06-27) First version.
 *
 * @method AlternateName|null find($id, $lockMode = null, $lockVersion = null)
 * @method AlternateName|null findOneBy(array $criteria, array $orderBy = null)
 * @method AlternateName[]    findAll()
 * @method AlternateName[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<AlternateName>
 */
class AlternateNameRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlternateName::class);
    }

    /**
     * Returns an array of AlternateName objects
     *
     * @param Location $location
     * @param string $isoLanguage
     * @return AlternateName[]
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    public function findByIsoLanguage(Location $location, string $isoLanguage): array
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->andWhere('a.location = :location')
            ->setParameter('location', $location)

            ->andWhere('a.isoLanguage = :isoLanguage')
            ->setParameter('isoLanguage', $isoLanguage)

            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
        ;

        /* Returns the result. */
        return array_values(
            (new CheckerArray($queryBuilder->getQuery()->getResult()))
                ->checkClass(AlternateName::class)
        );
    }

    /**
     * Returns the first AlternateName object.
     *
     * @param Location $location
     * @param string $isoLanguage
     * @param string|null $language
     * @return AlternateName|null
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function findOneByIsoLanguage(Location $location, string $isoLanguage, string $language = null): ?AlternateName
    {
        $alternateNames = $this->findByIsoLanguage($location, $isoLanguage);

        if (count($alternateNames) <= 0) {
            return null;
        }

        if ($isoLanguage !== LanguageCode::LINK) {
            return $alternateNames[0];
        }

        if (is_null($language)) {
            return null;
        }

        return (new Wikipedia($alternateNames))->getWikipediaLink($language);
    }

    /**
     * Find one AlternateName entity by given iata code.
     *
     * @param string $iata
     * @param bool $ignoreExistingProperties
     * @return AlternateName|null
     * @throws NonUniqueResultException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function findOneByAirportAndIata(string $iata, bool $ignoreExistingProperties = false): ?AlternateName
    {
        if (array_key_exists($iata, IataIgnore::IGNORE)) {
            return null;
        }

        $queryBuilder = $this->createQueryBuilder('a');

        $queryBuilder
            ->leftJoin('a.location', 'l')
            ->leftJoin('l.featureClass', 'fcl')
            ->leftJoin('l.featureCode', 'fco')

            ->andWhere('fcl.class = :featureClass')
            ->setParameter('featureClass', FeatureClass::S)

            ->andWhere('fco.code = :featureCode')
            ->setParameter('featureCode', DbFeatureCode::AIRP)

            ->andWhere('a.isoLanguage = :isoLanguage')
            ->setParameter('isoLanguage', LanguageCode::IATA)

            ->andWhere('a.alternateName = :alternateName')
            ->setParameter('alternateName', $iata)

            ->setMaxResults(1)
        ;

        if ($ignoreExistingProperties) {
            $queryBuilder
                ->andWhere('NOT EXISTS (SELECT 1 FROM App\Entity\Property p WHERE p.location = l)');
        }

        $alternateName = $queryBuilder->getQuery()->getOneOrNullResult();

        if ($alternateName instanceof AlternateName) {
            return $alternateName;
        }

        return null;
    }

    /**
     * Find all AlternateName entity by given airport feature code.
     *
     * @param int|null $maxResults
     * @param bool $ignoreExistingProperties
     * @return string[]
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function findIataCodes(int $maxResults = null, bool $ignoreExistingProperties = false): array
    {
        $queryBuilder = $this->createQueryBuilder('a');

        $queryBuilder
            ->select('a.alternateName')

            ->leftJoin('a.location', 'l')
            ->leftJoin('l.featureClass', 'fcl')
            ->leftJoin('l.featureCode', 'fco')

            ->andWhere('fcl.class = :featureClass')
            ->setParameter('featureClass', FeatureClass::S)

            ->andWhere('fco.code = :featureCode')
            ->setParameter('featureCode', DbFeatureCode::AIRP)

            ->andWhere('a.isoLanguage = :isoLanguage')
            ->setParameter('isoLanguage', LanguageCode::IATA)
        ;

        $queryBuilder
            ->andWhere('a.alternateName NOT IN (:excludedNames)')
            ->setParameter('excludedNames', array_keys(IataIgnore::IGNORE));

        if (!is_null($maxResults)) {
            $queryBuilder->setMaxResults($maxResults);
        }

        if ($ignoreExistingProperties) {
            $queryBuilder
                ->andWhere('NOT EXISTS (SELECT 1 FROM App\Entity\Property p WHERE p.location = l)');
        }

        $result = $queryBuilder->getQuery()->getScalarResult();

        return array_column($result, 'alternateName');
    }
}
