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

namespace App\ApiPlatform\State\Base\Wrapper;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\Route\LocationRoute;
use App\ApiPlatform\State\LocationProvider;
use App\Utils\Version\Version;
use DateTimeImmutable;
use Exception;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Route\Base\BaseRoute;
use Ixnode\PhpApiVersionBundle\ApiPlatform\State\Base\BaseProvider;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class BaseResourceWrapperProvider
 *
 * Use this Provider to provide the data of the given BasePublicResource (doProvide) with additional API specific wrapper information:
 *
 * - data resource
 * - given resource
 * - valid state of request
 * - date of request
 * - time-taken for request
 * - version of API
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class BaseResourceWrapperProvider extends BaseProvider
{
    private Operation $operation;

    /** @var array<string, mixed> $uriVariables */
    private array $uriVariables = [];

    /** @var array<int|string, mixed> $context */
    private array $context = [];

    /** @var array<int|string, mixed> $validationDetails */
    private ?array $validationDetails = null;

    /** @var array<int|string, mixed> $enrichmentDetails */
    private ?array $enrichmentDetails = null;

    protected string $requestMethod;

    protected const METHOD_GET_COLLECTION = 'GET_COLLECTION';

    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request
    )
    {
        parent::__construct($parameterBag, $request);
    }

    /**
     * Returns the route properties according to current class.
     *
     * @return array<string, array<string, int|string|string[]>>
     * @throws CaseInvalidException
     */
    protected function getRouteProperties(): array
    {
        return match (static::class) {
            LocationProvider::class => LocationRoute::PROPERTIES,
            default => throw new CaseInvalidException(static::class, [])
        };
    }

    /**
     * @inheritdoc
     * @param Operation $operation
     * @param array<string, mixed> $uriVariables
     * @param array<int|string, mixed> $context
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $stopwatch = new Stopwatch();

        $stopwatch->start('provide');

        $this->setOperation($operation);
        $this->setUriVariables($uriVariables);
        $this->setContext($context);

        $this->setRequestMethodFromContext($operation);

        $baseResource = $this->doProvide();

        $event = $stopwatch->stop('provide');

        $timeTaken = sprintf('%.0fms', $event->getDuration());

        /* @phpstan-ignore-next-line */
        return $this->getResourceWrapper($baseResource, $timeTaken);
    }

    /**
     * @param mixed $data
     * @param Operation $operation
     * @param array<string, mixed> $uriVariables
     * @param array<int|string, mixed> $context
     * @return ResourceWrapper
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ResourceWrapper
    {
        $stopwatch = new Stopwatch();

        $stopwatch->start('process');

        $this->setOperation($operation);
        $this->setUriVariables($uriVariables);
        $this->setContext($context);

        $this->setRequestMethodFromContext($operation);

        $baseResource = $this->doProcess();

        $event = $stopwatch->stop('process');

        $timeTaken = sprintf('%.0fms', $event->getDuration());

        return $this->getResourceWrapper($baseResource, $timeTaken);
    }

    /**
     * @return Operation
     */
    protected function getOperation(): Operation
    {
        return $this->operation;
    }

    /**
     * @param Operation $operation
     * @return void
     */
    private function setOperation(Operation $operation): void
    {
        $this->operation = $operation;
    }

    /**
     * Gets the uri variables.
     *
     * @return array<string, mixed>
     */
    protected function getUriVariables(): array
    {
        return $this->uriVariables;
    }

    /**
     * Sets the uri variables.
     *
     * @param array<string, mixed> $uriVariables
     * @return void
     */
    private function setUriVariables(array $uriVariables): void
    {
        $this->uriVariables = $uriVariables;
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array<int|string, mixed> $context
     * @return void
     */
    private function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * Returns the request body as string.
     *
     * @return string|null
     * @throws CaseUnsupportedException
     */
    protected function getRequestBody(): ?string
    {
        $currentRequest = $this->getCurrentRequest();

        $requestBody = $currentRequest->getContent();

        if (empty($requestBody)) {
            return null;
        }

        return strval($requestBody);
    }

    /**
     * Returns the request body as JSON object.
     *
     * @return Json|null
     * @throws CaseUnsupportedException
     * @throws JsonException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     */
    protected function getRequestBodyAsJson(): ?Json
    {
        $requestBody = $this->getRequestBody();

        if ($requestBody === null) {
            return null;
        }

        return new Json($requestBody);
    }

    /**
     * Sets the request method from the context.
     *
     * @param Operation $operation
     * @return void
     * @throws CaseUnsupportedException
     */
    private function setRequestMethodFromContext(Operation $operation): void
    {
        $this->requestMethod = match (true) {
            $operation instanceof Get => Request::METHOD_GET,
            $operation instanceof GetCollection => self::METHOD_GET_COLLECTION,
            $operation instanceof Post => Request::METHOD_POST,
            $operation instanceof Patch => Request::METHOD_PATCH,
            default => throw new CaseUnsupportedException('request method'),
        };
    }

    /**
     * Returns the request method.
     *
     * @return string
     */
    protected function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    /**
     * Returns the resource wrapper.
     *
     * @param BasePublicResource|BasePublicResource[] $baseResource
     * @param string $timeTaken
     * @return ResourceWrapper
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    protected function getResourceWrapper(BasePublicResource|array $baseResource, string $timeTaken): ResourceWrapper
    {
        $resourceWrapper = (new ResourceWrapper())
            ->setGiven($this->getUriVariablesOutput())
            ->setDate(new DateTimeImmutable())
            ->setTimeTaken($timeTaken)
            ->setVersion($this->version->getVersion());

        if (!$this->isValid()) {
            $resourceWrapper
                ->setValid(false)
                ->setError(strval($this->getError()));

            /* Add validation message stream. */
            if (!is_null($this->getValidationDetails())) {
                $resourceWrapper->setValidationDetails($this->getValidationDetails());
            }

            /* Add enrichment message stream. */
            if (!is_null($this->getEnrichmentDetails())) {
                $resourceWrapper->setEnrichmentDetails($this->getEnrichmentDetails());
            }
        }

        $resourceWrapper->setData($baseResource);

        return $resourceWrapper;
    }

    /**
     * Returns the protected uri variables.
     *
     * @return array<int, string>
     */
    protected function getProtectedValues(): array
    {
        return [];
    }

    /**
     * Gets the uri variables.
     *
     * @return array<int|string, bool|int|string>
     * @throws TypeInvalidException
     * @throws ClassInvalidException
     * @throws CaseUnsupportedException
     * @throws CaseInvalidException
     * @throws ArrayKeyNotFoundException
     */
    protected function getUriVariablesOutput(): array
    {
        $variablesOutput = [];

        $protectedValues = $this->getProtectedValues();

        foreach ($this->getRouteProperties() as $name => $property) {

            switch (true) {
                case (in_array($name, $protectedValues)):
                    continue 2;

                case $this->hasUri($name):
                    $value = match(true) {
                        $property[BaseRoute::KEY_TYPE] === BaseRoute::TYPE_STRING,
                        $property[BaseRoute::KEY_TYPE] === BaseRoute::TYPE_ENUM_STRING => $this->getUriString($name),
                        $property[BaseRoute::KEY_TYPE] === BaseRoute::TYPE_INTEGER => $this->getUriInteger($name),
                        default => throw new TypeInvalidException(sprintf('BaseRoute::KEY_TYPE (%s)', $name)),
                    };
                    break;

                case $this->hasHeader($name):
                    $value = match(true) {
                        $property[BaseRoute::KEY_TYPE] === BaseRoute::TYPE_STRING,
                        $property[BaseRoute::KEY_TYPE] === BaseRoute::TYPE_ENUM_STRING => $this->getHeaderAsString($name),
                        $property[BaseRoute::KEY_TYPE] === BaseRoute::TYPE_INTEGER => $this->getHeaderAsInt($name),
                        $property[BaseRoute::KEY_TYPE] === BaseRoute::TYPE_BOOLEAN => $this->isHeaderAsBoolean($name),
                        default => throw new TypeInvalidException(sprintf('BaseRoute::KEY_TYPE (%s)', $name)),
                    };
                    break;

                case $this->hasFilter($name):
                    $value = match(true) {
                        $property[BaseRoute::KEY_TYPE] === BaseRoute::TYPE_STRING,
                        $property[BaseRoute::KEY_TYPE] === BaseRoute::TYPE_ENUM_STRING => $this->getFilterString($name),
                        $property[BaseRoute::KEY_TYPE] === BaseRoute::TYPE_INTEGER => $this->getFilterInteger($name),
                        default => throw new TypeInvalidException(sprintf('BaseRoute::KEY_TYPE (%s)', $name)),
                    };
                    break;

                /* Continue foreach loop */
                default:
                    continue 2;
            }

            $variablesOutput[(new TypeCastingHelper($property[BaseRoute::KEY_RESPONSE]))->strval()] = $value;
        }

        return $variablesOutput;
    }

    /**
     * Returns if the uri name exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasUri(string $name): bool
    {
        if (!array_key_exists($name, $this->uriVariables)) {
            return false;
        }

        return true;
    }

    /**
     * Returns an uri variable as string representation.
     *
     * @param string $name
     * @return mixed
     * @throws ArrayKeyNotFoundException
     */
    public function getUri(string $name): mixed
    {
        if (!array_key_exists($name, $this->uriVariables)) {
            throw new ArrayKeyNotFoundException($name);
        }

        $uri = $this->uriVariables[$name];

        if (is_string($uri)) {
            return urldecode($uri);
        }

        return $uri;
    }


    /**
     * Translate special uri strings.
     *
     * @param string $string
     * @return string
     */
    private function translateUriString(string $string): string
    {
        $translation = [
            '~point~' => '.',
        ];

        return str_replace(array_keys($translation), array_values($translation), $string);
    }

    /**
     * Returns an uri variable as string representation.
     *
     * @param string $name
     * @return string
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     */
    public function getUriString(string $name): string
    {
        return $this->translateUriString((new TypeCastingHelper($this->getUri($name)))->strval());
    }

    /**
     * Returns an uri variable as integer representation.
     *
     * @param string $name
     * @return int
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function getUriInteger(string $name): int
    {
        return (new TypeCastingHelper($this->getUri($name)))->intval();
    }

    /**
     * Returns if the filter name exists.
     *
     * @param string $name
     * @return bool
     * @throws TypeInvalidException
     */
    public function hasFilter(string $name): bool
    {
        if (!array_key_exists('filters', $this->context)) {
            return false;
        }

        $filters = $this->context['filters'];

        if (!is_array($filters)) {
            throw new TypeInvalidException('array', gettype($filters));
        }

        if (!array_key_exists($name, $filters)) {
            return false;
        }

        return true;
    }

    /**
     * Returns a filter variable as string representation.
     *
     * @param string $name
     * @return mixed
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    public function getFilter(string $name): mixed
    {
        if (!array_key_exists('filters', $this->context)) {
            throw new ArrayKeyNotFoundException('filters');
        }

        $filters = $this->context['filters'];

        if (!is_array($filters)) {
            throw new TypeInvalidException('array', gettype($filters));
        }

        if (!array_key_exists($name, $filters)) {
            throw new ArrayKeyNotFoundException($name);
        }

        $filter = $filters[$name];

        if (is_string($filter)) {
            return urldecode($filter);
        }

        return $filter;
    }

    /**
     * Returns a filter variable as string representation.
     *
     * @param string $name
     * @return string
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     */
    public function getFilterString(string $name): string
    {
        return $this->translateUriString((new TypeCastingHelper($this->getFilter($name)))->strval());
    }

    /**
     * Returns a filter variable as integer representation.
     *
     * @param string $name
     * @return int
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function getFilterInteger(string $name): int
    {
        return (new TypeCastingHelper($this->getFilter($name)))->intval();
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function getValidationDetails(): ?array
    {
        return $this->validationDetails;
    }

    /**
     * @return bool
     */
    public function hasValidationDetails(): bool
    {
        return !is_null($this->getValidationDetails());
    }

    /**
     * @param array<int|string, mixed> $validationDetails
     * @return self
     */
    public function setValidationDetails(array $validationDetails): self
    {
        $this->validationDetails = $validationDetails;

        return $this;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function getEnrichmentDetails(): ?array
    {
        return $this->enrichmentDetails;
    }

    /**
     * @return bool
     */
    public function hasEnrichmentDetails(): bool
    {
        return !is_null($this->getEnrichmentDetails());
    }

    /**
     * @param array<int|string, mixed> $enrichmentDetails
     * @return self
     */
    public function setEnrichmentDetails(array $enrichmentDetails): self
    {
        $this->enrichmentDetails = $enrichmentDetails;

        return $this;
    }

    /**
     * Returns if current resource is valid.
     *
     * @return bool
     */
    protected function isValid(): bool
    {
        return $this->error === null;
    }

    /**
     * Returns the endpoint.
     *
     * @return string
     * @throws CaseUnsupportedException
     */
    protected function getEndpoint(): string
    {
        $pathInfo = explode('/', $this->getCurrentRequest()->getPathInfo());

        return implode('/', array_slice($pathInfo, 3));
    }

    /**
     * Endpoint contains word.
     *
     * @param string $word
     * @return bool
     * @throws CaseUnsupportedException
     */
    protected function endpointContains(string $word): bool
    {
        $pathInfo = explode('/', $this->getCurrentRequest()->getPathInfo());

        return in_array($word, $pathInfo);
    }
}
