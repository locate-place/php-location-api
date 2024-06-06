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

namespace App\Repository\Base;

use App\Entity\Location;
use App\Entity\ZipCode;
use App\Entity\ZipCodeArea;
use App\Repository\LocationRepository;
use App\Repository\ZipCodeAreaRepository;
use App\Repository\ZipCodeRepository;
use App\Service\LocationServiceConfig;
use App\Utils\Db\DebugNativeQuery;
use App\Utils\Db\DebugQuery;
use App\Utils\Doctrine\QueryBuilder as UtilQueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    private bool $debug = false;

    protected UtilQueryBuilder $queryBuilder;

    /**
     * @inheritdoc
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(
        protected ManagerRegistry $registry,
        protected ParameterBagInterface $parameterBag,
        protected TranslatorInterface $translator,
        protected LocationRepository $locationRepository
    )
    {
        parent::__construct(
            $registry,
            $this->getEntityClassByRepository()
        );

        $this->queryBuilder = new UtilQueryBuilder(
            $this->getEntityManager(),
            $locationRepository,
            new LocationServiceConfig($parameterBag, $translator)
        );
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     * @return self
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;

        return $this;
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
    public function debugQuery(QueryBuilder $queryBuilder): void
    {
        if (!$this->isDebug()) {
            return;
        }

        $debugQuery = new DebugQuery($queryBuilder);

        print '<h1>SQL Query</h1>';
        print '<textarea style="width: 100%" rows="50">';
        print $debugQuery->getSqlRaw();
        print '</textarea>';
    }

    /**
     * Prints the raw query from given native builder.
     *
     * @param NativeQuery $nativeQuery
     * @return void
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function debugNativeQuery(NativeQuery $nativeQuery): void
    {
        if (!$this->isDebug()) {
            return;
        }

        $debugNativeQuery = new DebugNativeQuery($nativeQuery);

        print '<h1>SQL Query</h1>';
        print '<textarea style="width: 100%" rows="50">';
        print $debugNativeQuery->getSqlRaw();
        print '</textarea>';
    }

    /**
     * Debug admin areas.
     *
     * @param string $adminArea
     * @param Location|null $admin2
     * @param Location|null $admin3
     * @param Location|null $admin4
     * @param Location|null $admin5
     * @param Location|null $cityAdm
     * @param Location|null $districtAdm
     * @param Location|null $city
     * @param Location|null $district
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function debugAdminAreas(
        string $adminArea,
        Location|null $admin2,
        Location|null $admin3,
        Location|null $admin4,
        Location|null $admin5,
        Location|null $cityAdm,
        Location|null $districtAdm,
        Location|null $city,
        Location|null $district
    ): void
    {
        if (!$this->isDebug()) {
            return;
        }

        print PHP_EOL;
        print PHP_EOL;
        print '<table cellpadding="10">';
        print '<tr><td>Admin area:</td><td>'.$adminArea.'</td></tr>';
        print '<tr><td></td></tr>';
        print '<tr><td>Admin 2:</td><td>'.(is_null($admin2) ? 'n/a' : $admin2->getName()).'</td></tr>';
        print '<tr><td>Admin 3:</td><td>'.(is_null($admin3) ? 'n/a' : $admin3->getName()).'</td></tr>';
        print '<tr><td>Admin 4:</td><td>'.(is_null($admin4) ? 'n/a' : $admin4->getName()).'</td></tr>';
        print '<tr><td>Admin 5:</td><td>'.(is_null($admin5) ? 'n/a' : $admin5->getName()).'</td></tr>';
        print '<tr><td>City Adm:</td><td>'.(is_null($cityAdm) ? 'n/a' : $cityAdm->getName()).'</td></tr>';
        print '<tr><td>District Adm:</td><td>'.(is_null($districtAdm) ? 'n/a' : $districtAdm->getName()).'</td></tr>';
        print '<tr><td>City:</td><td>'.(is_null($city) ? 'n/a' : $city->getName()).'</td></tr>';
        print '<tr><td>District:</td><td>'.(is_null($district) ? 'n/a' : $district->getName()).'</td></tr>';
        print '</table>';
    }

    /**
     * Stops execution in debug mode.
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function debugStopExecution(): void
    {
        if (!$this->isDebug()) {
            return;
        }

        exit();
    }
}
