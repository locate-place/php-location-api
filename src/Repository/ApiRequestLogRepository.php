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

namespace App\Repository;

use App\Entity\ApiKey;
use App\Entity\ApiRequestLog;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class ApiRequestLogRepository
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-08)
 * @since 0.1.0 (2024-06-08) First version.
 *
 * @method ApiRequestLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiRequestLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiRequestLog[]    findAll()
 * @method ApiRequestLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<ApiRequestLog>
 */
class ApiRequestLogRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiRequestLog::class);
    }

    /**
     * Counts the result of valid logs.
     *
     * @param ApiKey $apiKey
     * @param string $ip
     * @param DateTime|null $dateTime
     * @return int
     */
    public function countIpLogs(ApiKey $apiKey, string $ip, DateTime $dateTime = null): int
    {
        $queryBuilder = $this->createQueryBuilder('l');

        $queryBuilder
            ->select('COUNT(l.id)')
            ->join('l.apiEndpoint', 'e')

            /* Only check given ip address. */
            ->andWhere('l.ip = :ip')
            ->setParameter('ip', $ip)

            /* Only current API key. */
            ->andWhere('l.apiKey = :apiKey')
            ->setParameter('apiKey', $apiKey)

            /* Only valid logs. */
            ->andWhere('l.isValid = :valid')
            ->setParameter('valid', true)

            /* Only apiEndpoint's with more than 0 credits. */
            ->andWhere('e.credits > 0')
        ;

        if (!is_null($dateTime)) {
            $queryBuilder
                ->andWhere('l.createdAt >= :createdAt')
                ->setParameter('createdAt', $dateTime)
            ;
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Counts the result of valid logs for one hour.
     *
     * @param ApiKey $apiKey
     * @param string $ip
     * @return int
     */
    public function countIpLogsLastHour(ApiKey $apiKey, string $ip): int
    {
        return $this->countIpLogs($apiKey, $ip, new DateTime('-1 hour'));
    }

    /**
     * Counts the result of valid logs for one minute.
     *
     * @param ApiKey $apiKey
     * @param string $ip
     * @return int
     */
    public function countIpLogsLastMinute(ApiKey $apiKey, string $ip): int
    {
        return $this->countIpLogs($apiKey, $ip, new DateTime('-1 minute'));
    }
}
