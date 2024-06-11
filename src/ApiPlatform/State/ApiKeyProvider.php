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

namespace App\ApiPlatform\State;

use App\ApiPlatform\OpenApiContext\Name;
use App\ApiPlatform\Resource\ApiKey;
use App\ApiPlatform\Route\ApiKeyRoute;
use App\ApiPlatform\State\Base\BaseProviderCustom;
use App\Repository\ApiKeyRepository;
use App\Service\LocationService;
use App\Utils\Api\ApiLogger;
use Doctrine\ORM\EntityManagerInterface;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ApiKeyProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-11)
 * @since 0.1.0 (2024-06-11) First version.
 */
final class ApiKeyProvider extends BaseProviderCustom
{
    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $requestStack
     * @param LocationService $locationService
     * @param TranslatorInterface $translator
     * @param EntityManagerInterface $entityManager
     * @param ApiLogger $apiLogger
     * @param ApiKeyRepository $apiKeyRepository
     * @throws CaseUnsupportedException
     */
    public function __construct(
        Version $version,
        ParameterBagInterface $parameterBag,
        RequestStack $requestStack,
        protected LocationService $locationService,
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $entityManager,
        protected ApiLogger $apiLogger,
        protected ApiKeyRepository $apiKeyRepository,
    )
    {
        parent::__construct(
            $version,
            $parameterBag,
            $requestStack,
            $locationService,
            $translator,
            $entityManager,
            $apiLogger,
        );
    }

    /**
     * Returns the route properties according to current class.
     *
     * @return array<string, array<string, int|string|string[]>>
     */
    protected function getRouteProperties(): array
    {
        return ApiKeyRoute::PROPERTIES;
    }

    /**
     * Returns an ApiKey ressource.
     *
     * @return BasePublicResource
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    private function doProvideGet(): BasePublicResource
    {
        $apiKeyResource = new ApiKey();

        $apiKey = $this->getHeaderAsStringOrNull(Name::API_KEY_HEADER);

        if (is_null($apiKey)) {
            $this->setError('The API key was not given.');
            return $apiKeyResource;
        }

        $apiKeyEntity = $this->apiKeyRepository->findOneBy(['apiKey' => $apiKey]);

        if (is_null($apiKeyEntity)) {
            $this->setError('The given API key does not exist.');
            return $apiKeyResource;
        }

        $apiKeyResource
            ->setIsEnabled($apiKeyEntity->isEnabled() ?? false)
            ->setIsPublic($apiKeyEntity->isPublic() ?? false)
            ->setHasIpLimit($apiKeyEntity->hasIpLimit() ?? false)
            ->setHasCredentialLimit($apiKeyEntity->hasCredentialLimit() ?? false)
        ;

        return $apiKeyResource;
    }

    /**
     * Do the provided job.
     *
     * @return BasePublicResource|BasePublicResource[]
     * @throws CaseUnsupportedException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    protected function doProvide(): BasePublicResource|array
    {
        return match($this->getRequestMethod()) {
            Request::METHOD_GET => $this->doProvideGet(),
            default => throw new CaseUnsupportedException('Unsupported mode from api endpoint /api/v1/import.'),
        };
    }
}
