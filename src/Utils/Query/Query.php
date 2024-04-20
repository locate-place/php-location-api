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

use App\Constants\DB\Limit;
use App\Exception\QueryParserException;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Parser\ParserException;
use LogicException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Query
 *
 * Terminology:
 * ------------
 *
 * ===============
 * Filter: A parameter that is used to filter or specify the results
 * ---------------
 * - &c=51.05811,13.74133
 * ---------------
 * - &country=DE
 * - &country=GB
 * - &country=US
 * ---------------
 * - &language=de
 * - &language=en
 * ---------------
 * - &q=Dresden
 * - &q=Berlin
 * ---------------
 * - &s=distance
 * - &s=geoname-id
 * - &s=name
 * - &s=relevance
 * ---------------
 * - etc.
 * ===============
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-11)
 * @since 0.1.0 (2024-01-11) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Query
{
    final public const FILTER_COUNTRY = 'country';

    final public const FILTER_CURRENT_POSITION = 'p';

    final public const FILTER_LANGUAGE = 'language';

    final public const FILTER_QUERY = 'q';

    final public const FILTER_SORT = 's';

    final public const FILTER_DISTANCE = 'distance';

    final public const FILTER_LIMIT = 'limit';

    final public const FILTER_PAGE = 'page';

    final public const URI_GEONAME_ID = 'geoname_id';

    final public const WORD_EXAMPLES = 'examples';

    private const PAGE_FIRST = 1;

    /** @var array<string, mixed> $uriVariables */
    private array $uriVariables = [];

    /**
     * @param Request $request
     */
    public function __construct(private readonly Request $request)
    {
    }

    /**
     * Returns the uri variables.
     *
     * @return array<string, mixed>
     */
    public function getUriVariables(): array
    {
        return $this->uriVariables;
    }

    /**
     * Sets the uri variables.
     *
     * @param array<string, mixed> $uriVariables
     * @return void
     */
    public function setUriVariables(array $uriVariables): void
    {
        $this->uriVariables = $uriVariables;
    }

    /**
     * Returns the path of the request.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->request->getPathInfo();
    }

    /**
     * Returns whether the request contains a path.
     *
     * @param string $wordRegexp
     * @return bool
     */
    public function hasPath(string $wordRegexp): bool
    {
        $pathWords = explode('/', $this->getPath());

        foreach ($pathWords as $pathWord) {
            if (preg_match(sprintf('~^%s$~', $wordRegexp), $pathWord)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns if the request is an example request.
     *
     * @return bool
     */
    public function isExampleRequest(): bool
    {
        return $this->hasPath('examples(:?\.(:?json|html))?');
    }

    /**
     * Returns if the request is a country request.
     *
     * @return bool
     */
    public function isCountryRequest(): bool
    {
        return $this->hasPath('countries(:?\.(:?json|html))?');
    }

    /**
     * Returns if the request is an airport request.
     *
     * @return bool
     */
    public function isAirportRequest(): bool
    {
        return $this->hasPath('airports(:?\.(:?json|html))?');
    }

    /**
     * Returns if the request contains a uri.
     *
     * @param string $key
     * @return bool
     */
    public function hasUri(string $key): bool
    {
        if (!array_key_exists($key, $this->uriVariables)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the uri from the given $key.
     *
     * @param string $key
     * @return mixed
     */
    public function getUri(string $key): mixed
    {
        if (!array_key_exists($key, $this->uriVariables)) {
            throw new LogicException(sprintf('The uri with given key "%s" does not exist.', $key));
        }

        return $this->uriVariables[$key];
    }

    /**
     * Returns the uri from the given $key as string.
     *
     * @param string $key
     * @return string
     */
    public function getUriAsString(string $key): string
    {
        $uri = $this->getUri($key);

        return match (true) {
            is_string($uri) => $uri,
            is_int($uri) => (string) $uri,
            default => throw new LogicException(sprintf('The uri with given key "%s" is not a string.', $key)),
        };
    }

    /**
     * Returns the uri from the given $key as integer.
     *
     * @param string $key
     * @return int
     */
    public function getUriAsInteger(string $key): int
    {
        $uri = $this->getUri($key);

        return match (true) {
            is_int($uri) => $uri,
            is_string($uri) => (int) $uri,
            default => throw new LogicException(sprintf('The uri with given key "%s" is not an integer.', $key)),
        };
    }

    /**
     * Returns if parameter $key exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasFilter(string $key): bool
    {
        return $this->request->query->has($key);
    }

    /**
     * Returns parameter $key.
     *
     * @param string $key
     * @param string|int|float|bool|null $default
     * @return string|int|float|bool|null
     */
    public function getFilter(string $key, string|int|float|bool|null $default = null): string|int|float|bool|null
    {
        if (!$this->hasFilter($key) && !is_null($default)) {
            return $default;
        }

        if (!$this->hasFilter($key)) {
            throw new LogicException(sprintf('The filter with given key "%s" does not exist.', $key));
        }

        return $this->request->query->get($key);
    }

    /**
     * Returns parameter $key as string.
     *
     * @param string $key
     * @param string|null $default
     * @return string
     */
    public function getFilterAsString(string $key, string $default = null): string
    {
        $parameter = $this->getFilter($key, $default);

        return match (true) {
            is_string($parameter) => $parameter,
            is_int($parameter) => (string) $parameter,
            default => throw new LogicException(sprintf('The filter with given key "%s" is not a string.', $key)),
        };
    }

    /**
     * Returns parameter $key as string.
     *
     * @param string $key
     * @param string|null $default
     * @return int
     */
    public function getFilterAsInt(string $key, string $default = null): int
    {
        $parameter = $this->getFilter($key, $default);

        return match (true) {
            is_string($parameter) => (int) $parameter,
            is_int($parameter) => $parameter,
            default => throw new LogicException(sprintf('The filter with given key "%s" is not an integer.', $key)),
        };
    }

    /**
     * Returns the query parameter.
     *
     * @return string|null
     */
    public function getQuery(): string|null
    {
        $key = self::FILTER_QUERY;

        if (!$this->hasFilter($key)) {
            return null;
        }

        return $this->getFilterAsString($key);
    }

    /**
     * Returns the own coordinate (own position) parameter as Coordinate.
     *
     * @return Coordinate|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function getCurrentPosition(): Coordinate|null
    {
        $key = self::FILTER_CURRENT_POSITION;

        if (!$this->hasFilter($key)) {
            return null;
        }

        return new Coordinate($this->getFilterAsString($key));
    }

    /**
     * Returns the distance parameter.
     *
     * @return int|null
     */
    public function getDistance(): int|null
    {
        $key = self::FILTER_DISTANCE;

        if (!$this->hasFilter($key)) {
            return null;
        }

        return $this->getFilterAsInt($key);
    }

    /**
     * Returns the distance parameter.
     *
     * @param int|null $defaultDistance
     * @return int|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getDistanceDefault(int|null $defaultDistance = null): int|null
    {
        $key = self::FILTER_DISTANCE;

        $queryParser = $this->getQueryParser();

        $distance = $queryParser?->getDistance() ?? null;

        if (!is_null($distance)) {
            return $distance;
        }

        if (!$this->hasFilter($key)) {
            return $defaultDistance;
        }

        return $this->getFilterAsInt($key);
    }

    /**
     * Returns the limit parameter.
     *
     * @return int|null
     */
    public function getLimit(): int|null
    {
        $key = self::FILTER_LIMIT;

        if (!$this->hasFilter($key)) {
            return null;
        }

        return $this->getFilterAsInt($key);
    }

    /**
     * Returns the limit parameter.
     *
     * @param int $defaultLimit
     * @return int
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws QueryParserException
     */
    public function getLimitDefault(int $defaultLimit = Limit::LIMIT_10): int
    {
        $key = self::FILTER_LIMIT;

        $queryParser = $this->getQueryParser();

        $limit = $queryParser?->getLimit() ?? null;

        if (!is_null($limit)) {
            return $limit;
        }

        if (!$this->hasFilter($key)) {
            return $defaultLimit;
        }

        return $this->getFilterAsInt($key);
    }

    /**
     * Returns the page parameter.
     *
     * @return int|null
     */
    public function getPage(): int|null
    {
        $key = self::FILTER_PAGE;

        if (!$this->hasFilter($key)) {
            return null;
        }

        return $this->getFilterAsInt($key);
    }

    /**
     * Returns the page parameter.
     *
     * @param int $defaultPage
     * @return int
     */
    public function getPageDefault(int $defaultPage = self::PAGE_FIRST): int
    {
        $key = self::FILTER_PAGE;

        if (!$this->hasFilter($key)) {
            return $defaultPage;
        }

        return $this->getFilterAsInt($key);
    }

    /**
     * Returns the country parameter.
     *
     * @return string|null
     */
    public function getCountry(): string|null
    {
        $key = self::FILTER_COUNTRY;

        if (!$this->hasFilter($key)) {
            return null;
        }

        return $this->getFilterAsString($key);
    }

    /**
     * Returns the language parameter.
     *
     * @param string|null $default
     * @return string|null
     */
    public function getLanguage(string $default = null): string|null
    {
        $key = self::FILTER_LANGUAGE;

        if (!$this->hasFilter($key)) {
            return !is_null($default) ? $default : null;
        }

        return $this->getFilterAsString($key);
    }

    /**
     * Returns the query parameter as string.
     *
     * @return string|int|null
     */
    public function getQueryParserString(): string|int|null
    {
        $isGeonameIdsRequest = $this->isExampleRequest() ||
            $this->isCountryRequest() ||
            $this->isAirportRequest();

        return match (true) {
            $this->hasFilter(self::FILTER_QUERY) => $this->getFilterAsString(self::FILTER_QUERY),
            $this->hasUri(self::URI_GEONAME_ID) => $this->getUriAsInteger(self::URI_GEONAME_ID),
            $isGeonameIdsRequest => self::WORD_EXAMPLES,
            default => null,
        };
    }

    /**
     * Returns the query parameter as QueryParser.
     *
     * @return QueryParser|null
     */
    public function getQueryParser(): QueryParser|null
    {
        $queryParserString = $this->getQueryParserString();

        if (is_null($queryParserString)) {
            return null;
        }

        return new QueryParser(
            queryString: $queryParserString,
            request: $this->request
        );
    }
}
