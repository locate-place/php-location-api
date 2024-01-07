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
use App\Constants\DB\Distance;
use App\Constants\DB\Limit;
use App\Constants\Key\KeyArray;
use App\Repository\LocationRepository;
use App\Service\LocationService;
use App\Utils\Query\QueryParser;
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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class LocationProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 */
final class LocationProvider extends BaseProviderCustom
{
    private const FILE_SCHEMA_COORDINATE = 'data/json/schema/command/coordinate/resource.schema.json';

    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     * @param LocationRepository $locationRepository
     * @param TranslatorInterface $translator
     * @param LocationService $locationService
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request,
        protected LocationRepository $locationRepository,
        protected TranslatorInterface $translator,
        protected LocationService $locationService
    )
    {
        parent::__construct($version, $parameterBag, $request);
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
     * Returns a collection of location resources that matches the given coordinate.
     *
     * @return BasePublicResource[]
     * @throws ArrayKeyNotFoundException
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
            $coordinate,
            $this->hasFilter(Name::LIMIT) ? $this->getFilterInteger(Name::LIMIT) : Limit::LIMIT_10,
            $this->hasFilter(Name::DISTANCE) ? $this->getFilterInteger(Name::DISTANCE) : Distance::DISTANCE_1000,
            $queryParser->getFeatureClasses(),
            $queryParser->getFeatureCodes(),
            $isoLanguage,
            $country
        );

        if ($this->locationService->hasError()) {
            $this->setError($this->locationService->getError());
        }

        return $locations;
    }

    /**
     * Returns a location resource that matches the given geoname id.
     *
     * @return BasePublicResource
     * @throws ArrayKeyNotFoundException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     */
    private function doProvideGetWithGeonameId(): BasePublicResource
    {
        [
            KeyArray::ISO_LANGUAGE => $isoLanguage,
            KeyArray::COUNTRY => $country,
        ] = $this->getIsoLanguageAndCountryByFilter();

        if (is_null($isoLanguage) || is_null($country)) {
            return (new LocationResource())->setGeonameId(0);
        }

        $location = $this->locationService->getLocationByGeonameId(
            $this->getUriInteger(Name::GEONAME_ID),
            $isoLanguage,
            $country,
            $this->isNextPlacesByFilter()
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
            $coordinate,
            $isoLanguage,
            $country,
            $this->isNextPlacesByFilter()
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
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws NonUniqueResultException
     * @throws ParserException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function doProvide(): BasePublicResource|array
    {
        $queryParser = match (true) {
            $this->hasFilter(Name::QUERY) => new QueryParser($this->getFilterString(Name::QUERY)),
            $this->hasUri(Name::GEONAME_ID) => new QueryParser($this->getUriInteger(Name::GEONAME_ID)),
            default => null,
        };

        if (!$queryParser instanceof QueryParser) {
            $this->setError('No query given.');
            return [];
        }

        switch (true) {

            /*
             * ------
             * Schema
             * ------
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
             * ----
             * Data
             * ----
             */

            /*
             * https://www.location-api.localhost/api/v1/location.json?q=51.05811,13.74133&distance=1000&limit=10
             * https://www.location-api.localhost/api/v1/location.json?q=51.05811,13.74133&distance=1000&limit=10&language=de&country=DE
             */
            case $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION && $queryParser->isType(QueryParser::TYPE_SEARCH_COORDINATE):

            /*
             * https://www.location-api.localhost/api/v1/location.json?q=AIRP%2051.05811,13.74133&distance=150000&limit=10
             * https://www.location-api.localhost/api/v1/location.json?q=AIRP%2051.05811,13.74133&distance=150000&limit=10&language=de&country=DE
             */
            case $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION && $queryParser->isType(QueryParser::TYPE_SEARCH_LIST_WITH_FEATURES):
                return $this->doProvideGetCollectionByCoordinate($queryParser);

            /*
             * https://www.location-api.localhost/api/v1/location/coordinate.json?q=51.119882,%2013.132567
             * https://www.location-api.localhost/api/v1/location/coordinate.json?q=51.119882,%2013.132567&language=en&country=US
             * https://www.location-api.localhost/api/v1/location/coordinate.json?q=51.119882,%2013.132567&language=en&country=US&next_places
             */
            case $this->getRequestMethod() === Request::METHOD_GET && $queryParser->isType(QueryParser::TYPE_SEARCH_COORDINATE):
                return $this->doProvideGetWithCoordinate($queryParser);

            /*
             * https://www.location-api.localhost/api/v1/location/2830942.json
             * https://www.location-api.localhost/api/v1/location/2830942.json?language=de&country=DE
             * https://www.location-api.localhost/api/v1/location/2830942.json?language=de&country=DE&next_places
             */
            case $this->getRequestMethod() === Request::METHOD_GET && $this->hasUri(Name::GEONAME_ID):
                return $this->doProvideGetWithGeonameId();



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
