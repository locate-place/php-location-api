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

namespace App\ApiPlatform\State;

use App\ApiPlatform\OpenApiContext\Name;
use App\ApiPlatform\Resource\Base;
use App\ApiPlatform\Resource\Location as LocationResource;
use App\ApiPlatform\Route\LocationRoute;
use App\ApiPlatform\State\Base\BaseProviderCustom;
use App\Constants\DB\Limit;
use App\Constants\Key\KeyArray;
use App\Repository\LocationRepository;
use App\Service\LocationService;
use App\Service\LocationServiceConfig;
use App\Utils\Query\Query;
use App\Utils\Query\QueryParser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\ApiPlatform\State\Base\Wrapper\BaseResourceWrapperProvider;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpContainer\File;
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
use JsonException;
use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class LocationProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class LocationProvider extends BaseProviderCustom
{
    private const FILE_SCHEMA_COORDINATE = 'data/json/schema/command/coordinate/resource.schema.json';

    private const SEARCH_MINIMUM_LENGTH = 3;

    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     * @param LocationRepository $locationRepository
     * @param TranslatorInterface $translator
     * @param LocationService $locationService
     * @param LocationServiceConfig $locationServiceConfig
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request,
        protected LocationRepository $locationRepository,
        protected TranslatorInterface $translator,
        protected LocationService $locationService,
        private readonly LocationServiceConfig $locationServiceConfig,
        protected EntityManagerInterface $entityManager
    )
    {
        parent::__construct($version, $parameterBag, $request, $locationService, $translator, $entityManager);
    }

    /**
     * Returns the route properties according to current class.
     *
     * @return array<string, array<string, bool|int|string>>
     */
    protected function getRouteProperties(): array
    {
        return LocationRoute::PROPERTIES;
    }

    /**
     * Returns a collection of location resources from examples, etc.:
     *
     * - https://www.location-api.localhost/api/v1/location/examples.json?language=de&country=DE
     * - https://www.location-api.localhost/api/v1/location/examples.json?language=de&country=DE&c=51.061002,13.740674
     *
     *  - https://www.location-api.localhost/api/v1/location/countries.json?language=de&country=DE
     *  - https://www.location-api.localhost/api/v1/location/countries.json?language=de&country=DE&c=51.061002,13.740674
     *
     *  - https://www.location-api.localhost/api/v1/location/airports.json?language=de&country=DE
     *  - https://www.location-api.localhost/api/v1/location/airports.json?language=de&country=DE&c=51.061002,13.740674
     *
     * @param int[] $geonameIds
     * @return BasePublicResource[]
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
     */
    private function doProvideGetCollectionByGeonameIds(array $geonameIds): array
    {
        [
            KeyArray::ISO_LANGUAGE => $isoLanguage,
            KeyArray::COUNTRY => $country,
        ] = $this->getIsoLanguageAndCountryByFilter();

        if (is_null($isoLanguage) || is_null($country)) {
            return [];
        }

        $currentPosition = $this->query->getCurrentPosition();

        $locations = $this->locationService->getLocationsByGeonameIds(
            /* Search */
            geonameIds: $geonameIds,

            /* Search filter */
            /* --- no filter --- */

            /* Configuration */
            currentPosition: $currentPosition,
            isoLanguage: $isoLanguage,
            country: $country,
            addLocations: true,
            addNextPlacesConfig: true,

            /* Sort configuration */
            sortBy: $this->query->getFilterAsString(
                Query::FILTER_SORT,
                !is_null($currentPosition) ? LocationService::SORT_BY_DISTANCE_USER : LocationService::SORT_BY_NAME
            ),

            /* Other configuration */
            namesFull: $this->getNamesFull()
        );

        if ($this->locationService->hasError()) {
            $this->setError($this->locationService->getError());
        }

        $this->setResultsFromLocations($locations);

        return $locations;
    }

    /**
     * Returns a collection of location resources that matches the given search string.
     *
     * @param QueryParser $queryParser
     * @return BasePublicResource[]
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
     */
    private function doProvideGetCollectionBySearch(QueryParser $queryParser): array
    {
        [
            KeyArray::ISO_LANGUAGE => $isoLanguage,
            KeyArray::COUNTRY => $country,
        ] = $this->getIsoLanguageAndCountryByFilter();

        if (is_null($isoLanguage) || is_null($country)) {
            return [];
        }

        $search = $queryParser->getSearch();

        if (is_null($search)) {
            $this->setError('Unable to get search string.');
            return [];
        }

        foreach ($search as $searchPart) {
            if (mb_strlen($searchPart) < self::SEARCH_MINIMUM_LENGTH) {
                $this->setError(sprintf('At least %s characters are required to search ("%s").', self::SEARCH_MINIMUM_LENGTH, $searchPart));
                return [];
            }
        }

        $currentPosition = $this->query->getCurrentPosition();

        $locations = $this->locationService->getLocationsBySearch(
            /* Search */
            search: $search,

            /* Search filter */
            featureClass: $queryParser->getFeatureClasses(),
            featureCode: $queryParser->getFeatureCodes(),
            limit: $this->hasFilter(Name::LIMIT) ? $this->getFilterInteger(Name::LIMIT) : Limit::LIMIT_10,
            page: $this->query->getPage(),

            /* Configuration */
            currentPosition: $currentPosition,
            isoLanguage: $isoLanguage,
            country: $country,
            addLocations: true,
            addNextPlaces: $this->isNextPlacesByFilter(),
            addNextPlacesConfig: true,

            /* Sort configuration */
            sortBy: $this->query->getFilterAsString(
                Query::FILTER_SORT,
                LocationService::SORT_BY_RELEVANCE
            ),
        );

        if ($this->locationService->hasError()) {
            $this->setError($this->locationService->getError());
        }

        $this->setResultsFromLocations($locations);

        return $locations;
    }

    /**
     * Returns a collection of location resources that matches the given coordinate.
     *
     * @param QueryParser $queryParser
     * @return BasePublicResource[]
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
     */
    private function doProvideGetCollectionByCoordinate(QueryParser $queryParser): array
    {
        [
            KeyArray::ISO_LANGUAGE => $isoLanguage,
            KeyArray::COUNTRY => $country,
        ] = $this->getIsoLanguageAndCountryByFilter();

        if (is_null($isoLanguage) || is_null($country)) {
            return [];
        }

        $coordinate = $queryParser->getCoordinate();

        if (is_null($coordinate)) {
            $this->setError('Unable to get coordinate from given data.');
            return [];
        }

        $locations = $this->locationService->getLocationsByCoordinate(
            /* Search */
            coordinate: $coordinate,

            /* Search filter */
            distanceMeter: $this->locationServiceConfig->getDistanceByQuery($this->query),
            featureClass: $queryParser->getFeatureClasses(),
            featureCode: $queryParser->getFeatureCodes(),
            limit: $this->locationServiceConfig->getLimitByQuery($this->query),
            page: $this->query->getPage(),

            /* Configuration */
            currentPosition: $this->query->getCurrentPosition(),
            isoLanguage: $isoLanguage,
            country: $country,
            addLocations: true,
            addNextPlaces: $this->isNextPlacesByFilter(),
            addNextPlacesConfig: true,

            /* Sort configuration */
            sortBy: $this->query->getFilterAsString(
                Query::FILTER_SORT,
                LocationService::SORT_BY_RELEVANCE
            ),
        );

        if ($this->locationService->hasError()) {
            $this->setError($this->locationService->getError());
        }

        $this->setResultsFromLocations($locations);

        return $locations;
    }

    /**
     * Returns a collection of location resources that matches the given geoname id (usually one result).
     *
     * @param QueryParser $queryParser
     * @return BasePublicResource[]
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
     */
    private function doProvideGetCollectionByGeonameId(QueryParser $queryParser): array
    {
        $location = $this->doProvideGetWithGeonameId($queryParser);

        if (!$location instanceof LocationResource) {
            return [];
        }

        if ($location->getGeonameId() === 0) {
            return [];
        }

        return [$location];
    }

    /**
     * Returns a location resource that matches the given geoname id.
     *
     * @param QueryParser $queryParser
     * @return BasePublicResource
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
     */
    private function doProvideGetWithGeonameId(QueryParser $queryParser): BasePublicResource
    {
        [
            KeyArray::ISO_LANGUAGE => $isoLanguage,
            KeyArray::COUNTRY => $country,
        ] = $this->getIsoLanguageAndCountryByFilter();

        if (is_null($isoLanguage) || is_null($country)) {
            return (new LocationResource())->setGeonameId(0);
        }

        $currentPosition = $this->query->getCurrentPosition();

        $geonameId = $queryParser->getGeonameId();

        if (is_null($geonameId)) {
            throw new LogicException('Unexpected behaviour: geoname id is null.');
        }

        $location = $this->locationService->getLocationByGeonameId(
            /* Search */
            geonameId: $geonameId,

            /* Search filter */
            /* --- no filter --- */

            /* Configuration */
            currentPosition: $currentPosition,
            isoLanguage: $isoLanguage,
            country: $country,
            addLocations: true,
            addNextPlaces: $this->isNextPlacesByFilter(),
            addNextPlacesConfig: true
        );

        if ($this->locationService->hasError()) {
            $this->setError($this->locationService->getError());
        }

        return $location;
    }

    /**
     * Returns a location resource that matches the given coordinate.
     *
     * @param QueryParser $queryParser
     * @return BasePublicResource
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
     */
    private function doProvideGetWithCoordinate(QueryParser $queryParser): BasePublicResource
    {
        [
            KeyArray::ISO_LANGUAGE => $isoLanguage,
            KeyArray::COUNTRY => $country,
        ] = $this->getIsoLanguageAndCountryByFilter();

        if (is_null($isoLanguage) || is_null($country)) {
            return (new LocationResource())->setGeonameId(0);
        }

        $coordinate = $queryParser->getCoordinate();

        if (is_null($coordinate)) {
            $this->setError('Unable to get coordinate from given data.');
            return (new LocationResource())->setGeonameId(0);
        }

        $location = $this->locationService->getLocationByCoordinate(
            /* Search */
            coordinate: $coordinate,

            /* Search filter */
            /* --- no filter --- */

            /* Configuration */
            currentPosition: $this->query->getCurrentPosition(),
            isoLanguage: $isoLanguage,
            country: $country,
            addLocations: true,
            addNextPlaces: $this->isNextPlacesByFilter(),
            addNextPlacesConfig: true
        );

        if ($this->locationService->hasError()) {
            $this->setError($this->locationService->getError());
        }

        return $location;
    }

    /**
     * Returns a location resource that matches the given coordinate.
     *
     * @return BasePublicResource
     * @throws ArrayKeyNotFoundException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function doProvideGetCollectionSchema(): BasePublicResource
    {
        /* TODO: Add collection schema. */

        return $this->doProvideGetWithCoordinateSchema();
    }

    /**
     * Returns a location resource that matches the given coordinate.
     *
     * @return BasePublicResource
     * @throws ArrayKeyNotFoundException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function doProvideGetWithCoordinateSchema(): BasePublicResource
    {
        $schemaFile = new File(self::FILE_SCHEMA_COORDINATE, $this->getProjectDir());

        $base = new Base();

        $base->setAll($schemaFile->getContentAsJson()->getArray());

        return $base;
    }

    /**
     * Do the provided job.
     *
     * @return BasePublicResource|BasePublicResource[]
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function doProvide(): BasePublicResource|array
    {
        /* Sets the uri variables from the routing system. */
        $this->query->setUriVariables($this->getUriVariables());

        $queryParser = $this->query->getQueryParser();

        if (!$queryParser instanceof QueryParser) {
            $this->setError('No query given.');
            return [];
        }

        switch (true) {

            /*
             * ---------
             * 0) Schema
             * ---------
             */

            /*
             * https://www.location-api.localhost/api/v1/location.json?schema
             */
            case $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION && $this->hasFilter(Name::SCHEMA):
                return $this->doProvideGetCollectionSchema();

            /*
             * https://www.location-api.localhost/api/v1/location/coordinate.json?schema
             */
            case $this->getRequestMethod() === Request::METHOD_GET && $this->hasFilter(Name::SCHEMA):
                return $this->doProvideGetWithCoordinateSchema();



            /*
             * -------
             * A) List
             * -------
             */

            /* A-1) Show example locations (list search):
             * ------------------------------------------
             *
             * - https://www.location-api.localhost/api/v1/location/examples.json
             * - https://www.location-api.localhost/api/v1/location/examples.json?language=de&country=DE
             * - https://www.location-api.localhost/api/v1/location/examples.json?language=de&country=DE&c=51.061002,13.740674
             */
            case $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION && $this->query->isExampleRequest():
                return $this->doProvideGetCollectionByGeonameIds($this->getGeonameIdsExample());

            /* A-2) Show country locations (list search):
             * ------------------------------------------
             *
             * - https://www.location-api.localhost/api/v1/location/countries.json
             * - https://www.location-api.localhost/api/v1/location/countries.json?language=de&country=DE
             * - https://www.location-api.localhost/api/v1/location/countries.json?language=de&country=DE&c=51.061002,13.740674
             */
            case $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION && $this->query->isCountryRequest():
                return $this->doProvideGetCollectionByGeonameIds($this->getGeonameIdsCountry());

            /* A-3) Show airport locations (list search):
             * ------------------------------------------
             *
             * - https://www.location-api.localhost/api/v1/location/countries.json
             * - https://www.location-api.localhost/api/v1/location/countries.json?language=de&country=DE
             * - https://www.location-api.localhost/api/v1/location/countries.json?language=de&country=DE&c=51.061002,13.740674
             */
            case $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION && $this->query->isAirportRequest():
                return $this->doProvideGetCollectionByGeonameIds($this->getGeonameIdsAirport());

            /* A-4) Simple search (list search):
             * ---------------------------------
             *
             * - https://www.location-api.localhost/api/v1/location.json?q=Berlin&limit=10
             * - https://www.location-api.localhost/api/v1/location.json?q=Eiffel%20Tower&limit=10&language=de&country=DE
             */
            case $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION && $queryParser->isType(QueryParser::TYPE_SEARCH_LIST_GENERAL):
                return $this->doProvideGetCollectionBySearch($queryParser);

            /* A-5) Next places search (list search, all, DEPRECATED!!!):
             * ----------------------------------------------------------
             *
             * - https://www.location-api.localhost/api/v1/location.json?q=51.05811,13.74133&distance=1000&limit=10
             * - https://www.location-api.localhost/api/v1/location.json?q=51.05811,13.74133&distance=1000&limit=10&language=de&country=DE
             */
            case $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION && $queryParser->isType(QueryParser::TYPE_SEARCH_COORDINATE):

            /* A-6) Next places search (list search, feature code search: Airports, etc.):
             * ---------------------------------------------------------------------------
             *
             * - https://www.location-api.localhost/api/v1/location.json?q=AIRP%2051.05811,13.74133&distance=150000&limit=10
             * - https://www.location-api.localhost/api/v1/location.json?q=AIRP%2051.05811,13.74133&distance=150000&limit=10&language=de&country=DE
             */
            case $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION && $queryParser->isType(QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES):
                return $this->doProvideGetCollectionByCoordinate($queryParser);

            /* A-7) Geoname ID search (list search):
             * ---------------------------------------------------------------------------
             *
             * - https://www.location-api.localhost/api/v1/location.json?q=2879139
             * - https://www.location-api.localhost/api/v1/location.json?q=2879139&country=DE&language=de
             */
            case $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION && $queryParser->isType(QueryParser::TYPE_SEARCH_GEONAME_ID):
                return $this->doProvideGetCollectionByGeonameId($queryParser);



            /*
             * ---------
             * B) Detail
             * ---------
             */

            /* B-1) Coordinate search (single location search):
             * ------------------------------------------------
             *
             * - https://www.location-api.localhost/api/v1/location/coordinate.json?q=51.119882,%2013.132567
             * - https://www.location-api.localhost/api/v1/location/coordinate.json?q=51.119882,%2013.132567&language=en&country=US
             * - https://www.location-api.localhost/api/v1/location/coordinate.json?q=51.119882,%2013.132567&language=en&country=US&next_places
             * - https://www.location-api.localhost/api/v1/location/coordinate.json?q=51.119882,%2013.132567&language=en&country=US&next_places=1
             */
            case $this->getRequestMethod() === Request::METHOD_GET && $queryParser->isType(QueryParser::TYPE_SEARCH_COORDINATE):
                return $this->doProvideGetWithCoordinate($queryParser);

            /* B-2) Show geoname detail (single location search):
             * --------------------------------------------------
             *
             * - https://www.location-api.localhost/api/v1/location/2830942.json
             * - https://www.location-api.localhost/api/v1/location/2830942.json?language=de&country=DE
             * - https://www.location-api.localhost/api/v1/location/2830942.json?language=de&country=DE&next_places
             * - https://www.location-api.localhost/api/v1/location/2830942.json?language=de&country=DE&next_places=1
             * - https://www.location-api.localhost/api/v1/location/2830942.json?c=51.05811,13.74133 // current position
             * - https://www.location-api.localhost/api/v1/location/coordinate.json?q=2830942&country=DE&language=de&next_places=1
             * - https://www.location-api.localhost/api/v1/location/coordinate.json?q=2830942&country=DE&language=de&next_places=1&p=51.05811,13.74133
             */
            case $this->getRequestMethod() === Request::METHOD_GET && $this->hasUri(Name::GEONAME_ID):
                return $this->doProvideGetWithGeonameId($queryParser);



            /*
             * ----------------------
             * Unsupported query type
             * ----------------------
             */
            default:
                $this->setError('Unsupported query type.');
                return [];
        }
    }
}
