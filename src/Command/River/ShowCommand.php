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

namespace App\Command\River;

use App\Constants\DB\FeatureCode;
use App\Constants\Key\KeyArray;
use App\Constants\Language\LanguageCode;
use App\Entity\Location;
use App\Repository\LocationRepository;
use App\Repository\RiverPartRepository;
use App\Repository\RiverRepository;
use App\Service\LocationContainer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class ShowCommand.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-23)
 * @since 0.1.0 (2024-03-23) First version.
 *
 * @example bin/console river:show --position="51.120552, 13.132655" --distance=3000 --limit=4
 * @example bin/console river:show --distance=20000 --limit=10 --position="51.067377, 13.735513"
 * @example bin/console river:show --distance=20000 --limit=10 --position="51.067377, 13.735513" -s river
 * @example bin/console river:show --distance=20000 --limit=10 --position="51.067377, 13.735513" -s location
 * @example bin/console river:show --distance=20000 --limit=10 --position="51.067377, 13.735513" -s location -l en
 */
class ShowCommand extends Command
{
    protected static string $defaultName = 'river:show';

    protected InputInterface $input;

    protected OutputInterface $output;

    private const OPTION_NAME_SEARCH_TYPE = 'search-type';

    private const OPTION_NAME_ISO_LANGUAGE = 'iso-language';

    private const SEARCH_TYPE_RIVER = 'river';

    private const SEARCH_TYPE_LOCATION = 'location';

    private const LENGTH_LINE = 120;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LocationRepository $locationRepository
     * @param RiverRepository $riverRepository
     * @param RiverPartRepository $riverPartRepository
     * @param LocationContainer $locationContainer
     */
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly LocationRepository $locationRepository,
        protected readonly RiverRepository $riverRepository,
        protected readonly RiverPartRepository $riverPartRepository,
        protected readonly LocationContainer $locationContainer
    )
    {
        parent::__construct();
    }

    /**
     * Configures the command.
     *
     * @inheritdoc
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName(strval(self::$defaultName))
            ->setDescription('Shows rivers to given location.')
            ->setDefinition([
                new InputOption(KeyArray::POSITION, null, InputOption::VALUE_OPTIONAL, 'The latitude value.', null),
                new InputOption(KeyArray::DISTANCE, null, InputOption::VALUE_OPTIONAL, 'The distance value (meters).', 2000),
                new InputOption(KeyArray::LIMIT, null, InputOption::VALUE_OPTIONAL, 'The limit value.', 100),
                new InputOption(KeyArray::RIVER_NAME, null, InputOption::VALUE_OPTIONAL, 'The river name value.', null),
                new InputOption(self::OPTION_NAME_SEARCH_TYPE, '-s', InputOption::VALUE_REQUIRED, sprintf('The search type. Possible values: %s', implode(', ', [self::SEARCH_TYPE_RIVER, self::SEARCH_TYPE_LOCATION])), self::SEARCH_TYPE_LOCATION),
                new InputOption(self::OPTION_NAME_ISO_LANGUAGE, '-l', InputOption::VALUE_REQUIRED, sprintf('The search type. Possible values: %s', implode(', ', [LanguageCode::DE, LanguageCode::EN, 'etc.'])), LanguageCode::DE),
            ])
            ->setHelp(
                <<<'EOT'

The <info>river:show</info> shows rivers to given location.

EOT
            );
    }

    /**
     * Returns the given input.
     *
     * @param string $input
     * @param int $padLength
     * @param string $padString
     * @param int $padType
     * @return string
     */
    private function getMbStrPad(string $input, int $padLength, string $padString = ' ', int $padType = STR_PAD_RIGHT): string
    {
        $diff = strlen($input) - mb_strlen($input);
        return str_pad($input, $padLength + $diff, $padString, $padType);
    }

    /**
     * Prints the header.
     *
     * @return void
     */
    private function printHeader(): void
    {
        $this->output->writeln(sprintf(
            '%-6s %-62s   %-11s   %-10s   %-9s   %s',
            'ID',
            'Name',
            'Length',
            'River Code',
            'Distance',
            'Latitude,Longitude',
        ));
        $this->output->writeln(str_repeat('-', self::LENGTH_LINE));
    }

    /**
     * Requests rivers and shows them.
     *
     * @param Coordinate|null $coordinate
     * @param string[]|null $riverNames
     * @param int|null $distanceMeter
     * @param int $limit
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    private function showRivers(
        Coordinate|null $coordinate,
        array|null $riverNames,
        int|null $distanceMeter,
        int $limit
    ): void
    {
        /* Try to find rivers. */
        $rivers = $this->riverRepository->findRivers(
            coordinate: $coordinate,
            riverNames: $riverNames,
            distanceMeter: $distanceMeter,
            limit: $limit
        );

        $this->printHeader();

        foreach ($rivers as $river) {
            $this->output->writeln(sprintf(
                '%6d %-60s   %8.2f km   %12d   %6.2f km   %s,%s (%s)',
                $river->getId(),
                $this->getMbStrPad($river->getName() ?? '', 60),
                round((float) $river->getLength(), 2),
                $river->getRiverCode(),
                $river->getClosestDistance() ?? .0,
                $river->getClosestCoordinate()?->getLatitude() ?? '--',
                $river->getClosestCoordinate()?->getLongitude() ?? '--',
                $coordinate?->getDirection(
                    new Coordinate(
                        $river->getClosestCoordinate()?->getLatitude(),
                        $river->getClosestCoordinate()?->getLongitude()
                    )
                ) ?? '--',
            ));
        }

        $this->output->writeln('');
    }

    /**
     * Requests (river) locations and shows them.
     *
     * @param Coordinate|null $coordinate
     * @param int|null $distanceMeter
     * @param int $limit
     * @return void
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws ParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     */
    private function showLocations(
        Coordinate|null $coordinate,
        int|null $distanceMeter,
        int $limit
    ): void
    {
        $isoLanguage = $this->input->getOption(self::OPTION_NAME_ISO_LANGUAGE);
        if (!is_string($isoLanguage)) {
            throw new LogicException('The given ISO language is not a string.');
        }

        /* Try to find (river) locations. */
        $locations = $this->locationRepository->findRiversAndLakes(
            coordinate: $coordinate,
            distanceMeter: $distanceMeter,
            featureCodes: FeatureCode::STM,
            limit: $limit,
        );

        $this->printHeader();

        foreach ($locations as $location) {
            $river = $location->getRiver();

            if (is_null($river) || $river === false) {
                continue;
            }

            $riverCoordinate = $location->getCoordinateIxnode();

            $distanceMeter = is_null($coordinate) ? '--' : $coordinate->getDistance($riverCoordinate, Coordinate::RETURN_KILOMETERS);
            $direction = is_null($coordinate) ? '--' : $coordinate->getDirection($riverCoordinate);

            $this->output->writeln(sprintf(
                '%6d %-60s   %8.2f km   %12d   %6.2f km   %s,%s (%s)',
                $location->getId(),
                $this->getMbStrPad($this->locationContainer->getAlternateName($location, $isoLanguage) ?? '--', 60),
                round((float) $river->getLength(), 2),
                $river->getRiverCode(),
                $distanceMeter,
                $riverCoordinate->getLatitude(),
                $riverCoordinate->getLongitude(),
                $direction,
            ));
        }

        $this->output->writeln('');
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws ClientExceptionInterface
     * @throws ORMException
     * @throws ParserException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $position = $input->getOption(KeyArray::POSITION);
        if (!is_string($position) && !is_null($position)) {
            throw new LogicException('The given position is not a string.');
        }

        $distanceMeter = $input->getOption(KeyArray::DISTANCE);
        if (!is_string($distanceMeter) && !is_int($distanceMeter)) {
            throw new LogicException('The given distance is not a string or an integer.');
        }

        $limit = $input->getOption(KeyArray::LIMIT);
        if (!is_string($limit) && !is_int($limit)) {
            throw new LogicException('The given limit is not a string or an integer.');
        }

        $riverName = $input->getOption(KeyArray::RIVER_NAME);
        if (!is_null($riverName) && !is_string($riverName)) {
            throw new LogicException('The given name is not a string.');
        }

        $searchType = $this->input->getOption(self::OPTION_NAME_SEARCH_TYPE);
        if (!is_string($searchType) || !in_array($searchType, [self::SEARCH_TYPE_RIVER, self::SEARCH_TYPE_LOCATION])) {
            throw new LogicException(sprintf('Invalid option "%s". Allowed: %s', self::OPTION_NAME_SEARCH_TYPE, implode(', ', [self::SEARCH_TYPE_RIVER, self::SEARCH_TYPE_LOCATION])));
        }

        $coordinate = is_null($position) ? null : new Coordinate($position);
        $riverNames = is_null($riverName) ? null : explode(Location::NAME_SEPARATOR, $riverName);
        $distanceMeter = is_null($position) ? null : (int) $distanceMeter;
        $limit = (int) $limit;

        /* Print coordinate and distance. */
        $textCoordinate = 'Coordinate: No coordinate was given.';
        if (!is_null($coordinate)) {
            $textCoordinate = sprintf(
                'Coordinate: %s, %s (%d meters).',
                $coordinate->getLatitude(),
                $coordinate->getLongitude(),
                $distanceMeter
            );
        }
        $output->writeln($textCoordinate);
        $output->writeln('');

        $time = microtime(true);
        match ($searchType) {
            self::SEARCH_TYPE_RIVER => $this->showRivers(
                coordinate: $coordinate,
                riverNames: $riverNames,
                distanceMeter: $distanceMeter,
                limit: $limit
            ),
            self::SEARCH_TYPE_LOCATION => $this->showLocations(
                coordinate: $coordinate,
                distanceMeter: $distanceMeter,
                limit: $limit
            ),
            default => throw new LogicException(sprintf('Invalid option "%s". Allowed: %s', self::OPTION_NAME_SEARCH_TYPE, implode(', ', [self::SEARCH_TYPE_RIVER, self::SEARCH_TYPE_LOCATION]))),
        };
        $time = microtime(true) - $time;
        $output->writeln(sprintf('Time: %3.4f seconds', $time));
        $output->writeln('');

        return Command::SUCCESS;
    }
}
