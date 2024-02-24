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

namespace App\Command\Property\Crawler;

use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use Ixnode\PhpWebCrawler\Converter\Converter;
use Ixnode\PhpWebCrawler\Converter\Number;
use Ixnode\PhpWebCrawler\Converter\PregMatch;
use Ixnode\PhpWebCrawler\Converter\Sprintf;
use Ixnode\PhpWebCrawler\Converter\Trim;
use Ixnode\PhpWebCrawler\Output\Field;
use Ixnode\PhpWebCrawler\Output\Group;
use Ixnode\PhpWebCrawler\Source\Url;
use Ixnode\PhpWebCrawler\Source\XpathSections;
use Ixnode\PhpWebCrawler\Value\XpathTextNode;
use JsonException;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WikipediaAirportCrawlerCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-24)
 * @since 0.1.0 (2024-02-24) First version.
 *
 * @example bin/console crawler:wikipedia:airport
 */
class WikipediaAirportCrawlerCommand extends Command
{
    protected static $defaultName = 'crawler:wikipedia:airport';

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setName(strval(self::$defaultName))
            ->setDescription('Crawls wikipedia pages for airport properties.')
            ->setDefinition([
                new InputArgument('iata', InputArgument::REQUIRED, 'The iata code to be parsed.'),
            ])
            ->setHelp(
                <<<'EOT'

The <info>crawler:wikipedia:airport</info> crawls wikipedia pages for airport properties.

EOT
            );
    }

    /**
     * Returns the airport wikipedia page from wikipedia search.
     *
     * @return array{link: string, name: string}|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getPage(string $iata): array|null
    {
        $domain = 'https://en.wikipedia.org';
        $site = sprintf('%s/wiki/%s', $domain, $iata);

        $url = new Url(
            $site,
            new Field('title', new XpathTextNode('/html/head/title')),
            new Group(
                'hits',
                new XpathSections(
                    '/html/body//div[@id="mw-content-text"]//ul/li[contains(translate(., \'ABCDEFGHIJKLMNOPQRSTUVWXYZ\', \'abcdefghijklmnopqrstuvwxyz\'), \'airport\')]',
                    new Field('link', new XpathTextNode('./a/@href', new Sprintf($domain.'%s'))),
                    new Field('name', new XpathTextNode('./a/text()')),
                )
            )
        );

        $hits = $url->parse()->getKeyArray('hits');

        if (count($hits) <= 0) {
            return null;
        }

        $hit = $hits[0];

        if (!is_array($hit)) {
            return null;
        }

        if (!array_key_exists('link', $hit)) {
            return null;
        }

        $link = $hit['link'];

        if (!is_string($link)) {
            return null;
        }

        if (!array_key_exists('name', $hit)) {
            return null;
        }

        $name = $hit['name'];

        if (!is_string($name)) {
            return null;
        }

        return [
            'link' => $link,
            'name' => $name,
        ];
    }

    /**
     * Returns the crawler query Field class.
     *
     * @param string $key
     * @param string $search
     * @param Converter[] $converters
     * @param string $subElements
     * @return Field
     */
    private function getField(string $key, string $search, array $converters = [], string $subElements = ''): Field
    {
        return new Field($key, new XpathTextNode(
            sprintf('/html/body//tr[th[contains(@class, "infobox-label") and contains(., "%s")]]/td[contains(@class, "infobox-data")]%s', $search, $subElements),
            ...$converters
        ));
    }

    /**
     * Returns the parsed wikipedia data.
     *
     * @param string $wikipediaPage
     * @return Json
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getPageData(string $wikipediaPage): Json
    {
        $url = new Url(
            $wikipediaPage,
            $this->getField('airport-passengers', 'Passengers', [new Number()]),
            $this->getField('airport-movements', 'Aircraft movements', [new Number()]),
            $this->getField('airport-website', 'Website', [new Trim()], '//a/@href'),
            $this->getField('airport-operator', 'Operator', [new Trim()]),
            $this->getField('airport-opened', 'Opened', [
                new Trim(),
                new PregMatch('/(\d{4}-\d{2}-\d{2})/', 1),
            ]),
            new Field('airport-statistics-year', new XpathTextNode(
                '/html/body//tr/th[contains(@class, "infobox-header") and contains(., "Statistics")]/text()',
                new Trim(),
                new PregMatch('~Statistics \(([0-9]+)\)~', 1)
            ))
        );

        return $url->parse();
    }

    /**
     * Execute the commands.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $iata = $input->getArgument('iata');

        if (!is_string($iata)) {
            throw new LogicException('Unsupported type of iata given.');
        }

        $iata = strtoupper($iata);

        $page = $this->getPage($iata);

        if (is_null($page)) {
            $output->writeln(sprintf('<error>No airport page found for %s.</error>', $iata));
            return Command::FAILURE;
        }

        $name = $page['name'];
        $link = $page['link'];

        $output->writeln(sprintf('IATA: %s', $iata));
        $output->writeln(sprintf('Name: %s', $name));
        $output->writeln(sprintf('Link: %s', $link));

        $data = $this->getPageData($link);

        $output->writeln('Data:');
        $output->writeln($data->getJsonStringFormatted());

        return Command::SUCCESS;
    }
}
