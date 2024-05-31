<?php

/*
* This file is part of the ixnode/php-api-version-bundle project.
*
* (c) Björn Hempel <https://www.hempel.li/>
*
* For the full copyright and license information, please view the LICENSE.md
* file that was distributed with this source code.
*/

declare(strict_types=1);

namespace App\Utils\Query;

use App\Constants\DB\FeatureClass;
use App\Constants\DB\FeatureCode;
use App\Constants\Key\KeyArray;
use App\Constants\Language\LanguageCode;
use App\Exception\QueryParserException;
use App\Tests\Unit\Utils\Query\ParserTest;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpTimezone\Constants\CountryAll;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * Class QueryParser
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-06)
 * @since 0.1.0 (2024-01-06) First version.
 * @link ParserTest
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class QueryParser
{
    final public const TYPE_SEARCH_GEONAME_ID = 'search-geoname-id';

    final public const TYPE_SEARCH_COORDINATE = 'search-coordinate';

    final public const TYPE_SEARCH_LIST_GENERAL = 'search-list-general';

    final public const TYPE_CUSTOM = 'search-custom';

    final public const TYPE_SEARCH_LIST_WITH_FEATURES = 'search-list-with-features';

    final public const TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE = 'search-list-with-features-and-coordinate';

    final public const TYPE_SEARCH_LIST_WITH_FEATURES_AND_GEONAME_ID = 'search-list-with-features-and-geoname-id';

    final public const TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH = 'search-list-with-features-and-search';

    private const SEPARATOR_FEATURE_CODES = '|';

    private const LENGTH_FEATURE_CLASS = 1;

    private const LENGTH_FEATURE_CODE = 5;

    private const EXCEPTION_MINIMUM = 3;

    private const EREG_WRAPPER_SINGLE = '^%s$';

    private const EREG_WRAPPER_COORDINATE = '^%s *[,/| ]+ *%s$';

    private const EREG_WRAPPER_COORDINATE_WITH_FEATURES = '^%s[ :] *%s *[,/| ]+ *%s$';

    private const EREG_WRAPPER_LIST_SEARCH_WITH_FEATURES = '^%s(?:(?:(?:[ :][ ]*)(.*))|)$';

    private const FORMAT_ID = '([0-9]+)';

    private const FORMAT_FEATURES_SINGLE = '(?:[A-Z]{1,3}[A-Z0-9]{1,2}|[AHLPRSTUV])';

    private const FORMAT_FEATURES = '('.self::FORMAT_FEATURES_SINGLE.'(?:\\'.self::SEPARATOR_FEATURE_CODES.self::FORMAT_FEATURES_SINGLE.')*)';

    private const FORMAT_DECIMAL = '([-+]?[0-9]+[\.,][0-9]+)°?';

    private const FORMAT_DMS = '[0-9]+°[0-9]+′[0-9]+\.[0-9]+″';

    private const FORMAT_DMS_LATITUDE = '('.self::FORMAT_DMS.'[NS])';

    private const FORMAT_DMS_LONGITUDE = '('.self::FORMAT_DMS.'[EW])';

    private const FORMAT_LATITUDES = [self::FORMAT_DECIMAL, self::FORMAT_DMS_LATITUDE];

    private const FORMAT_LONGITUDES = [self::FORMAT_DECIMAL, self::FORMAT_DMS_LONGITUDE];

    private string $queryString;

    private string $type;

    /** @var array{
     *      country: string|null,
     *      distance: int|null,
     *      feature-classes: string[]|null,
     *      feature-codes: string[]|null,
     *      geoname-id: int|null,
     *      latitude: float|null,
     *      limit: int|null,
     *      longitude: float|null,
     *      search: string|null,
     *      type: string
     * } $data */
    private array $data;

    /** @var string[] $matches */
    private array $matches = [];

    private int|null $distance = null;

    private int|null $limit = null;

    private string|null $country = null;

    /** @var string[]|null $featureCodes */
    private array|null $featureCodes = null;

    /** @var string[]|null $featureClasses */
    private array|null $featureClasses = null;

    private readonly Query|null $query;

    /**
     * @param string|int $queryString
     * @param string[] $allowedFeatureClasses
     * @param string[] $allowedFeatureCodes
     * @param Request|null $request
     */
    public function __construct(
        string|int $queryString,
        protected array $allowedFeatureClasses = FeatureClass::ALL,
        protected array $allowedFeatureCodes = FeatureCode::ALL,
        protected Request|null $request = null
    )
    {
        $this->queryString = trim((string) $queryString);

        $this->query = match (true) {
            !is_null($request) => new Query($request),
            default => null,
        };
    }

    /**
     * Returns the query string.
     *
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }

    /**
     * Returns the query object.
     *
     * @return Query|null
     */
    public function getQuery(): Query|null
    {
        return $this->query;
    }

    /**
     * Returns the query type (cached).
     *
     * @return string
     */
    public function getType(): string
    {
        if (isset($this->type)) {
            return $this->type;
        }

        $this->type = $this->doGetType($this->queryString);

        return $this->type;
    }

    /**
     * Returns if the given type matches with the query.
     *
     * @param string $type
     * @return bool
     */
    public function isType(string $type): bool
    {
        return $this->getType() === $type;
    }

    /**
     * Returns the query data (cached).
     *
     * @return array{
     *     type: string,
     *     geoname-id: int|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     feature-classes: string[]|null,
     *     feature-codes: string[]|null,
     *     search: string|null,
     *     distance: int|null,
     *     country: string|null,
     *     limit: int|null
     * }
     *
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getData(): array
    {
        if (isset($this->data)) {
            return $this->data;
        }

        $this->data = $this->doGetData();

        return $this->data;
    }

    /**
     * Returns the geoname-id.
     *
     * @return int|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getGeonameId(): int|null
    {
        $data = $this->getData();

        return $data[KeyArray::GEONAME_ID];
    }

    /**
     * Returns the latitude.
     *
     * @return float|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getLatitude(): float|null
    {
        $data = $this->getData();

        return $data[KeyArray::LATITUDE];
    }

    /**
     * Returns the longitude.
     *
     * @return float|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getLongitude(): float|null
    {
        $data = $this->getData();

        return $data[KeyArray::LONGITUDE];
    }

    /**
     * Returns the feature classes.
     *
     * @return string[]|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getFeatureClasses(): array|null
    {
        $data = $this->getData();

        return $data[KeyArray::FEATURE_CLASSES];
    }

    /**
     * Returns the feature classes translated.
     *
     * @param TranslatorInterface $translator
     * @param string $locale
     * @return array<int, array<string, string>>|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getFeatureClassesTranslated(
        TranslatorInterface $translator,
        string $locale = LanguageCode::EN
    ): array|null
    {
        $featureClasses = $this->getFeatureClasses();

        if (is_null($featureClasses)) {
            return null;
        }

        $translated = [];

        foreach ($featureClasses as $featureClass) {
            $featureClassInstance = new FeatureClass($translator);

            $translated[] = [
                KeyArray::CODE => $featureClass,
                KeyArray::TRANSLATED => $featureClassInstance->translate($featureClass, $locale),
            ];
        }

        return $translated;
    }

    /**
     * Returns the feature codes.
     *
     * @return string[]|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getFeatureCodes(): array|null
    {
        $data = $this->getData();

        return $data[KeyArray::FEATURE_CODES];
    }

    /**
     * Returns the feature codes translated.
     *
     * @param TranslatorInterface $translator
     * @param string $locale
     * @return array<int, array<string, string>>|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getFeatureCodesTranslated(
        TranslatorInterface $translator,
        string $locale = LanguageCode::EN
    ): array|null
    {
        $featureCodes = $this->getFeatureCodes();

        if (is_null($featureCodes)) {
            return null;
        }

        $translated = [];

        foreach ($featureCodes as $featureCode) {
            $featureCodeInstance = new FeatureCode($translator);

            $translated[] = [
                KeyArray::CODE => $featureCode,
                KeyArray::TRANSLATED => $featureCodeInstance->translate($featureCode, $locale),
            ];
        }

        return $translated;
    }

    /**
     * Returns the search array.
     *
     * @return string[]|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getSearch(): array|null
    {
        $data = $this->getData();

        $searchString = $data[KeyArray::SEARCH];

        if (!is_string($searchString)) {
            return null;
        }

        $searchString = preg_replace('~[():;!?]~', '', $searchString);

        if (!is_string($searchString)) {
            throw new LogicException('Unable to replace values from $searchString');
        }

        return array_filter($this->getSplitStringKeepingQuotes($searchString), fn($word) => !empty($word));
    }

    /**
     * @param string $input
     * @return string[]
     */
    public function getSplitStringKeepingQuotes(string $input): array
    {
        /* Regex matches quoted substrings or non-whitespace sequences. */
        preg_match_all('~"[^"]*"|\'[^\']*\'|\S+~', $input, $matches);

        /* Trim the quotes from the matched parts */
        return array_map(function($part)
        {
            /* Check if the part starts and ends with quotes */
            if (
                (str_starts_with((string) $part, '"') && str_ends_with((string) $part, '"')) ||
                (str_starts_with((string) $part, '\'') && str_ends_with((string) $part, '\''))
            ) {
                /* Remove the surrounding quotes. */
                return substr((string) $part, 1, -1);
            }

            /* Return the part as is if it's not quoted */
            return $part;
        }, $matches[0]);
    }

    /**
     * Returns the distance.
     *
     * @return int|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getDistance(): int|null
    {
        $data = $this->getData();

        return $data[KeyArray::DISTANCE];
    }

    /**
     * Returns the country.
     *
     * @return string|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getCountry(): string|null
    {
        $data = $this->getData();

        return $data[KeyArray::COUNTRY];
    }

    /**
     * Returns the limit.
     *
     * @return int|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getLimit(): int|null
    {
        $data = $this->getData();

        return $data[KeyArray::LIMIT];
    }

    /**
     * Returns a Coordinate class from given data.
     *
     * @return Coordinate|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getCoordinate(): Coordinate|null
    {
        $data = $this->getData();

        $latitude = $data[KeyArray::LATITUDE];
        $longitude = $data[KeyArray::LONGITUDE];

        if (is_null($latitude) || is_null($longitude)) {
            return null;
        }

        return new Coordinate($latitude, $longitude);
    }

    /**
     * Returns the data container according the given parameter.
     *
     * @param string $type
     * @param int|null $geonameId
     * @param float|null $latitude
     * @param float|null $longitude
     * @param string[]|null $featureClasses
     * @param string[]|null $featureCodes
     * @param string|null $search
     * @param int|null $distance
     * @param string|null $country
     * @param int|null $limit
     * @return array{
     *     country: string|null,
     *     distance: int|null,
     *     feature-classes: string[]|null,
     *     feature-codes: string[]|null,
     *     geoname-id: int|null,
     *     latitude: float|null,
     *     limit: int|null,
     *     longitude: float|null,
     *     search: string|null,
     *     type: string
     * }
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public static function getDataContainer(
        /* Search type. */
        string $type,

        /* Search terms. */
        string|null $search = null,
        int|null $geonameId = null,
        float|null $latitude = null,
        float|null $longitude = null,

        /* Search filter. */
        array|null $featureClasses = null,
        array|null $featureCodes = null,

        /* Filter configuration. */
        int|null $distance = null,
        int|null $limit = null,
        string|null $country = null
    ): array
    {
        if (!is_null($latitude) && is_null($longitude)) {
            throw new LogicException('If latitude is given, longitude must be given as well.');
        }
        if (!is_null($longitude) && is_null($latitude)) {
            throw new LogicException('If longitude is given, latitude must be given as well.');
        }

        return [
            KeyArray::COUNTRY => $country,
            KeyArray::DISTANCE => $distance,
            KeyArray::FEATURE_CLASSES => $featureClasses,
            KeyArray::FEATURE_CODES => $featureCodes,
            KeyArray::GEONAME_ID => $geonameId,
            KeyArray::LATITUDE => $latitude,
            KeyArray::LIMIT => $limit,
            KeyArray::LONGITUDE => $longitude,
            KeyArray::SEARCH => $search,
            KeyArray::TYPE => $type,
        ];
    }

    /**
     * Returns the query type (type: search-list-with-features-X).
     *
     * @param string|false $query
     * @return string
     */
    private function getTypeSearchListWithFeatures(string|false $query): string
    {
        /* Only features were given. */
        if (empty($query)) {
            return self::TYPE_SEARCH_LIST_WITH_FEATURES;
        }

        /* Features with geoname id were given. */
        if (mb_ereg(sprintf(self::EREG_WRAPPER_SINGLE, self::FORMAT_ID), $query)) {
            return self::TYPE_SEARCH_LIST_WITH_FEATURES_AND_GEONAME_ID;
        }

        /* Features with search term were given. */
        return self::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH;
    }

    /**
     * Parses the given search query.
     *
     * @param string $query
     * @return array{
     *     search: string,
     *     country?: string|null,
     *     feature-codes?: string[],
     *     feature-classes?: string[],
     *     limit?: int,
     *     distance?: int,
     * }
     */
    private function parseSearchQuery(string $query): array
    {
        /** @var array{
         *     search: string,
         *     country?: string|null,
         *     feature-codes?: string[],
         *     feature-classes?: string[],
         *     limit?: int,
         *     distance?: int,
         * } $params */
        $params = [
            KeyArray::SEARCH => [],
        ];

        $parts = explode(' ', $query);

        foreach ($parts as $part) {
            if (!str_contains($part, ':')) {
                $part = trim($part);

                if (empty($part)) {
                    continue;
                }

                $params[KeyArray::SEARCH][] = trim($part);
                continue;
            }

            [$key, $value] = explode(':', $part);

            match ($key) {
                /* String values. */
                KeyArray::COUNTRY => $params[$key] = $this->getCountryCode($value),

                /* Array values. */
                KeyArray::FEATURE_CLASSES, KeyArray::FEATURE_CODES => $params[$key] = $this->splitFeatures($value),

                /* Integer values. */
                KeyArray::LIMIT, KeyArray::DISTANCE => $params[$key] = (int) $value,

                /* Search values. */
                default => $params[KeyArray::SEARCH][] = trim(sprintf('%s:%s', $key, $value)),
            };
        }

        $params[KeyArray::SEARCH] = implode(' ', $params[KeyArray::SEARCH]);

        return $params;
    }

    /**
     * @param string $value
     * @return string|null
     */
    private function getCountryCode(string $value): string|null
    {
        if (empty($value)) {
            return null;
        }

        if (!array_key_exists($value, CountryAll::COUNTRY_NAMES)) {
            return null;
        }

        return $value;
    }

    /**
     * @param string $value
     * @return string[]
     */
    private function splitFeatures(string $value): array
    {
        $split = preg_split('~[,|]~', $value);

        if ($split === false) {
            throw new LogicException(sprintf('Unable to split %s', $value));
        }

        return $split;
    }

    /**
     * Returns the query type.
     *
     * @param string $query
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function doGetType(string $query): string
    {
        $parsedSearchQuery = $this->parseSearchQuery($query);

        $onlySearch = count($parsedSearchQuery) === 1 && array_key_exists('search', $parsedSearchQuery);
        $search = $parsedSearchQuery['search'];

        /* Just an id was given -> Use the id to query a direct geoname id:
         * - 12345678
         * - 52454235
         */
        if ($onlySearch && mb_ereg(sprintf(self::EREG_WRAPPER_SINGLE, self::FORMAT_ID), $search, $this->matches)) {
            return self::TYPE_SEARCH_GEONAME_ID;
        }

        /* A coordinate was given -> Use the given coordinate to query according given coordinate:
         * - 52.524889,13.3692797
         * - 28.137008, -15.438614
         * - 51.05811°,13.74133°
         * - 52°31′12.108″N,13°24′17.604″E
         * - 52°31′12.108″N, 13°24′17.604″E
         * - 52°31′12.108″N,13.3692797
         * - 52°31′12.108″N, -15.438614
         * - 52.524889,13°24′17.604″E
         * - 28.137008, 13°24′17.604″E
         * - etc.
         */
        if ($onlySearch) {
            foreach (self::FORMAT_LATITUDES as $formatLatitude) {
                foreach (self::FORMAT_LONGITUDES as $formatLongitude) {
                    if (mb_ereg(sprintf(self::EREG_WRAPPER_COORDINATE, $formatLatitude, $formatLongitude), $search, $this->matches)) {
                        $featuresString = $this->getFeaturesAsString();

                        $this->matches = match(true) {
                            /* Search with feature codes. */
                            !is_null($featuresString) => [$this->matches[0], $featuresString, $this->matches[1], $this->matches[2]],

                            /* Search without feature codes. */
                            default => $this->matches,
                        };

                        return match (true) {
                            /* Search with feature codes. */
                            !is_null($featuresString) => self::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE,

                            /* Search without feature codes. */
                            default => self::TYPE_SEARCH_COORDINATE,
                        };
                    }
                }
            }
        }

        /* A coordinate with feature class/code was given -> Use search list query with features:
         * - AIRP 52.524889,13.3692797
         * - AIRP 28.137008, -15.438614
         * - AIRP 51.05811°,13.74133°
         * - AIRP 52°31′12.108″N,13°24′17.604″E
         * - AIRP 52°31′12.108″N, 13°24′17.604″E
         * - AIRP 52°31′12.108″N,13.3692797
         * - AIRP 52°31′12.108″N, -15.438614
         * - AIRP 52.524889,13°24′17.604″E
         * - AIRP 28.137008, 13°24′17.604″E
         * - AIRP|AIRT 28.137008, 13°24′17.604″E
         * - S 28.137008, 13°24′17.604″E
         * - S|AIRP|AIRT 28.137008, 13°24′17.604″E
         * - etc.
         */
        if ($onlySearch) {
            foreach (self::FORMAT_LATITUDES as $formatLatitude) {
                foreach (self::FORMAT_LONGITUDES as $formatLongitude) {
                    if (mb_ereg(sprintf(self::EREG_WRAPPER_COORDINATE_WITH_FEATURES, self::FORMAT_FEATURES, $formatLatitude, $formatLongitude), $search, $this->matches)) {
                        return self::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE;
                    }
                }
            }
        }

        /* Feature class/code was given -> Use search list query with features:
         * - AIRP
         * - AIRP|AIRT
         * - AIRP|AIRT Dresden
         * - etc.
         */
        /* Feature codes/classes with and without search term */
        if ($onlySearch && mb_ereg(sprintf(self::EREG_WRAPPER_LIST_SEARCH_WITH_FEATURES, self::FORMAT_FEATURES), $search, $this->matches)) {

            $featuresString = $this->getFeaturesAsString();

            $this->matches = match(true) {
                /* Search with feature codes. */
                !is_null($featuresString) => [$this->matches[0], $this->matches[1].'|'.$featuresString, $this->matches[2]],

                /* Search without feature codes. */
                default => $this->matches,
            };

            return $this->getTypeSearchListWithFeatures($this->matches[2]);
        }

        /* Use the query as a search list query:
         * - all the rest
         */
        if ($onlySearch) {
            $featuresString = $this->getFeaturesAsString();

            $this->matches = match (true) {
                /* Search with feature codes. */
                !is_null($featuresString) => [$search, $featuresString, $search],

                /* Search without feature codes. */
                default => [$search, $search]
            };

            return match (true) {
                /* Search with feature codes. */
                !is_null($featuresString) => self::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH,

                /* Search without feature codes. */
                default => self::TYPE_SEARCH_LIST_GENERAL
            };
        }

        /* Remember properties. */
        if (array_key_exists(KeyArray::DISTANCE, $parsedSearchQuery)) {
            $this->distance = $parsedSearchQuery[KeyArray::DISTANCE];
        }
        if (array_key_exists(KeyArray::LIMIT, $parsedSearchQuery)) {
            $this->limit = $parsedSearchQuery[KeyArray::LIMIT];
        }
        if (array_key_exists(KeyArray::COUNTRY, $parsedSearchQuery)) {
            $this->country = $parsedSearchQuery[KeyArray::COUNTRY];
        }
        if (array_key_exists(KeyArray::FEATURE_CODES, $parsedSearchQuery)) {
            $this->featureCodes = [...(!is_null($this->featureCodes) ? $this->featureCodes : []) ,...$parsedSearchQuery[KeyArray::FEATURE_CODES]];
        }
        if (array_key_exists(KeyArray::FEATURE_CLASSES, $parsedSearchQuery)) {
            $this->featureClasses = $parsedSearchQuery[KeyArray::FEATURE_CLASSES];
        }

        return $this->doGetType($search);
    }

    /**
     * Returns the query data.
     *
     * @return array{
     *     country: string|null,
     *     distance: int|null,
     *     feature-classes: string[]|null,
     *     feature-codes: string[]|null,
     *     geoname-id: int|null,
     *     latitude: float|null,
     *     limit: int|null,
     *     longitude: float|null,
     *     search: string|null,
     *     type: string
     * }
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    private function doGetData(): array
    {
        $type = $this->getType();

        return match ($type) {
            /* Geoname search. */
            self::TYPE_SEARCH_GEONAME_ID => $this->getDataContainerParsed(
                type: $type,
                geonameId: (int) $this->matches[1]
            ),

            /* Coordinate search. */
            self::TYPE_SEARCH_COORDINATE => $this->getDataContainerParsed(
                type: $type,
                latitude: $this->matches[1],
                longitude: $this->matches[2]
            ),

            /* Feature list search. */
            self::TYPE_SEARCH_LIST_WITH_FEATURES => $this->getDataContainerParsed(
                type: $type,
                features: $this->matches[1]
            ),

            /* Feature list search with coordinate. */
            self::TYPE_SEARCH_LIST_WITH_FEATURES_AND_COORDINATE => $this->getDataContainerParsed(
                type: $type,
                latitude: $this->matches[2],
                longitude: $this->matches[3],
                features: $this->matches[1]
            ),

            /* Feature list search with coordinate. */
            self::TYPE_SEARCH_LIST_WITH_FEATURES_AND_GEONAME_ID => $this->getDataContainerParsed(
                type: $type,
                geonameId: (int) $this->matches[2],
                features: $this->matches[1]
            ),

            /* Feature list search with search string. */
            self::TYPE_SEARCH_LIST_WITH_FEATURES_AND_SEARCH => $this->getDataContainerParsed(
                type: $type,
                features: $this->matches[1],
                search: $this->matches[2]
            ),

            /* General search. */
            self::TYPE_SEARCH_LIST_GENERAL => $this->getDataContainerParsed(
                type: $type,
                search: $this->matches[1]
            ),

            default => throw new LogicException(sprintf('Unknown query type "%s".', $type)),
        };
    }

    /**
     * Returns the data container according the given parameter.
     *
     * @param string $type
     * @param int|null $geonameId
     * @param string|float|null $latitude
     * @param string|float|null $longitude
     * @param string[]|string|null $features
     * @param string|null $search
     * @return array{
     *     country: string|null,
     *     distance: int|null,
     *     feature-classes: string[]|null,
     *     feature-codes: string[]|null,
     *     geoname-id: int|null,
     *     latitude: float|null,
     *     limit: int|null,
     *     longitude: float|null,
     *     search: string|null,
     *     type: string
     * }
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    private function getDataContainerParsed(
        string $type,
        int|null $geonameId = null,
        string|float|null $latitude = null,
        string|float|null $longitude = null,
        array|string|null $features = null,
        string|null $search = null
    ): array
    {
        if (!is_null($latitude) && is_null($longitude)) {
            throw new LogicException('If latitude is given, longitude must be given as well.');
        }
        if (!is_null($longitude) && is_null($latitude)) {
            throw new LogicException('If longitude is given, latitude must be given as well.');
        }

        if (is_string($latitude) || is_string($longitude)) {
            $coordinate = new Coordinate($latitude, $longitude);

            $latitude = $coordinate->getLatitudeDecimal();
            $longitude = $coordinate->getLongitudeDecimal();
        }

        [
            KeyArray::FEATURE_CLASSES => $featureClasses,
            KeyArray::FEATURE_CODES => $featureCodes
        ] = $this->extractFeatures($features);

        return self::getDataContainer(
            /* Search type. */
            type: $type,

            /* Search terms. */
            search: $search,
            geonameId: $geonameId,
            latitude: $latitude,
            longitude: $longitude,

            /* Search filter. */
            featureClasses: $featureClasses,
            featureCodes: $featureCodes,

            /* Filter configuration. */
            distance: $this->distance ?? null,
            limit: $this->limit ?? null,
            country: $this->country ?? null,
        );
    }

    /**
     * Extract features (featureClasses and featureCodes) from given features.
     *
     * @param string[]|string|null $features
     * @return array{feature-classes: string[]|null, feature-codes: string[]|null}
     * @throws QueryParserException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function extractFeatures(array|string|null $features = null): array
    {
        if (is_null($features)) {
            return [
                KeyArray::FEATURE_CLASSES => null,
                KeyArray::FEATURE_CODES => null
            ];
        }

        if (is_string($features)) {
            $features = explode(self::SEPARATOR_FEATURE_CODES, $features);
        }

        $featureClasses = [];
        $featureCodes = [];

        foreach ($features as $feature) {
            $length = mb_strlen($feature);

            if (!is_int($length)) {
                throw new LogicException('Failed to get length of feature.');
            }

            match (true) {
                $length === self::LENGTH_FEATURE_CLASS => $featureClasses[] = strtoupper($feature),
                $length > self::LENGTH_FEATURE_CLASS && $length <= self::LENGTH_FEATURE_CODE => $featureCodes[] = strtoupper($feature),
                default => null,
            };
        }

        if (count($featureClasses) <= 0) {
            $featureClasses = null;
        }

        if (count($featureCodes) <= 0) {
            $featureCodes = null;
        }

        if (!is_null($featureClasses)) {
            $featureClasses = array_unique($featureClasses);
        }

        if (!is_null($featureCodes)) {
            $featureCodes = array_unique($featureCodes);
        }

        if (!is_null($featureClasses)) {
            foreach ($featureClasses as $featureClass) {
                if (in_array($featureClass, $this->allowedFeatureClasses, true)) {
                    continue;
                }

                throw new QueryParserException(sprintf('Unsupported feature class "%s".', $featureClass));
            }
        }

        if (!is_null($featureCodes)) {
            $featureCodesNew = [];

            foreach ($featureCodes as $featureCode) {
                if (in_array($featureCode, $this->allowedFeatureCodes, true)) {
                    $featureCodesNew[] = $featureCode;
                    continue;
                }

                if (strlen($featureCode) <= self::EXCEPTION_MINIMUM) {
                    $this->queryString .= ' '.strtolower($featureCode);
                    continue;
                }

                throw new QueryParserException(sprintf('Unsupported feature code "%s".', $featureCode));
            }

            $featureCodes = count($featureCodesNew) > 0 ? $featureCodesNew : null;
        }

        return [
            KeyArray::FEATURE_CLASSES => $featureClasses,
            KeyArray::FEATURE_CODES => $featureCodes,
        ];
    }

    /**
     * Returns the QueryParser configuration.
     *
     * @param TranslatorInterface|null $translator
     * @param string $locale
     * @return array<string, mixed>
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function get(
        TranslatorInterface $translator = null,
        string $locale = LanguageCode::EN
    ): array
    {
        $search = $this->getSearch();
        $coordinate = $this->getConfigCoordinate();

        try {
            $featureClasses = is_null($translator) ?
                $this->getFeatureClasses() :
                $this->getFeatureClassesTranslated($translator, $locale)
            ;
        } catch (Throwable) {
            $featureClasses = null;
        }

        try {
            $featureCodes = is_null($translator) ?
                $this->getFeatureCodes() :
                $this->getFeatureCodesTranslated($translator, $locale)
            ;
        } catch (Throwable) {
            $featureCodes = null;
        }

        $geonameId = $this->getGeonameId();
        $distance = $this->getDistance();
        $limit = $this->getLimit();

        return [
            KeyArray::RAW => $this->queryString,
            KeyArray::PARSED => [
                KeyArray::TYPE => $this->getType(),
                ...(is_null($search) ? [] : [KeyArray::SEARCH => $search]),
                ...(is_null($coordinate) ? [] : [KeyArray::COORDINATE => $coordinate]),
                ...(is_null($featureClasses) ? [] : [KeyArray::FEATURE_CLASSES => $featureClasses]),
                ...(is_null($featureCodes) ? [] : [KeyArray::FEATURE_CODES => $featureCodes]),
                ...(is_null($geonameId) ? [] : [KeyArray::GEONAME_ID => $geonameId]),
                ...(is_null($distance) ? [] : [KeyArray::DISTANCE => $distance]),
                ...(is_null($limit) ? [] : [KeyArray::LIMIT => $limit]),
            ],
        ];
    }

    /**
     * Returns the configuration array from the QueryParser.
     *
     * @return array<string, mixed>|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    private function getConfigCoordinate(): array|null
    {
        $coordinate = $this->getCoordinate();

        if (is_null($coordinate)) {
            return null;
        }

        return [
            KeyArray::RAW => sprintf('%s, %s', $coordinate->getLatitudeDecimal(), $coordinate->getLongitudeDecimal()),
            KeyArray::PARSED => [
                KeyArray::LATITUDE => [
                    KeyArray::DECIMAL => $coordinate->getLatitudeDecimal(),
                    KeyArray::DMS => $coordinate->getLatitudeDMS(),
                ],
                KeyArray::LONGITUDE => [
                    KeyArray::DECIMAL => $coordinate->getLongitudeDecimal(),
                    KeyArray::DMS => $coordinate->getLongitudeDMS(),
                ],
                KeyArray::SRID => 4326,
                KeyArray::LINKS => [
                    KeyArray::GOOGLE => $coordinate->getLinkGoogle(),
                    KeyArray::OPENSTREETMAP => $coordinate->getLinkOpenStreetMap(),
                ],
            ],
        ];
    }

    /**
     * Returns the features as string.
     *
     * @return string|null
     */
    private function getFeaturesAsString(): string|null
    {
        return match (true) {
            /* Search with feature codes. */
            !is_null($this->featureClasses) && !is_null($this->featureCodes) => implode('|', [...$this->featureClasses, ...$this->featureCodes]),
            !is_null($this->featureClasses) => implode('|', $this->featureClasses),
            !is_null($this->featureCodes) => implode('|', $this->featureCodes),

            /* No search with feature codes. */
            default => null,
        };
    }
}
