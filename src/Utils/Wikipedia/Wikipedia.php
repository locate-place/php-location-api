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

namespace App\Utils\Wikipedia;

use App\Command\AlternateName\WikipediaCommand;
use App\Constants\Language\LanguageCode;
use App\Constants\Language\WikipediaCode;
use App\Entity\AlternateName;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use LogicException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class Wikipedia
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-19)
 * @since 0.1.0 (2024-02-19) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Wikipedia
{
    private const ZERO_RESULT = 0;

    private const ALTERNATE_NAME_ID_ADD = 1_000_000_000;

    private const ALTERNATE_NAME_ID_ADD_LANGUAGE = 100_000_000;

    private const ALTERNATE_NAME_ID_LANGUAGE_MULTIPLIER_EN = 0;

    private const ALTERNATE_NAME_ID_LANGUAGE_MULTIPLIER_DE = 1;

    private const ALTERNATE_NAME_ID_LANGUAGE_MULTIPLIER_ES = 2;

    private const WIKIPEDIA_TEMPLATE = 'https://%s.wikipedia.org/wiki/%s';

    private const WIKIPEDIA_TYPE_PATTERN = '/^wikipedia-([a-z]{2,3})$/';

    /**
     * @var array<string, array{link: string, language: string, query: string}>
     */
    private array $wikipediaLinks = [];

    /** @var array<string, AlternateName> $alternateNamesWikipedia */
    private array $alternateNamesWikipedia = [];

    /** @var array<int, AlternateName> $alternateNamesOther */
    private array $alternateNamesOther = [];

    /**
     * @param AlternateName[] $alternateNames
     * @param bool $checkLinks
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct(
        array $alternateNames,
        protected bool $checkLinks = false
    )
    {
        if (count($alternateNames) <= self::ZERO_RESULT) {
            throw new LogicException('No alternate names given.');
        }

        match (true) {
            $checkLinks => $this->doParseLinksWithLinkCheck($alternateNames),
            default => $this->doAddLinks($alternateNames),
        };
    }

    /**
     * Returns all wikipedia links.
     *
     * @return array<string, array{link: string, language: string, query: string}>
     */
    public function getWikipediaLinks(): array
    {
        return $this->wikipediaLinks;
    }

    /**
     * Returns all wikipedia alternate names.
     *
     * @return array<string, AlternateName>
     */
    public function getAlternateNamesWikipedia(): array
    {
        return $this->alternateNamesWikipedia;
    }

    /**
     * Returns all wikipedia alternate names.
     *
     * @return array<int, AlternateName>
     */
    public function getAlternateNamesOther(): array
    {
        return $this->alternateNamesOther;
    }

    /**
     * Parses and adds all given AlternateName entities.
     *
     * @param AlternateName[] $alternateNames
     * @return void
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    private function doAddLinks(array $alternateNames): void
    {
        $this->wikipediaLinks = [];
        $this->alternateNamesWikipedia = [];

        foreach ($alternateNames as $alternateName) {
            $type = $alternateName->getType();

            $language = null;
            $matches = [];
            if (!is_null($type) && preg_match(self::WIKIPEDIA_TYPE_PATTERN, $type, $matches)) {
                $language = strtolower($matches[1]);
            }

            /* Alternate name was wikipedia type was found. */
            if (!is_null($language)) {
                $this->alternateNamesWikipedia[$language] = $alternateName;
                continue;
            }

            $alternateNameString = $alternateName->getAlternateName();

            if (is_null($alternateNameString)) {
                continue;
            }

            $linkParsed = $this->doParseWikipediaLink($alternateNameString);

            /* Wikipedia link found. */
            if (!is_null($linkParsed)) {
                $language = $linkParsed['language'];
                $this->alternateNamesWikipedia[$language] = $alternateName;
            }

            /* Another link was found. */
            $alternateName->setType('other');
            $this->alternateNamesOther[] = $alternateName;
        }
    }

    /**
     * Parses and adds all given AlternateName entities.
     *
     * @param AlternateName[] $alternateNames
     * @return void
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function doParseLinksWithLinkCheck(array $alternateNames): void
    {
        $this->wikipediaLinks = [];
        $this->alternateNamesWikipedia = [];

        foreach ($alternateNames as $alternateName) {
            $alternateNameString = $alternateName->getAlternateName();

            if (is_null($alternateNameString)) {
                continue;
            }

            $linkParsed = $this->doParseWikipediaLink($alternateNameString);

            /* Another link was found. */
            if (is_null($linkParsed)) {
                $alternateName->setType('other');
                $this->alternateNamesOther[] = $alternateName;
                continue;
            }

            $linkParsed['link'] = $this->queryWikipediaCheckLink($linkParsed['link']);
            $language = $linkParsed['language'];

            if ($alternateNameString !== $linkParsed['link']) {
                $alternateName
                    ->setAlternateName($linkParsed['link'])
                    ->setSource($this->getSource())
                    ->setChanged(true);
                ;
            }

            $alternateName->setType($this->getType($language));

            $this->alternateNamesWikipedia[$language] = $alternateName;
            $this->wikipediaLinks[$language] = $linkParsed;
        }
    }

    /**
     * Returns the type of given language.
     *
     * @param string $language
     * @return string
     */
    private function getType(string $language): string
    {
        return 'wikipedia-'.$language;
    }

    /**
     * Returns the source.
     *
     * @return string
     */
    private function getSource(): string
    {
        return WikipediaCommand::class;
    }

    /**
     * Returns the wikipedia link for the given language.
     *
     * @param string $language
     * @return AlternateName|null
     */
    public function getWikipediaLink(string $language): AlternateName|null
    {
        if (count($this->alternateNamesWikipedia) <= self::ZERO_RESULT) {
            return null;
        }

        if (array_key_exists($language, $this->alternateNamesWikipedia)) {
            return $this->alternateNamesWikipedia[$language];
        }

        foreach (WikipediaCode::ALLOWED_LANGUAGES as $language) {
            if (array_key_exists($language, $this->alternateNamesWikipedia)) {
                return $this->alternateNamesWikipedia[$language];
            }
        }

        return null;
    }

    /**
     * @param string $link
     * @return array{link: string, language: string, query: string}|null
     */
    private function doParseWikipediaLink(string $link): array|null
    {
        $matches = [];

        if (!preg_match('~([a-z]+).wikipedia\.org\/wiki\/(.+)$~', $link, $matches)) {
            return null;
        }

        return [
            'link' => $link,
            'language' => $matches[1],
            'query' => $matches[2],
        ];
    }

    /**
     * Checks and adds the languages: en, de and es.
     *
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    public function addMissingLanguages(): void
    {
        /* Nothing to do. We don't have at least one alternate name. */
        if (count($this->alternateNamesWikipedia) <= self::ZERO_RESULT) {
            return;
        }

        $languages = [
            LanguageCode::EN => self::ALTERNATE_NAME_ID_LANGUAGE_MULTIPLIER_EN,
            LanguageCode::DE => self::ALTERNATE_NAME_ID_LANGUAGE_MULTIPLIER_DE,
            LanguageCode::ES => self::ALTERNATE_NAME_ID_LANGUAGE_MULTIPLIER_ES,
        ];

        $firstAlternateName = reset($this->alternateNamesWikipedia);

        foreach ($languages as $language => $index) {
            if (!array_key_exists($language, $this->alternateNamesWikipedia)) {
                continue;
            }

            unset($languages[$language]);
        }

        /* All languages are available. */
        if (count(array_keys($languages)) <= self::ZERO_RESULT) {
            return;
        }

        $linksWikipedia = $this->getWikipediaLinksFromWikipediaApi(array_keys($languages));

        if (is_null($linksWikipedia) || count($linksWikipedia) <= self::ZERO_RESULT) {
            return;
        }

        foreach ($linksWikipedia as $language => $linkWikipedia) {
            if (!array_key_exists($language, $languages)) {
                throw new LogicException(sprintf('Unexpected behavior. Language "%s" is not allowed.', $language));
            }

            $index = $languages[$language];

            $alternateName = new AlternateName();
            $alternateName
                ->setLocation($firstAlternateName->getLocation())
                ->setAlternateName($linkWikipedia)
                ->setIsoLanguage($firstAlternateName->getIsoLanguage())
                ->setPreferredName($firstAlternateName->isPreferredName() ?? false)
                ->setShortName($firstAlternateName->isShortName() ?? false)
                ->setColloquial($firstAlternateName->isColloquial() ?? false)
                ->setHistoric($firstAlternateName->isHistoric() ?? false)
                ->setAlternateNameId($firstAlternateName->getAlternateNameId() + self::ALTERNATE_NAME_ID_ADD + $index * self::ALTERNATE_NAME_ID_ADD_LANGUAGE)
                ->setType($this->getType($language))
                ->setSource($this->getSource())
                ->setChanged(true)
            ;

            $this->alternateNamesWikipedia[$language] = $alternateName;
        }
    }

    /**
     * Returns the wikipedia response for the given language.
     *
     * @param string|string[] $languages
     * @return array<string, string>|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    public function getWikipediaLinksFromWikipediaApi(string|array $languages): array|null
    {
        if (count($this->wikipediaLinks) <= self::ZERO_RESULT) {
            throw new LogicException('The number of wikipedia links is zero.');
        }

        $languageFallback = LanguageCode::EN;

        /* Check if fallback language exists, otherwise use the first wikipedia link which can be found. */
        if (!array_key_exists($languageFallback, $this->wikipediaLinks)) {
            $firstWikipediaLink = reset($this->wikipediaLinks);
            $languageFallback = $firstWikipediaLink['language'];
        }

        $link = $this->queryWikipediaCheckLink($this->wikipediaLinks[$languageFallback]['link']);

        $parsedLink = $this->doParseWikipediaLink($link);

        /* We are not able to parse and find that wikipedia link. */
        if (is_null($parsedLink)) {
            return null;
        }

        $languageLinks = $this->getLanguageLinks(
            languageFallback: $languageFallback,
            query: $parsedLink['query']
        );

        if (is_null($languageLinks)) {
            return null;
        }

        $languageQueries = $this->getLanguageQueries($languageLinks, is_string($languages) ? [$languages] : $languages);

        if (count($languageQueries) <= 0) {
            return null;
        }

        return $this->translateLanguageQueriesToWikipediaLinks($languageQueries);
    }

    /**
     * Converts the given language links to language queries.
     *
     * @param array<int|string, mixed> $languageLinks
     * @param string[] $languages
     * @return array<string, string>
     */
    private function getLanguageQueries(array $languageLinks, array $languages): array
    {
        $languageQueries = [];

        foreach ($languageLinks as $languageLink) {
            if (!is_array($languageLink)) {
                continue;
            }

            $language = array_key_exists('lang', $languageLink) ? $languageLink['lang'] : null;

            if (!is_string($language)) {
                continue;
            }

            $query = array_key_exists('*', $languageLink) ? $languageLink['*'] : null;

            if (!is_string($query)) {
                continue;
            }

            if (!in_array($language, $languages)) {
                continue;
            }

            $languageQueries[$language] = $query;
        }

        return $languageQueries;
    }

    /**
     * Translates all given language queries to wikipedia links.
     *
     * @param array<string, string> $languageQueries
     * @return array<string, string>
     */
    private function translateLanguageQueriesToWikipediaLinks(array $languageQueries): array
    {
        $wikipediaLinks = [];

        foreach ($languageQueries as $language => $query) {
            $wikipediaLinks[$language] = sprintf(
                self::WIKIPEDIA_TEMPLATE,
                $language,
                str_replace(' ', '_', $query)
            );
        }

        return $wikipediaLinks;
    }

    /**
     * Returns the language links from wikipedia.
     *
     * @param string $languageFallback
     * @param string $query
     * @return array<int|string, mixed>|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    private function getLanguageLinks(
        string $languageFallback,
        string $query
    ): array|null
    {
        $result = $this->queryWikipediaLanguages(
            languageFallback: $languageFallback,
            query: $query
        );

        $pages = $result->getKeyArray(['query', 'pages']);

        $firstPage = array_shift($pages);

        if (!is_array($firstPage)) {
            return null;
        }

        $firstPage = new Json($firstPage);

        if (!$firstPage->hasKey('langlinks')) {
            return null;
        }

        return $firstPage->getKeyArray(['langlinks']);
    }

    /**
     * Gets the wikipedia langauges for the given query.
     *
     * @param string $languageFallback
     * @param string $query
     * @return Json
     * @throws ClientExceptionInterface
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    private function queryWikipediaLanguages(
        string $languageFallback,
        string $query
    ): Json
    {
        $client = HttpClient::create();
        $endPoint = sprintf('https://%s.wikipedia.org/w/api.php', $languageFallback);
        $response = $client->request('GET', $endPoint, [
            'query' => [
                "action" => "query",
                "titles" => urldecode($query),
                "prop" => "langlinks",
                "format" => "json",
                "lllimit" => "max",
            ]
        ]);

        return new Json($response->getContent());
    }

    /**
     * Checks if the link is a valid wikipedia link.
     *
     * @param string $link
     * @return string
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function queryWikipediaCheckLink(string $link): string
    {
        $client = HttpClient::create();
        $response = $client->request('GET', $link);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return $link;
        }

        $htmlContent = $response->getContent();
        $crawler = new Crawler($htmlContent);
        $filter = $crawler->filter('link[rel="canonical"]');

        if ($filter->count() <= self::ZERO_RESULT) {
            return $link;
        }

        $href = $filter->attr('href');

        if (is_null($href)) {
            return $link;
        }

        return $href;
    }
}
