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

namespace App\Utils\Api;

use ApiPlatform\Metadata\HttpOperation;
use App\ApiPlatform\State\Base\ResourceWrapperCustom;
use App\Constants\Code\ApiKey;
use App\Constants\DB\ApiRequestLogType;
use App\Entity\ApiEndpoint as ApiEndpointEntity;
use App\Entity\ApiKey as ApiKeyEntity;
use App\Entity\ApiKeyCreditsDay as ApiKeyCreditsDayEntity;
use App\Entity\ApiKeyCreditsMonth as ApiKeyCreditsMonthEntity;
use App\Entity\ApiRequestLog as ApiRequestLogEntity;
use App\Repository\ApiEndpointRepository;
use App\Repository\ApiKeyCreditsDayRepository;
use App\Repository\ApiKeyCreditsMonthRepository;
use App\Repository\ApiKeyRepository;
use App\Repository\ApiRequestLogRepository;
use App\Repository\ApiRequestLogTypeRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ApiLogger
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-08)
 * @since 0.1.0 (2024-06-08) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ApiLogger
{
    private string|null $errorLast = null;

    private readonly Request $request;

    private ApiKeyEntity|null|false $apiKey = false;

    private ApiEndpointEntity|null|false $apiEndpoint = false;

    private ApiKeyCreditsDayEntity|null $apiKeyCreditsDay = null;

    private ApiKeyCreditsMonthEntity|null $apiKeyCreditsMonth = null;

    private string|null $apiRequestLogTypeValue = null;

    private bool $initialized = false;

    /**
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $entityManager
     * @param ApiEndpointRepository $apiEndpointRepository
     * @param ApiKeyRepository $apiKeyRepository
     * @param ApiRequestLogRepository $apiRequestLogRepository
     * @param ApiRequestLogTypeRepository $apiRequestLogTypeRepository
     * @param ApiKeyCreditsDayRepository $apiKeyCreditsDayRepository
     * @param ApiKeyCreditsMonthRepository $apiKeyCreditsMonthRepository
     */
    public function __construct(
        RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiEndpointRepository $apiEndpointRepository,
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly ApiRequestLogRepository $apiRequestLogRepository,
        private readonly ApiRequestLogTypeRepository $apiRequestLogTypeRepository,
        private readonly ApiKeyCreditsDayRepository $apiKeyCreditsDayRepository,
        private readonly ApiKeyCreditsMonthRepository $apiKeyCreditsMonthRepository,
    )
    {
        $request = $requestStack->getCurrentRequest();

        if (is_null($request)) {
            throw new LogicException('Unable to get the current request.');
        }

        $this->request = $request;
    }

    /**
     * Returns the API key.
     *
     * @return string
     */
    protected function getApiKey(): string
    {
        return ApiKey::PUBLIC_KEY;
    }

    /**
     * @return bool
     */
    public function isRequestAccepted(): bool
    {
        $this->initialized = true;

        $endpoint = $this->getApiEndpoint();
        $method = $this->request->getMethod();

        $this->apiEndpoint = $this->apiEndpointRepository->findOneBy(['endpoint' => $endpoint, 'method' => $method]);

        if (is_null($this->apiEndpoint)) {
            $this->apiRequestLogTypeValue = ApiRequestLogType::FAILED_404;
            $this->setErrorLast(sprintf('The endpoint "%s %s" is not registered.', $method, $endpoint));
            return false;
        }

        $this->apiKey = $this->apiKeyRepository->findOneBy(['apiKey' => $this->getApiKey()]);

        if (is_null($this->apiKey)) {
            $this->apiRequestLogTypeValue = ApiRequestLogType::FAILED_401;
            $this->setErrorLast('The given API key was not found.');
            return false;
        }

        /* IP protection. */
        if (!$this->checkIpLimits($this->apiKey, $this->apiEndpoint)) {
            $this->apiRequestLogTypeValue = ApiRequestLogType::FAILED_429;
            $this->setErrorLast('The limit for the given API key has been reached. Please try again later.');
            return false;
        }

        $credits = $this->apiEndpoint->getCredits();

        /* No credits needed. */
        if (is_null($credits) || $credits <= 0) {
            return true;
        }

        /* Check if the credit points are used up. */
        if (!$this->checkCreditPoints($this->apiKey, $credits)) {
            $this->apiRequestLogTypeValue = ApiRequestLogType::FAILED_429;
            $this->setErrorLast('The limit for the given API key has been reached. Please try again later.');
            return false;
        }

        return true;
    }

    /**
     * Checks the ip rate limit.
     *
     * @param ApiKeyEntity $apiKey
     * @param ApiEndpointEntity $apiEndpoint
     * @return bool
     */
    private function checkIpLimits(ApiKeyEntity $apiKey, ApiEndpointEntity $apiEndpoint): bool
    {
        /* We don't have an ip limit. Cancel. */
        if (!$apiKey->hasIpLimit()) {
            return true;
        }

        if ($apiEndpoint->getCredits() <= 0) {
            return true;
        }

        $limitsPerMinute = $apiKey->getLimitsPerMinute();
        $limitsPerHour = $apiKey->getLimitsPerHour();

        if (is_null($limitsPerMinute) && is_null($limitsPerHour)) {
            throw new LogicException('The ip limit configuration is wrong. Please check your configuration.');
        }

        $ip = $this->getIp();

        /* Check limit per minute. */
        if (!is_null($limitsPerMinute)) {
            $count = $this->apiRequestLogRepository->countIpLogsLastMinute($apiKey, $ip);

            if ($count >= $limitsPerMinute) {
                return false;
            }
        }

        /* Check limit per hour. */
        if (!is_null($limitsPerHour)) {
            $count = $this->apiRequestLogRepository->countIpLogsLastHour($apiKey, $ip);

            if ($count >= $limitsPerHour) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks the available credit points.
     *
     * @param ApiKeyEntity $apiKey
     * @param int $credits
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function checkCreditPoints(ApiKeyEntity $apiKey, int $credits): bool
    {
        /* We don't have a credit point limit. Cancel. */
        if (!$apiKey->hasCredentialLimit()) {
            return true;
        }

        $creditsPerDay = $apiKey->getCreditsPerDay();
        $creditsPerMonth = $apiKey->getCreditsPerMonth();

        if (is_null($creditsPerDay) && is_null($creditsPerMonth)) {
            throw new LogicException('The credit point configuration is wrong. Please check your configuration.');
        }

        if (!is_null($creditsPerDay)) {
            $day = (new DateTimeImmutable())->setTime(0, 0);

            $this->apiKeyCreditsDay = $this->apiKeyCreditsDayRepository->findOneBy(['apiKey' => $apiKey, 'day' => $day]);

            if (is_null($this->apiKeyCreditsDay)) {
                $this->apiKeyCreditsDay = (new ApiKeyCreditsDayEntity())
                    ->setCreditsUsed(0)
                    ->setApiKey($apiKey)
                    ->setDay($day)
                ;

                $this->entityManager->persist($this->apiKeyCreditsDay);
                $this->entityManager->flush();
            }

            $creditsUsed = $this->apiKeyCreditsDay->getCreditsUsed();

            if (is_null($creditsUsed)) {
                throw new LogicException('Unexpected value type for credits used.');
            }

            if ($credits + $creditsUsed > $creditsPerDay) {
                return false;
            }
        }

        if (!is_null($creditsPerMonth)) {
            $month = (new DateTimeImmutable('first day of this month'))->setTime(0, 0);

            $this->apiKeyCreditsMonth = $this->apiKeyCreditsMonthRepository->findOneBy(['apiKey' => $apiKey, 'month' => $month]);

            if (is_null($this->apiKeyCreditsMonth)) {
                $this->apiKeyCreditsMonth = (new ApiKeyCreditsMonthEntity())
                    ->setCreditsUsed(0)
                    ->setApiKey($apiKey)
                    ->setMonth($month)
                ;

                $this->entityManager->persist($this->apiKeyCreditsMonth);
                $this->entityManager->flush();
            }

            $creditsUsed = $this->apiKeyCreditsMonth->getCreditsUsed();

            if (is_null($creditsUsed)) {
                throw new LogicException('Unexpected value type for credits used.');
            }

            if ($credits + $creditsUsed > $creditsPerMonth) {
                return false;
            }
        }

        return true;
    }

    /**
     * Increases the credits for day and month.
     *
     * @param ResourceWrapperCustom $resourceWrapper
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function increaseCredits(ResourceWrapperCustom $resourceWrapper): void
    {
        if (!$this->initialized) {
            throw new LogicException('Please execute the "accepted()" method first.');
        }

        if ($this->apiKey === false || $this->apiEndpoint === false) {
            return;
        }

        /* We don't have an api key: Unable to log. */
        if (is_null($this->apiKey) || is_null($this->apiEndpoint)) {
            return;
        }

        /* Do not increase failed requests. */
        if (!$resourceWrapper->isValid()) {
            return;
        }

        $credits = $this->apiEndpoint->getCredits();

        /* Do not increase api request with free credits. */
        if (is_null($credits) || $credits <= 0) {
            return;
        }

        /* Do not increase api requests without credits (public api key requests). */
        if (is_null($this->apiKeyCreditsDay) && is_null($this->apiKeyCreditsMonth)) {
            return;
        }

        $entityChanged = false;

        if (!is_null($this->apiKeyCreditsDay)) {
            $this->apiKeyCreditsDay->increaseCreditsUsedBy($credits);
            $this->entityManager->persist($this->apiKeyCreditsDay);
            $entityChanged = true;
        }

        if (!is_null($this->apiKeyCreditsMonth)) {
            $this->apiKeyCreditsMonth->increaseCreditsUsedBy($credits);
            $this->entityManager->persist($this->apiKeyCreditsMonth);
            $entityChanged = true;
        }

        if ($entityChanged) {
            $this->entityManager->flush();
        }
    }


    /**
     * Logs the request.
     *
     * @param ResourceWrapperCustom $resourceWrapper
     * @param array<int|string, bool|int|string>|null $givenRaw
     * @return void
     */
    public function log(ResourceWrapperCustom $resourceWrapper, array|null $givenRaw = null): void
    {
        if (!$this->initialized) {
            throw new LogicException('Please execute the "accepted()" method first.');
        }

        if ($this->apiKey === false || $this->apiEndpoint === false) {
            return;
        }

        /* We don't have an api key: Unable to log. */
        if (is_null($this->apiKey) || is_null($this->apiEndpoint)) {
            return;
        }

        $apiRequestLogTypeValue = match (true) {
            !is_null($this->apiRequestLogTypeValue) => $this->apiRequestLogTypeValue,
            !is_null($resourceWrapper->getError()) => ApiRequestLogType::FAILED_406,
            default => ApiRequestLogType::SUCCESS_200,
        };

        $apiRequestLogType = $this->apiRequestLogTypeRepository->findOneBy(['type' => $apiRequestLogTypeValue]);

        if (is_null($apiRequestLogType)) {
            throw new LogicException('Unable to get the API request log type.');
        }

        $credits = $this->getCreditsUsed($this->apiKey, $this->apiEndpoint);

        $valid = in_array($apiRequestLogTypeValue, ApiRequestLogType::ALL_SUCCESS);

        $error = match ($valid) {
            true => null,
            default => $this->getErrorLast(),
        };

        $apiRequestLog = (new ApiRequestLogEntity())
            ->setApiKey($this->apiKey)
            ->setApiEndpoint($this->apiEndpoint)
            ->setApiRequestLogType($apiRequestLogType)
            ->setCreditsUsed($credits)
            ->setIp($this->getIp())
            ->setBrowser($this->request->headers->get('User-Agent') ?? '')
            ->setReferrer($this->request->headers->get('Referer') ?? '')
            ->setGiven($givenRaw ?? $resourceWrapper->getGiven())
            ->setValid($valid)
            ->setError($error)
            ->setTimeTaken($resourceWrapper->getTimeTakenValue())
            ->setMemoryTaken($resourceWrapper->getMemoryTakenValue())
        ;

        $this->entityManager->persist($apiRequestLog);
        $this->entityManager->flush();
    }

    /**
     * Returns the used credits for current request.
     *
     * @param ApiKeyEntity $apiKey
     * @param ApiEndpointEntity $apiEndpoint
     * @return int|null
     */
    private function getCreditsUsed(ApiKeyEntity $apiKey, ApiEndpointEntity $apiEndpoint): int|null
    {
        if (!$this->initialized) {
            throw new LogicException('Please execute the "accepted()" method first.');
        }

        if (is_null($apiKey->getCreditsPerDay()) && is_null($apiKey->getCreditsPerMonth())) {
            return null;
        }

        return $apiEndpoint->getCredits();
    }

    /**
     * Returns the api endpoint.
     *
     * @return string
     */
    private function getApiEndpoint(): string
    {
        $operation = $this->request->attributes->get('_api_operation');

        if (!$operation instanceof HttpOperation) {
            throw new LogicException('HttpOperation expected.');
        }

        $apiEndpoint = str_replace('{._format}', '', $operation->getUriTemplate() ?? '');

        $apiEndpoint = '/'.ltrim($apiEndpoint, '/');

        $prefix = $operation->getRoutePrefix();

        if (!is_null($prefix)) {
            $apiEndpoint = $prefix.$apiEndpoint;
        }

        return $apiEndpoint;
    }

    /**
     * @return string
     */
    private function getIp(): string
    {
        $ip = $this->request->getClientIp();

        if (is_null($ip)) {
            throw new LogicException('Unable to determine the ip address.');
        }

        return $ip;
    }

    /**
     * @return string|null
     */
    public function getErrorLast(): ?string
    {
        return $this->errorLast;
    }

    /**
     * @param string|null $errorLast
     * @return self
     */
    public function setErrorLast(?string $errorLast): self
    {
        $this->errorLast = $errorLast;

        return $this;
    }


}
