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

use App\ApiPlatform\Resource\Autocomplete;
use App\ApiPlatform\Route\AutocompleteRoute;
use App\ApiPlatform\State\Base\BaseProviderCustom;
use App\Constants\DB\FeatureClass;
use App\Constants\DB\FeatureCode;
use App\Constants\Key\KeyArray;
use App\Constants\Language\LanguageCode;
use App\Entity\Country;
use App\Exception\QueryParserException;
use App\Repository\LocationRepository;
use App\Service\LocationContainer;
use App\Service\LocationService;
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
     * @param RequestStack $request
     * @param LocationService $locationService
     * @param TranslatorInterface $translator
     * @param EntityManagerInterface $entityManager
     * @param LocationRepository $locationRepository
     * @param LocationContainer $locationContainer
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request,
        protected LocationService $locationService,
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $entityManager,
        protected LocationRepository $locationRepository,
        protected LocationContainer $locationContainer,
    )
    {
        parent::__construct($version, $parameterBag, $request, $locationService, $translator, $entityManager);
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
     *     relevance: int,
     *     country: string|null
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
     *      relevance: int
     *  }> $array
     * @return array<int, array{
     *      id: int|string,
     *      name: string,
     *      relevance: int
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
     *      relevance: int
     *  }> $array
     * @param bool $useRelevance
     * @return array<int, array{
     *      id: int|string,
     *      name: string,
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
     *      relevance: int
     *  }> $array
     * @return array<int, array{
     *      id: int|string,
     *      name: string
     *  }>
     */
    private function removeRelevance(
        array $array
    ): array
    {
        return array_map(function($locationMatch) {
            unset($locationMatch[KeyArray::RELEVANCE]);
            return $locationMatch;
        }, $array);
    }

    /**
     * @param array<int, array{
     *      id: int|string,
     *      name: string,
     *      relevance: int
     *  }> $array
     * @param string[] $search
     * @param bool $useRelevance
     * @return array<int, array{
     *      id: int|string,
     *      name: string
     *  }>
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function prepareArray(
        array $array,
        array $search,
        bool $useRelevance = false,
    ): array
    {
        $array = $this->filterFirstSearch($array, $search);
        $array = $this->sortRelevance($array, $search, $useRelevance);
        return $this->removeRelevance($array);
    }

    /**
     * Returns all location matches.
     *
     * @param Query $query
     * @param string $isoLanguage
     * @return array<int, array{
     *     id: int|string,
     *     name: string,
     *     country?: string|null
     * }>
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

        return $this->prepareArray(
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
     * @return array<int, array{
     *     id: int|string,
     *     name: string
     * }>
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

        return $this->prepareArray(
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
     * @return array<int, array{
     *     id: int|string,
     *     name: string
     * }>
     * @throws CaseUnsupportedException
     * @throws ParserException
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

        return $this->prepareArray(
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
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    private function doProvideGet(): BasePublicResource
    {
        $currentRequest = $this->request->getCurrentRequest();

        if (is_null($currentRequest)) {
            throw new LogicException('Unable to get current request.');
        }

        $query = new Query($currentRequest);

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
     */
    protected function doProvide(): BasePublicResource|array
    {
        return match($this->getRequestMethod()) {
            Request::METHOD_GET => $this->doProvideGet(),
            default => throw new CaseUnsupportedException('Unsupported mode from api endpoint /api/v1/import.'),
        };
    }
}
