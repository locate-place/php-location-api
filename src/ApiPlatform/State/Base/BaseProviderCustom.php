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

namespace App\ApiPlatform\State\Base;

use ApiPlatform\Metadata\Operation;
use App\ApiPlatform\OpenApiContext\Name;
use App\ApiPlatform\Resource\Base;
use App\ApiPlatform\Resource\Location;
use App\Constants\Key\KeyArray;
use App\Constants\Language\CountryCode;
use App\Constants\Language\LanguageCode;
use App\Constants\Language\LocaleCode;
use App\Constants\Place\Search;
use App\Entity\Location as LocationEntity;
use App\Exception\QueryParserException;
use App\Repository\LocationRepository;
use App\Service\LocationService;
use App\Utils\Api\ApiLogger;
use App\Utils\Performance\PerformanceLogger;
use App\Utils\Query\Query;
use App\Utils\Query\QueryParser;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Version as VersionOrigin;
use Ixnode\PhpApiVersionBundle\ApiPlatform\State\Base\Wrapper\BaseResourceWrapperProvider;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use Ixnode\PhpTimezone\Constants\Language;
use JsonException;
use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class BaseProviderCustom
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-04)
 * @since 0.1.0 (2023-07-04) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BaseProviderCustom extends BaseResourceWrapperProvider
{
    protected readonly Query $query;

    /** @var array<int|string, mixed>|null $results */
    private array|null $results = null;

    protected const GET_COLLECTION = 'GET_COLLECTION';

    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $requestStack
     * @param LocationService $locationService
     * @param TranslatorInterface $translator
     * @param EntityManagerInterface $entityManager
     * @param ApiLogger $apiLogger
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
    )
    {
        parent::__construct($version, $parameterBag, $requestStack);

        $this->query = new Query($this->getRequest());
    }

    /**
     * Protection of some given variables that should not be returned.
     *
     * @inheritdoc
     */
    protected function getProtectedValues(): array
    {
        return [
            Name::API_KEY_HEADER
        ];
    }

    /**
     * Replaces the provide function and add the execution time from getResourceWrapper also to
     * overall time.
     *
     * @param Operation $operation
     * @param array<string, mixed> $uriVariables
     * @param array<int|string, mixed> $context
     * @return object|array|object[]|null
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws ORMException
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('provide-custom');

        $accepted = $this->apiLogger->isRequestAccepted();

        switch ($accepted) {
            /* API access accepted. */
            case true:
                /** @var ResourceWrapperCustom $resourceWrapper */
                $resourceWrapper = parent::provide($operation, $uriVariables, $context);
                break;

            /* API access is not accepted. */
            default:
                /* Set context, etc. because parent::provide() is not called in this case. */
                $this->prepare($operation, $uriVariables, $context);

                $resourceWrapper = new ResourceWrapperCustom();

                $resourceWrapper->setGiven($this->getUriVariablesOutput(true));
                $resourceWrapper
                    ->setData([])
                    ->setError($this->apiLogger->getErrorLast() ?? '')
                    ->setValid(false)
                    ->setDate(new DateTimeImmutable())
                    ->setVersion($this->version->getVersion())
                ;

                $this->prepareRessourceWrapper($resourceWrapper);
                break;
        }

        $event = $stopwatch->stop('provide-custom');

        $memoryTaken = sprintf('%.2f MB', memory_get_usage() / 1024 / 1024);
        $timeTaken = sprintf('%.0fms', $event->getDuration());

        $resourceWrapper
            ->setMemoryTaken($memoryTaken)
            ->setTimeTaken($timeTaken)
        ;

        if ($this->hasResults()) {
            $resourceWrapper->setResults($this->getResults());
        }

        $this->apiLogger->increaseCredits($resourceWrapper);
        $this->apiLogger->log($resourceWrapper, $this->getUriVariablesOutputRaw());

        $data = $resourceWrapper->getData();

        /* Return version data directly. */
        if ($data instanceof VersionOrigin) {
            return $data;
        }

        return $resourceWrapper;
    }

    /**
     * Returns the custom resource wrapper:
     *
     * - Add some additional data to ResourceWrapper (memory-taken, data-licence, etc.).
     *
     * @param BasePublicResource|BasePublicResource[] $baseResource
     * @param string $timeTaken
     * @return ResourceWrapperCustom
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     */
    protected function getResourceWrapper(BasePublicResource|array $baseResource, string $timeTaken): ResourceWrapperCustom
    {
        $resourceWrapper = parent::getResourceWrapper($baseResource, $timeTaken);

        $resourceWrapperNew = new ResourceWrapperCustom();

        /* Show schema */
        if ($this->isSchemaByFilter()) {
            $data = $resourceWrapper->getData();

            if (!$data instanceof Base) {
                throw new LogicException(sprintf('The data part must be an instance of "%s".', Base::class));
            }

            $resourceWrapperNew
                ->setSchema($data->getAll())
                ->setDate($resourceWrapper->getDate())
                ->setVersion($resourceWrapper->getVersion())
            ;
        }

        $resourceWrapperNew
            ->setPerformance(PerformanceLogger::getInstance()->getPerformanceData()->getArray())
            ->setGiven($resourceWrapper->getGiven())
            ->setDate($resourceWrapper->getDate())
            ->setVersion($resourceWrapper->getVersion())
            ->setData($resourceWrapper->getData())
        ;

        $this->prepareRessourceWrapper($resourceWrapperNew);

        $error = $resourceWrapper->getError();

        if (!is_null($error)) {
            $resourceWrapperNew->setError($error)->setValid(false);
        }

        return $resourceWrapperNew;
    }

    /**
     * Adds data licenses, etc.
     *
     * @param ResourceWrapperCustom $resourceWrapper
     * @return void
     * @throws TypeInvalidException
     */
    protected function prepareRessourceWrapper(ResourceWrapperCustom $resourceWrapper): void
    {
        /* Add data licence. */
        $resourceWrapper->setDataLicence([
            'full' => (new TypeCastingHelper($this->parameterBag->get('data_license_full')))->strval(),
            'short' => (new TypeCastingHelper($this->parameterBag->get('data_license_short')))->strval(),
            'url' => (new TypeCastingHelper($this->parameterBag->get('data_license_url')))->strval(),
        ]);
    }

    /**
     * Extends the getUriVariablesOutput method.
     *
     * @param bool $doNotTranslate
     * @return array<int|string, array<string, mixed>|bool|int|string|null>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws ORMException
     * @throws ParserException
     * @throws QueryParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function getUriVariablesOutput(bool $doNotTranslate = false): array
    {
        $uriVariablesOutput = parent::getUriVariablesOutput();

        if ($doNotTranslate) {
            return $uriVariablesOutput;
        }

        /* Add query information */
        if ($this->isLocationRequest() && array_key_exists(KeyArray::QUERY, $uriVariablesOutput)) {
            $givenQueryArray = $this->getGivenQueryArray();

            switch (true) {
                case !is_null($givenQueryArray):
                    $uriVariablesOutput[KeyArray::QUERY] = $givenQueryArray;
                    break;

                default:
                    unset($uriVariablesOutput[KeyArray::QUERY]);
                    break;
            }
        }
        if ($this->isAutocompleteRequest() && array_key_exists(KeyArray::QUERY, $uriVariablesOutput)) {
            $uriVariablesOutput[KeyArray::QUERY] = $this->getGivenQueryArray();
        }

        /* Add limit information */
        $limit = $this->query->getQueryParser()?->getLimit() ?? null;
        if (!is_null($limit)) {
            $uriVariablesOutput[KeyArray::LIMIT] = $limit;
        }

        /* Add distance information */
        $distance = $this->query->getQueryParser()?->getDistance() ?? null;
        if (!is_null($distance)) {
            $uriVariablesOutput[KeyArray::DISTANCE] = $distance;
        }

        /* Add coordinate information. */
        if (array_key_exists(KeyArray::COORDINATE, $uriVariablesOutput)) {
            $uriVariablesOutput[KeyArray::COORDINATE] = $this->getGivenCoordinateArray($uriVariablesOutput);
        }

        if (array_key_exists('language', $uriVariablesOutput)) {
            $coordinateString = (new TypeCastingHelper($uriVariablesOutput['language']))->strval();

            $languageValues = array_key_exists($coordinateString, Language::LANGUAGE_ISO_639_1) ?
                Language::LANGUAGE_ISO_639_1[$coordinateString] :
                null
            ;

            $uriVariablesOutput['language'] = [
                'raw' => $coordinateString,
                'parsed' => [
                    'name' => !is_null($languageValues) ? $languageValues['en'] : 'n/a',
                ]
            ];
        }

        if (array_key_exists('country', $uriVariablesOutput)) {
            $country = (new TypeCastingHelper($uriVariablesOutput['country']))->strval();

            $uriVariablesOutput['country'] = [
                'raw' => $country,
                'parsed' => [
                    'name' => $country,
                ]
            ];
        }

        if ($this->isLocationRequest() && !array_key_exists(KeyArray::NEXT_PLACES, $uriVariablesOutput)) {
            $uriVariablesOutput[KeyArray::NEXT_PLACES] = false;
        }

        if (array_key_exists(KeyArray::GEONAME_ID, $uriVariablesOutput) && $uriVariablesOutput[KeyArray::GEONAME_ID] === 0) {
            unset($uriVariablesOutput[KeyArray::GEONAME_ID]);
        }

        return $uriVariablesOutput;
    }

    /**
     * Returns the untranslated raw given variables output.
     *
     * @return array<int|string, bool|int|string>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     */
    protected function getUriVariablesOutputRaw(): array
    {
        return parent::getUriVariablesOutput();
    }

    /**
     * Returns the given query array.
     *
     * @return array<string, mixed>|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    private function getGivenQueryArray(): array|null
    {
        $queryParser = $this->query->getQueryParser();

        if (is_null($queryParser)) {
            return null;
        }

        return $queryParser->get($this->translator, LanguageCode::DE);
    }

    /**
     * Returns the coordinate given array.
     *
     * @param array<int|string, array<string, mixed>|bool|int|string|null> $uriVariablesOutput
     * @return array<string, mixed>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     * @throws ORMException
     */
    private function getGivenCoordinateArray(array $uriVariablesOutput): array
    {
        $coordinateString = $uriVariablesOutput[KeyArray::COORDINATE];

        if (!is_string($coordinateString)) {
            throw new LogicException(sprintf('The coordinate string must be a string. "%s" given.', gettype($coordinateString)));
        }

        $coordinate = new Coordinate($coordinateString);

        return [
            KeyArray::RAW => $coordinateString,
            KeyArray::PARSED => [
                KeyArray::LATITUDE => [
                    KeyArray::DECIMAL => $coordinate->getLatitudeDecimal(),
                    KeyArray::DMS => $coordinate->getLatitudeDMS(),
                ],
                KeyArray::LONGITUDE => [
                    KeyArray::DECIMAL => $coordinate->getLongitudeDecimal(),
                    KeyArray::DMS => $coordinate->getLongitudeDMS(),
                ],
                KeyArray::LINKS => [
                    KeyArray::GOOGLE => $coordinate->getLinkGoogle(),
                    KeyArray::OPENSTREETMAP => $coordinate->getLinkOpenStreetMap(),
                ]
            ],
            KeyArray::LOCATION => $this->getLocation($coordinate),
        ];
    }

    /**
     * Returns the location from given coordinate.
     *
     * @param Coordinate $coordinate
     * @return Location|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ORMException
     */
    private function getLocation(Coordinate $coordinate): Location|null
    {
        $country = $this->query->getFilterAsString(Query::FILTER_COUNTRY, CountryCode::US);
        $isoLanguage = $this->query->getFilterAsString(Query::FILTER_LANGUAGE, LanguageCode::EN);

        $location = $this->locationService->getLocationByCoordinate(
            /* Search */
            coordinate: $coordinate,

            /* Search filter */
            /* --- no filter --- */

            /* Configuration */
            isoLanguage: $isoLanguage,
            country: $country,
            addLocations: true,
            addNextPlacesConfig: true
        );

        if ($this->locationService->hasError()) {
            $this->setError($this->locationService->getError());
            return null;
        }

        return $location;
    }

    /**
     * Returns the Coordinate object from url parameters.
     *
     * @return Coordinate|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    protected function getCoordinateByFilter(): Coordinate|null
    {
        if (!$this->hasFilter(Name::COORDINATE)) {
            return null;
        }

        $coordinate = $this->getFilterString(Name::COORDINATE);

        return new Coordinate($coordinate);
    }

    /**
     * Returns the iso language from url parameters.
     *
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function getIsoLanguageByFilter(): string
    {
        if (!$this->hasFilter(Name::LANGUAGE)) {
            return LanguageCode::EN;
        }

        $isoLanguage = $this->getFilterString(Name::LANGUAGE);

        return strtolower($isoLanguage);
    }

    /**
     * Returns the country from url parameters.
     *
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function getCountryByFilter(): string
    {
        if (!$this->hasFilter(Name::COUNTRY)) {
            return CountryCode::US;
        }

        $country = $this->getFilterString(Name::COUNTRY);

        return strtoupper($country);
    }

    /**
     * Returns whether next places should be added.
     *
     * @return bool
     * @throws TypeInvalidException
     */
    protected function isNextPlacesByFilter(): bool
    {
        if (!$this->hasFilter(Name::NEXT_PLACES)) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether schema should be displayed.
     *
     * @return bool
     * @throws TypeInvalidException
     */
    protected function isSchemaByFilter(): bool
    {
        if (!$this->hasFilter(Name::SCHEMA)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the locale from url parameters.
     *
     * @param string|null $isoLanguage
     * @param string|null $country
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function getLocaleByFilter(string|null $isoLanguage = null, string|null $country = null): string
    {
        if ($this->hasFilter(Name::LOCALE)) {
            $locale = $this->getFilter(Name::LOCALE);

            if (!is_string($locale)) {
                throw new LogicException('Locale must be a string.');
            }

            return $locale;
        }

        if (is_null($isoLanguage)) {
            $isoLanguage = $this->getIsoLanguageByFilter();
        }

        if (is_null($country)) {
            $country = $this->getCountryByFilter();
        }

        return sprintf('%s_%s', $isoLanguage, $country);
    }

    /**
     * Return the language by given locale.
     *
     * @param string $locale
     * @return string
     */
    protected function getLanguageByLocale(string $locale): string
    {
        [$language] = explode('_', $locale);

        return $language;
    }

    /**
     * Returns the iso language and country given by filter.
     *
     * @return array{iso-language: string|null, country: string|null}
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function getIsoLanguageAndCountryByFilter(): array
    {
        $isoLanguage = $this->getIsoLanguageByFilter();
        $country = $this->getCountryByFilter();
        $locale = $this->getLocaleByFilter($isoLanguage, $country);

        if (!in_array($locale, LocaleCode::ALL)) {
            $this->setError(sprintf('Locale "%s" is not supported yet. Please choose on of them: %s', $locale, implode(', ', LocaleCode::ALL)));
            return [
                KeyArray::ISO_LANGUAGE => null,
                KeyArray::COUNTRY => null,
            ];
        }

        [
            $isoLanguage,
            $country
        ] = explode('_', $locale);

        return [
            KeyArray::ISO_LANGUAGE => $isoLanguage,
            KeyArray::COUNTRY => $country,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function endpointContains(string $word): bool
    {
        $pathInfo = explode('/', $this->getRequest()->getPathInfo());

        foreach ($pathInfo as $pathInfoPart) {
            if (preg_match(sprintf('~^%s$~', $word), $pathInfoPart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns some geoname id examples.
     *
     * @return int[]
     */
    protected function getGeonameIdsExample(): array
    {
        $geonameIds = [];

        foreach (array_keys(Search::VALUES) as $key) {
            $search = new Search($key);

            $geonameIds[] = $search->getGeonameId();
        }

        return $geonameIds;
    }

    /**
     * Returns some geoname ids for capitals of the world.
     *
     * @return int[]
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    protected function getGeonameIdsCapitals(): array
    {
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->entityManager->getRepository(LocationEntity::class);

        return array_filter(
            array_map(fn(LocationEntity $location) => $location->getGeonameId(), $locationRepository->findCapitals()),
            fn($geonameId) => !is_null($geonameId)
        );
    }

    /**
     * Returns some geoname id airport.
     *
     * @return int[]
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    protected function getGeonameIdsAirport(): array
    {
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->entityManager->getRepository(LocationEntity::class);

        return array_filter(
            array_map(fn(LocationEntity $location) => $location->getGeonameId(), $locationRepository->findAirports()),
            fn($geonameId) => !is_null($geonameId)
        );
    }

    /**
     * Returns the full name array.
     *
     * @return array<int, string>
     */
    protected function getNamesFull(): array
    {
        $namesFull = [];

        foreach (array_keys(Search::VALUES) as $key) {
            $search = new Search($key);

            $namesFull[$search->getGeonameId()] = $search->getNameFull();
        }

        return $namesFull;
    }

    /**
     * @return bool
     */
    protected function hasResults(): bool
    {
        return !is_null($this->results);
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function getResults(): array
    {
        if (is_null($this->results)) {
            throw new LogicException('Results must be an array.');
        }

        return $this->results;
    }

    /**
     * @param array<int|string, mixed> $results
     * @return void
     */
    protected function setResults(array $results): void
    {
        $this->results = $results;
    }

    /**
     * Sets the result from given location array.
     *
     * @param array<int, Location> $locations
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    protected function setResultsFromLocations(array $locations): void
    {
        if (!$this->locationService->hasResultCount()) {
            return;
        }

        $count = count($locations);
        $countTotal = $this->locationService->getResultCount();

        $limit = $this->query->getLimitDefault();

        $page = $this->query->getPageDefault();

        $this->setResults([
            KeyArray::RESULTS_CURRENT => $count,
            KeyArray::RESULTS_TOTAL => $countTotal,
            KeyArray::PAGE_CURRENT => $page,
            KeyArray::PAGE_SIZE => $limit,
        ]);
    }

    /**
     * Returns whether the request is a location request.
     *
     * @return bool
     */
    private function isLocationRequest(): bool
    {
        return str_contains($this->getRequest()->getPathInfo(), '/location');
    }

    /**
     * Returns whether the request is a autocomplete request.
     *
     * @return bool
     */
    private function isAutocompleteRequest(): bool
    {
        return str_contains($this->getRequest()->getPathInfo(), '/autocomplete');
    }

    /**
     * Returns whether the request contains a path.
     *
     * @param string $wordRegexp
     * @return bool
     */
    public function hasPath(string $wordRegexp): bool
    {
        $pathWords = explode('/', $this->getRequest()->getPathInfo());

        foreach ($pathWords as $pathWord) {
            if (preg_match(sprintf('~^%s$~', $wordRegexp), $pathWord)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tries to get the coordinate from the given query.
     *
     * @param QueryParser $queryParser
     * @param LocationRepository $locationRepository
     * @return Coordinate|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    protected function getCoordinateByQueryParser(QueryParser $queryParser, LocationRepository $locationRepository): Coordinate|null
    {
        $coordinate = $queryParser->getCoordinate();
        $geonameId = $queryParser->getGeonameId();

        return match (true) {
            $coordinate instanceof Coordinate => $coordinate,
            !is_null($geonameId) => $locationRepository->findOneBy(['geonameId' => $geonameId])?->getCoordinateIxnode(),
            default => null,
        };
    }
}
