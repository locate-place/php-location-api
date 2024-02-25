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

use App\Constants\Key\KeyArray;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use Ixnode\PhpWebCrawler\Converter\Base\Converter;
use Ixnode\PhpWebCrawler\Converter\Boolean;
use Ixnode\PhpWebCrawler\Converter\Number;
use Ixnode\PhpWebCrawler\Converter\PregMatch;
use Ixnode\PhpWebCrawler\Converter\Sprintf;
use Ixnode\PhpWebCrawler\Converter\Trim;
use Ixnode\PhpWebCrawler\Output\Field;
use Ixnode\PhpWebCrawler\Output\Group;
use Ixnode\PhpWebCrawler\Source\Url;
use Ixnode\PhpWebCrawler\Source\XpathSections;
use Ixnode\PhpWebCrawler\Value\LastUrl;
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

    private const ZERO_RESULT = 0;

    private const DOMAIN_WIKIPEDIA = 'https://en.wikipedia.org';

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setName(strval(self::$defaultName))
            ->setDescription('Crawls wikipedia pages for airport properties.')
            ->setDefinition([
                new InputArgument(KeyArray::IATA, InputArgument::REQUIRED, 'The iata code to be parsed.'),
            ])
            ->setHelp(
                <<<'EOT'

The <info>crawler:wikipedia:airport</info> crawls wikipedia pages for airport properties.

EOT
            );
    }

    /**
     * Returns the crawler query Field class.
     *
     * @param string $key
     * @param string|string[] $search
     * @param Converter[] $converters
     * @param string $subElements
     * @return Field
     */
    private function getField(string $key, string|array $search, array $converters = [], string $subElements = ''): Field
    {
        if (is_string($search)) {
            $search = [$search];
        }

        foreach ($search as &$value) {
            $value = sprintf('contains(., "%s")', $value);
        }

        $search = implode(' or ', $search);

        return new Field($key, new XpathTextNode(
            sprintf('/html/body//tr[th[contains(@class, "infobox-label") and (%s)]]/td[contains(@class, "infobox-data")]%s', $search, $subElements),
            ...$converters
        ));
    }

    /**
     * Returns the airport Field classes.
     *
     * @return Field[]
     */
    private function getFieldsAirport(): array
    {
        return [
            $this->getField('airport-passengers', ['Passengers', 'Passenger volume'], [new Number()]),
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
        ];
    }

    /**
     * Returns the airport wikipedia page from wikipedia search.
     *
     * @param string $iata
     * @return Json|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getPage(string $iata): Json|null
    {
        $site = sprintf('%s/wiki/%s', self::DOMAIN_WIKIPEDIA, $iata);

        $url = new Url(
            $site,
            new Field('title', new XpathTextNode('/html/head/title')),
            new Field('last-url', new LastUrl()),
            new Field('is-list-page', new XpathTextNode('/html/body//*[@id="mw-content-text"]/div/p[contains(., "may refer to:")]', new Boolean())),
            new Group(
                'hits',
                new XpathSections(
                    '/html/body//div[@id="mw-content-text"]//ul/li[contains(translate(., \'ABCDEFGHIJKLMNOPQRSTUVWXYZ\', \'abcdefghijklmnopqrstuvwxyz\'), \'airport\')]',
                    new Field('link', new XpathTextNode('./a/@href', new Sprintf(self::DOMAIN_WIKIPEDIA.'%s'))),
                    new Field('name', new XpathTextNode('./a/text()')),
                )
            ),
            new Group(
                'airport',
                ...$this->getFieldsAirport()
            )
        );

        $parsed = $url->parse();

        return match (true) {
            $parsed->getKeyBoolean('is-list-page') => $parsed,
            $parsed->hasKey('hits') && (count($parsed->getKeyArray('hits')) > self::ZERO_RESULT) => $parsed,
            default => null,
        };
    }

    /**
     * Returns the parsed wikipedia data.
     *
     * @param Json $parsed
     * @return Json
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getPageData(Json $parsed): Json
    {
        if (!$parsed->getKeyBoolean('is-list-page')) {
            return $parsed->getKeyJson('airport');
        }

        $link = $parsed->getKeyString(['hits', '0', 'link']);

        $url = new Url(
            $link,
            ...$this->getFieldsAirport()
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
        $iata = $input->getArgument(KeyArray::IATA);

        if (!is_string($iata)) {
            throw new LogicException('Unsupported type of iata given.');
        }

        $iata = strtoupper($iata);

        $parsed = $this->getPage($iata);

        if (is_null($parsed)) {
            $output->writeln(sprintf('<error>No airport page found for %s.</error>', $iata));
            return Command::FAILURE;
        }

        $isListPage = $parsed->getKeyBoolean('is-list-page');

        $name = $isListPage ? $parsed->getKeyString(['hits', '0', 'name']) : $parsed->getKeyString('title');
        $link = $isListPage ? $parsed->getKeyString(['hits', '0', 'link']) : $parsed->getKeyString('last-url');

        $output->writeln('');
        $output->writeln(sprintf('IATA:   %s', $iata));
        $output->writeln(sprintf('Name:   %s', $name));
        $output->writeln(sprintf('Link:   %s', $link));

        $data = $this->getPageData($parsed);

        $output->writeln('');
        $output->writeln('Data:');
        $output->writeln('---');
        $output->writeln($data->getJsonStringFormatted());
        $output->writeln('---');
        $output->writeln('');

        return Command::SUCCESS;
    }
}
