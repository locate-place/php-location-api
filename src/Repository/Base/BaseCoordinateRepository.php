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

namespace App\Repository\Base;

use App\Entity\Location;
use App\Entity\ZipCode;
use App\Entity\ZipCodeArea;
use App\Repository\LocationRepository;
use App\Repository\ZipCodeAreaRepository;
use App\Repository\ZipCodeRepository;
use App\Utils\Db\DebugQuery;
use App\Utils\Doctrine\QueryBuilder as UtilQueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use JetBrains\PhpStorm\NoReturn;
use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class BaseCoordinateRepository
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-15)
 * @since 0.1.0 (2024-03-15) First version.
 * @extends ServiceEntityRepository<Location|ZipCode|ZipCodeArea>
 */
class BaseCoordinateRepository extends ServiceEntityRepository
{
    protected UtilQueryBuilder $queryBuilder;

    /**
     * @inheritdoc
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(
        protected ManagerRegistry $registry,
        protected ParameterBagInterface $parameterBag
    )
    {
        parent::__construct($registry, $this->getEntityClassByRepository());

        $this->queryBuilder = new UtilQueryBuilder($this->getEntityManager());
    }

    /**
     * @return class-string<Location|ZipCode|ZipCodeArea>
     */
    private function getEntityClassByRepository(): string
    {
        return match (static::class) {
            LocationRepository::class => Location::class,
            ZipCodeRepository::class => ZipCode::class,
            ZipCodeAreaRepository::class => ZipCodeArea::class,
            default => throw new LogicException(sprintf('Class "%s" is not supported.', static::class)),
        };
    }

    /**
     * Limit admin codes.
     *
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param array{a1?: string, a2?: string, a3?: string, a4?: string}|null $adminCodes
     * @return void
     * @throws CaseUnsupportedException
     */
    protected function limitAdminCodes(
        QueryBuilder $queryBuilder,
        string $alias,
        array|null $adminCodes
    ): void
    {
        if (is_null($adminCodes)) {
            return;
        }

        $notNull = $this->parameterBag->get('db_not_null');
        $null = $this->parameterBag->get('db_null');

        $queryBuilder
            ->leftJoin(sprintf('%s.adminCode', $alias), 'a');

        $adminCodesAll = $this->parameterBag->get('admin_codes_all');

        if (!is_array($adminCodesAll)) {
            throw new CaseUnsupportedException('The given admin_codes_all configuration is not an array.');
        }

        foreach ($adminCodesAll as $adminCode) {
            if (array_key_exists($adminCode, $adminCodes)) {
                $adminName = match ($adminCode) {
                    'a1' => 'admin1Code',
                    'a2' => 'admin2Code',
                    'a3' => 'admin3Code',
                    'a4' => 'admin4Code',
                };

                $valueAdminCode = $adminCodes[$adminCode];

                match (true) {
                    $valueAdminCode === $notNull => $queryBuilder
                        ->andWhere(sprintf('a.%s IS NOT NULL', $adminName)),
                    $valueAdminCode === $null => $queryBuilder
                        ->andWhere(sprintf('a.%s IS NULL', $adminName)),
                    str_starts_with($valueAdminCode, '?') => $queryBuilder
                        ->andWhere(sprintf('a.%s = :%s OR a.%s IS NULL', $adminName, $adminCode, $adminName))
                        ->setParameter($adminCode, substr($valueAdminCode, 1)),
                    default => $queryBuilder
                        ->andWhere(sprintf('a.%s = :%s', $adminName, $adminCode))
                        ->setParameter($adminCode, $valueAdminCode),
                };
            }
        }
    }

    /**
     * Prints the raw query from given query builder.
     *
     * @param QueryBuilder $queryBuilder
     * @return void
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    #[NoReturn]
    protected function debugQuery(QueryBuilder $queryBuilder): void
    {
        $debugQuery = new DebugQuery($queryBuilder);
        print $debugQuery->getSqlRaw();
        exit();
    }
}
