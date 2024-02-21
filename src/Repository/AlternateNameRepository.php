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

use App\Constants\Language\LanguageCode;
use App\Entity\AlternateName;
use App\Entity\Location;
use App\Utils\Wikipedia\Wikipedia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
