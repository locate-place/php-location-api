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

use App\ApiPlatform\Resource\Autocomplete;
use App\ApiPlatform\Route\AutocompleteRoute;
use App\ApiPlatform\State\Base\BaseProviderCustom;
use App\ApiPlatform\Type\AutocompleteFeature;
use App\ApiPlatform\Type\AutocompleteLocation;
use App\Constants\DB\FeatureClass;
use App\Constants\DB\FeatureCode;
use App\Constants\Key\KeyArray;
use App\Constants\Language\LanguageCode;
use App\Entity\Country;
use App\Exception\QueryParserException;
use App\Repository\LocationRepository;
use App\Service\LocationContainer;
use App\Service\LocationService;
use App\Utils\Api\ApiLogger;
use App\Utils\Query\Query;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
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
 * Class AutocompleteProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-04-07)
 * @since 0.1.0 (2024-04-07) First version.
 */
final class AutocompleteProvider extends BaseProviderCustom
{
    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $requestStack
     * @param LocationService $locationService
     * @param TranslatorInterface $translator
     * @param EntityManagerInterface $entityManager
     * @param ApiLogger $apiLogger
     * @param LocationRepository $locationRepository
     * @param LocationContainer $locationContainer
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
        protected LocationRepository $locationRepository,
        protected LocationContainer $locationContainer,
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
        return AutocompleteRoute::PROPERTIES;
    }

    /**
     * Retrieves locations from db.
     *
     * @param string|string[]|null $search
     * @param array<int, string>|string|null $featureClass
     * @param array<int, string>|string|null $featureCode
     * @param string|null $country
     * @param string $isoLanguage
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     country: string|null,
     *     country-name: string|null,
     *     relevance: int
     * }>
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws ORMException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    private function doGetLocationsFromDB(
        /* Search */
        string|array|null $search,

        /* Search filter */
        array|string|null $featureClass = null,
        array|string|null $featureCode = null,
        string|null $country = null,

        /* Configuration */
        string $isoLanguage = LanguageCode::DE
    ): array
    {
        $locations = $this->locationRepository->findBySearch(
            search: $search,
            featureClass: $featureClass,
            featureCode: $featureCode,
            limit: 30,
            isoLanguage: $isoLanguage,
            country: $country
        );

        $locationMatches = [];

        foreach ($locations as $location) {
            $alternateName = $this->locationContainer->getAlternateName($location, $isoLanguage);

            if (is_null($alternateName)) {
                continue;
            }

            $country = $location->getCountry();

            $alternateNameWithCountry = match (true) {
                $country instanceof Country => sprintf('%s, %s', $alternateName, $country->getCode()),
                default => $alternateName,
            };

            if (array_key_exists($alternateNameWithCountry, $locationMatches)) {
                continue;
            }

            $countryCode = $country?->getCode() ?? null;
            $countryName = $country?->getName() ?? null;

            $locationMatches[$alternateNameWithCountry] = [
                KeyArray::ID => $location->getGeonameId() ?? 0,
                KeyArray::NAME => $alternateName,
                KeyArray::COUNTRY => $countryCode,
                KeyArray::COUNTRY_NAME => $countryName,
                KeyArray::RELEVANCE => 9_999_999_999 - $location->getRelevanceScore(),
            ];
        }

        return array_values($locationMatches);
    }

    /**
     * @param string[] $search
     * @param array<int, array{
     *      id: int|string,
     *      name: string,
     *      country?: string|null,
     *      country-name?: string|null,
     *      relevance: int
     *  }> $array
     * @return array<int, array{
     *       id: int|string,
     *       name: string,
     *       country?: string|null,
     *       country-name?: string|null,
     *       relevance: int
     *  }>
     */
    private function filterFirstSearch(
        array $array,
        array $search,
    ): array
    {
        return array_filter(
            $array,
            function($item) use ($search): bool {
                $nameConverted = iconv('UTF-8', 'ASCII//TRANSLIT', strtolower((string) $item[KeyArray::NAME]));

                if (!is_string($nameConverted)) {
                    throw new LogicException('Could not convert $nameConverted to ASCII');
                }

                $searchConverted = iconv('UTF-8', 'ASCII//TRANSLIT', strtolower($search[0]));

                if (!is_string($searchConverted)) {
                    throw new LogicException('Could not convert $searchConverted to ASCII');
                }

                return str_contains($nameConverted, $searchConverted);
            }
        );
    }

    /**
     * @param string[] $search
     * @param array<int, array{
     *      id: int|string,
     *      name: string,
     *      country?: string|null,
     *      country-name?: string|null,
     *      relevance: int
     *  }> $array
     * @param bool $useRelevance
     * @return array<int, array{
     *      id: int|string,
     *      name: string,
     *      country?: string|null,
     *      country-name?: string|null,
     *      relevance: int
     *  }>
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function sortRelevance(
        array $array,
        array $search,
        bool $useRelevance = false,
    ): array
    {
        usort(
            $array,
            /** @var array{id: int, name: string, relevance: int} $valueA */
            /** @var array{id: int, name: string, relevance: int} $valueB */
            function (array $valueA, array $valueB) use ($search, $useRelevance): int {
                $nameA = strtolower($valueA[KeyArray::NAME]);
                $nameB = strtolower($valueB[KeyArray::NAME]);

                $relevanceA = strtolower(sprintf('%010d', $valueA[KeyArray::RELEVANCE]));
                $relevanceB = strtolower(sprintf('%010d', $valueB[KeyArray::RELEVANCE]));

                $startsWithSearchA = $useRelevance && str_starts_with($nameA, strtolower($search[0])) ? '0' : '1';
                $startsWithSearchB = $useRelevance && str_starts_with($nameB, strtolower($search[0])) ? '0' : '1';

                return $startsWithSearchA.$relevanceA.$nameA <=> $startsWithSearchB.$relevanceB.$nameB;
            }
        );

        return $array;
    }

    /**
     * @param array<int, array{
     *      id: int|string,
     *      name: string,
     *      country?: string|null,
     *      country-name?: string|null,
     *      relevance: int
     *  }> $array
     * @return array<int, AutocompleteLocation>
     */
    private function convertAutocompleteLocation(
        array $array
    ): array
    {
        return array_map(fn($locationMatch) => (new AutocompleteLocation())
            ->setId((int) $locationMatch[KeyArray::ID])
            ->setName($locationMatch[KeyArray::NAME])
            ->setCountry($locationMatch[KeyArray::COUNTRY] ?? null)
            ->setCountryName($locationMatch[KeyArray::COUNTRY_NAME] ?? null), $array);
    }

    /**
     * @param array<int, array{
     *      id: string|int,
     *      name: string,
     *      relevance: int
     *  }> $array
     * @return array<int, AutocompleteFeature>
     */
    private function convertAutocompleteFeature(
        array $array
    ): array
    {
        return array_map(fn($locationMatch) => (new AutocompleteFeature())
            ->setId((string) $locationMatch[KeyArray::ID])
            ->setName($locationMatch[KeyArray::NAME]), $array);
    }

    /**
     * @param array<int, array{
     *      id: int|string,
     *      name: string,
     *      country: string|null,
     *      country-name: string|null,
     *      relevance: int
     *  }> $array
     * @param string[] $search
     * @param bool $useRelevance
     * @return array<int, AutocompleteLocation>
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function prepareArrayAutocompleteLocation(
        array $array,
        array $search,
        bool $useRelevance = false,
    ): array
    {
        $array = $this->filterFirstSearch($array, $search);
        $array = $this->sortRelevance($array, $search, $useRelevance);
        return $this->convertAutocompleteLocation($array);
    }

    /**
     * @param array<int, array{
     *      id: string,
     *      name: string,
     *      relevance: int,
     *  }> $array
     * @param string[] $search
     * @param bool $useRelevance
     * @return array<int, AutocompleteFeature>
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function prepareArrayFeatureClasses(
        array $array,
        array $search,
        bool $useRelevance = false,
    ): array
    {
        $array = $this->filterFirstSearch($array, $search);
        $array = $this->sortRelevance($array, $search, $useRelevance);
        return $this->convertAutocompleteFeature($array);
    }

    /**
     * @param array<int, array{
     *     id: string,
     *     name: string,
     *     relevance: int
     * }> $array
     * @param string[] $search
     * @param bool $useRelevance
     * @return array<int, AutocompleteFeature>
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function prepareArrayFeatureCodes(
        array $array,
        array $search,
        bool $useRelevance = false,
    ): array
    {
        $array = $this->filterFirstSearch($array, $search);
        $array = $this->sortRelevance($array, $search, $useRelevance);
        return $this->convertAutocompleteFeature($array);
    }

    /**
     * Returns all location matches.
     *
     * @param Query $query
     * @param string $isoLanguage
     * @return array<int, AutocompleteLocation>
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws ORMException
     * @throws ParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     * @throws QueryParserException
     */
    private function getLocations(Query $query, string $isoLanguage = LanguageCode::DE): array
    {
        $search = $query->getQueryParser()?->getSearch() ?? null;

        if (is_null($search)) {
            return [];
        }

        if (count($search) <= 0) {
            return [];
        }

        $featureClass = $query->getQueryParser()?->getFeatureClasses() ?? null;
        $featureCode = $query->getQueryParser()?->getFeatureCodes() ?? null;
        $country = $query->getQueryParser()?->getCountry() ?? null;

        return $this->prepareArrayAutocompleteLocation(
            $this->doGetLocationsFromDB(
                search: $search,
                featureClass: $featureClass,
                featureCode: $featureCode,
                country: $country,
                isoLanguage: $isoLanguage
            ),
            $search,
            is_null($featureClass) && is_null($featureCode)
        );
    }

    /**
     * @param Query $query
     * @param string $isoLanguage
     * @return array<int, AutocompleteFeature>
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    private function getFeatureClasses(Query $query, string $isoLanguage = LanguageCode::DE): array
    {
        $search = $query->getQueryParser()?->getSearch() ?? null;

        if (is_null($search)) {
            return [];
        }

        if (count($search) <= 0) {
            return [];
        }

        return $this->prepareArrayFeatureClasses(
            (new FeatureCode($this->translator))->getAllAutoCompletion(
                queryString: strtolower($search[0]),
                locale: $isoLanguage
            ),
            $search
        );
    }

    /**
     * @param Query $query
     * @param string $isoLanguage
     * @return array<int, AutocompleteFeature>
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    private function getFeatureCodes(Query $query, string $isoLanguage = LanguageCode::DE): array
    {
        $search = $query->getQueryParser()?->getSearch() ?? null;

        if (is_null($search)) {
            return [];
        }

        if (count($search) <= 0) {
            return [];
        }

        return $this->prepareArrayFeatureCodes(
            (new FeatureClass($this->translator))->getAllAutoCompletion(
                queryString: strtolower($search[0]),
                locale: $isoLanguage
            ),
            $search
        );
    }

    /**
     * Returns a Autocomplete ressource.
     *
     * @return BasePublicResource
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws ORMException
     * @throws ParserException
     * @throws QueryParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    private function doProvideGet(): BasePublicResource
    {
        $request = $this->getRequest();

        $query = new Query($request);

        $queryString = $query->getQuery();
        if (is_null($queryString)) {
            throw new LogicException('Query string is missing.');
        }

        $isoLanguage = $query->getLanguage(LanguageCode::DE);
        if (is_null($isoLanguage)) {
            throw new LogicException('Iso language string is missing.');
        }

        $autocomplete = new Autocomplete();
        $autocomplete
            ->setLocations($this->getLocations($query, $isoLanguage))
            ->setFeatureClasses($this->getFeatureClasses($query, $isoLanguage))
            ->setFeatureCodes($this->getFeatureCodes($query, $isoLanguage))
        ;

        return $autocomplete;
    }

    /**
     * Do the provided job.
     *
     * @return BasePublicResource|BasePublicResource[]
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws ORMException
     * @throws ParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     * @throws QueryParserException
     */
    protected function doProvide(): BasePublicResource|array
    {
        return match($this->getRequestMethod()) {
            Request::METHOD_GET => $this->doProvideGet(),
            default => throw new CaseUnsupportedException('Unsupported mode from api endpoint /api/v1/import.'),
        };
    }
}
