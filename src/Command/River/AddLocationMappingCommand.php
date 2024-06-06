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

namespace App\Command\River;

use App\Constants\DB\Country as CountryDb;
use App\Constants\Key\KeyArray;
use App\Constants\Question\Question;
use App\Entity\Country;
use App\Entity\Location;
use App\Entity\River;
use App\Repository\CountryRepository;
use App\Repository\LocationRepository;
use App\Repository\RiverPartRepository;
use App\Repository\RiverRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Throwable;

/**
 * Class AddLocationMappingCommand.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-23)
 * @since 0.1.0 (2024-03-23) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 *
 * Start mapping rivers with 10 km distance (default: -m 10000):
 * @example bin/console river:mapping --number=10000 -f
 *
 * Add some more rivers with 20 km distance and ignore already ignored rivers:
 * @example bin/console river:mapping --number=10000 -f -m 20000 -i
 *
 * Check rivers:
 * bin/console river:show --river-name="Burggraben"
 * bin/console river:show --distance=20000 --limit=30 --position="47.75768 11.11567" --river-name="Zeil"
 */
class AddLocationMappingCommand extends Command
{
    protected static string $defaultName = 'river:mapping';

    private const TEXT_CALCULATED_SIMILARITY = '<comment>Calculated similarity:</comment>    <info>%1.2f</info>';

    private const TEXT_LOCATION_FOUND = '<comment>Location found:</comment>           <fg=#c0392b;options=bold>%s</>: <fg=green;options=bold>ID=%d</> - <fg=blue;options=bold>Coordinate=%s</>';

    private const TEXT_RIVER_NOT_FOUND = '<comment>River not found:</comment>          <fg=#c0392b;options=bold>%s</>';

    private const TEXT_RIVER_FOUND = '<comment>River found:</comment>              <fg=#c0392b;options=bold>%s</>: <fg=green;options=bold>ID=%d</> - <fg=blue;options=bold>Coordinate=%s</> - <fg=cyan;options=bold>Distance=%f km</>';

    private const DISTANCE_MAX_NEXT_RIVER = 10000;

    private const DEFAULT_NUMBER_IMPORT = 20;

    private const DEFAULT_NUMBER_SHOW = 20;

    private const DEFAULT_DISTANCE_NO_RIVER = 999.;

    private const NO_SIMILARITY = .0;

    private const DISTANCE_MIN_ASK = .5;

    private const LENGTH_LINE = 120;

    private const SIMILARITY_EQUAL = 1.;

    private const OPTION_NAME_IGNORE_IGNORED = 'ignore-ignored';

    private const OPTION_NAME_DISTANCE_MAX_NEXT_RIVER = 'distance-max-next-river';

    protected InputInterface $input;

    protected OutputInterface $output;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LocationRepository $locationRepository
     * @param RiverRepository $riverRepository
     * @param RiverPartRepository $riverPartRepository
     * @param CountryRepository $countryRepository
     */
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly LocationRepository $locationRepository,
        protected readonly RiverRepository $riverRepository,
        protected readonly RiverPartRepository $riverPartRepository,
        protected readonly CountryRepository $countryRepository
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
            ->setDescription('Adds location mappings to river table.')
            ->setDefinition([
                new InputOption(KeyArray::DEBUG, '-d', InputOption::VALUE_NONE, 'Shows debug information.'),
                new InputOption(KeyArray::FORCE, '-f', InputOption::VALUE_NONE, 'Force import. Simply import without asking.'),
                new InputOption(self::OPTION_NAME_IGNORE_IGNORED, '-i', InputOption::VALUE_NONE, 'Ignore ignored locations.'),
                new InputOption(KeyArray::NUMBER, null, InputOption::VALUE_REQUIRED, 'Number of rivers to import.', self::DEFAULT_NUMBER_IMPORT),
                new InputOption(self::OPTION_NAME_DISTANCE_MAX_NEXT_RIVER, '-m', InputOption::VALUE_REQUIRED, 'Max distance to next river.', self::DISTANCE_MAX_NEXT_RIVER),
            ])
            ->setHelp(
                <<<'EOT'

The <info>river:mapping</info> adds location mappings to river table.

EOT
            );
    }

    /**
     * Shows the river output of river:show command.
     *
     * @param Location $location
     * @param string $title
     * @param string[]|null $riverNames
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws Throwable
     */
    protected function showRivers(Location $location, string $title, array|false $riverNames = null): void
    {
        if ($riverNames === false) {
            return;
        }

        $parameters = [
            'command' => 'river:show',
            '--distance' => self::DISTANCE_MAX_NEXT_RIVER,
            '--limit' => self::DEFAULT_NUMBER_SHOW,
            '--position' => $location->getCoordinateIxnode()->getString(),
        ];

        if (!is_null($riverNames)) {
            $parameters['--river-name'] = implode(Location::NAME_SEPARATOR, $riverNames);
        }

        $riverShowCommand = new ArrayInput($parameters);

        $application = $this->getApplication();

        if (is_null($application)) {
            return;
        }

        $this->output->writeln('');
        $this->output->writeln('<fg=yellow;options=bold>'.str_repeat('=', self::LENGTH_LINE).'</>');
        $this->output->writeln('<fg=yellow;options=bold>'.$title.'</>');
        $this->output->writeln('<fg=yellow;options=bold>'.str_repeat('=', self::LENGTH_LINE).'</>');
        $application->doRun($riverShowCommand, $this->output);
        $this->output->writeln(str_repeat('=', self::LENGTH_LINE));
    }

    /**
     * Calculates the levenshtein value.
     *
     * @param string $value1
     * @param string $value2
     * @return float
     */
    function levenshteinSimilarity(string $value1, string $value2): float
    {
        $length1 = mb_strlen($value1, 'UTF-8');
        $length2 = mb_strlen($value2, 'UTF-8');

        $levenshtein = levenshtein($value1, $value2);

        $maxLength = max($length1, $length2);

        if ($maxLength == 0) {
            return self::SIMILARITY_EQUAL;
        }

        return self::SIMILARITY_EQUAL - ($levenshtein / $maxLength);
    }

    /**
     * Calculates similarity between river names and location names.
     *
     * @param string[]|null $riverNames
     * @param string[]|null $locationNames
     * @return float
     */
    protected function calculateSimilarity(
        array|null $riverNames,
        array|null $locationNames
    ): float
    {
        if (is_null($riverNames) || is_null($locationNames)) {
            return self::NO_SIMILARITY;
        }

        $highestSimilarityValue = self::NO_SIMILARITY;
        $highestSimilarityPair = ['', ''];

        foreach ($riverNames as $river) {
            foreach ($locationNames as $location) {
                $similarity = $this->levenshteinSimilarity($river, $location);
                if ($similarity > $highestSimilarityValue) {
                    $highestSimilarityValue = $similarity;
                    $highestSimilarityPair = [$river, $location];
                }
            }
        }

        $this->output->writeln(sprintf('<comment>Highest similarity pair</comment>:  <info>%s</info>', implode(' and ', $highestSimilarityPair)));
        $this->output->writeln(sprintf('<comment>Highest similarity value</comment>: <info>%1.2f</info>', $highestSimilarityValue));

        return $highestSimilarityValue;
    }

    /**
     * Prints the location process header.
     *
     * @param Location $location
     * @param int $number
     * @return void
     */
    private function printLocationStartHeader(
        Location $location,
        int $number
    ): void
    {
        $this->output->writeln('');
        $this->output->writeln(
            sprintf(
                '<fg=cyan;options=bold>%s</>',
                str_repeat('=', self::LENGTH_LINE)
            )
        );
        $this->output->writeln(
            sprintf(
                '<fg=cyan;options=bold>%d) Start finding closest river... (%s - location id: %d)</>',
                $number,
                $location->getName(),
                $location->getId()
            )
        );
        $this->output->writeln(
            sprintf(
                '<fg=cyan;options=bold>%s</>',
                str_repeat('=', self::LENGTH_LINE)
            )
        );
    }

    /**
     * Prints location and river information.
     *
     * @param Location $location
     * @param River|null $river
     * @param float $similarity
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    private function printLocationRiverInformation(
        Location $location,
        River|null $river,
        float $similarity
    ): void
    {
        /* Print calculated similarity. */
        $this->output->writeln(sprintf(self::TEXT_CALCULATED_SIMILARITY, $similarity));

        /* Print location information. */
        $this->output->writeln(
            sprintf(
                self::TEXT_LOCATION_FOUND,
                $location->getName(),
                $location->getId(),
                $location->getCoordinateIxnode()->getString()
            )
        );

        /* Print river information. */
        if (!is_null($river)) {
            $this->output->writeln(
                sprintf(
                    self::TEXT_RIVER_FOUND,
                    $river->getName(),
                    $river->getId(),
                    $river->getClosestCoordinateIxnode()?->getString() ?? 'No coordinate found.',
                    $river->getClosestDistance()
                )
            );
            return;
        }

        $this->output->writeln(
            sprintf(
                self::TEXT_RIVER_NOT_FOUND,
                $location->getName()
            )
        );
    }

    /**
     * Asks for current settings.
     *
     * @param Location $location
     * @param River|null $river
     * @param float $distance
     * @param bool $debug
     * @return mixed
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws Throwable
     */
    private function ask(
        Location $location,
        River|null $river,
        float $distance,
        bool $debug
    ): mixed
    {
        $message = is_null($river) ?
            'Set ignoring mapping.' :
            sprintf(
                'Set mapping river.id %d to location.id %d.',
                $river->getId(),
                $location->getId()
            )
        ;

        $askMessage = match (true) {
            is_null($river) => 'No river found.',
            $distance > self::DISTANCE_MIN_ASK => sprintf(
                'Distance higher than %3.2f km (%3.2f km - %s).',
                self::DISTANCE_MIN_ASK,
                $distance,
                $river->getName()
            ),
            $debug => 'Debug is enabled.',
            default => null,
        };

        $this->showRivers($location, 'Show rivers with distance 20 km, limit 10 and with given position:');
        $this->showRivers($location, sprintf(
            'Show rivers with distance 20 km, limit 10, with given position and river names "%s":',
            is_null($river) ? 'no river was found' : implode(',', $river->getNames())
        ), $river?->getNames() ?? false);

        $this->output->writeln('');
        $this->output->writeln('<comment>Reason to ask:</comment>   <fg=#c0392b;options=bold>'.$askMessage.'</>');
        $this->output->writeln('<comment>Current mapping:</comment> <fg=#c0392b;options=bold>'.$message.'</>');

        $question = new ChoiceQuestion(
            sprintf(
                '%s<question>Do you want to use the current mapping?</question>%s'.
                '<info>[yes - use the current mapping, no - skip the current mapping, cancel - skip all and exit]</info>%s',
                PHP_EOL,
                PHP_EOL,
                PHP_EOL,
            ),
            [Question::YES, Question::NO, Question::CANCEL],
            0
        );
        $question->setErrorMessage('Answer %s is invalid.');

        $helper = $this->getHelper('question');

        if (!method_exists($helper, 'ask')) {
            throw new LogicException('Method "ask" not found.');
        }

        $answer = $helper->ask($this->input, $this->output, $question);
        $this->output->writeln('You have just selected: '.$answer);

        return $answer;
    }

    /**
     * Processes the given location.
     *
     * Returns:
     * - Location: The processed location.
     * - false: Current location was ignored.
     * - null: Cancel this command.
     *
     * @param Location $location
     * @param int $distanceMaxNextRiver
     * @param int $printLocationNumber
     * @param bool $force
     * @param bool $debug
     * @return Location|false|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws Throwable
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function doLocation(
        Location $location,
        int $distanceMaxNextRiver,
        int $printLocationNumber,
        bool $force,
        bool $debug
    ): Location|false|null
    {
        $this->printLocationStartHeader($location, $printLocationNumber);

        /* Try to find the closest river. */
        $river = $this->riverRepository->findRiver(
            coordinate: $location->getCoordinateIxnode(),
            riverNames: $location->getNames(),
            distanceMeter: $distanceMaxNextRiver
        );

        /* Set ignore mapping mode if no river was found. */
        $ignoreMapping = match (true) {
            is_null($river) => true,
            default => false,
        };

        /* Some properties are changed within $location and $river. Get the original entities. */
        $locationDb = $this->locationRepository->find($location->getId());
        $riverDb = is_null($river) ? null : $this->riverRepository->find($river->getId());

        if (is_null($locationDb)) {
            throw new LogicException('Unexpected location not found.');
        }

        $similarity = is_null($river) ?
            self::NO_SIMILARITY :
            $this->calculateSimilarity(
                $river->getNames(),
                $location->getNames(),
            )
        ;

        $this->printLocationRiverInformation(
            $location,
            $river,
            $similarity
        );

        $distance = is_null($river) ?
            self::DEFAULT_DISTANCE_NO_RIVER :
            ($river->getClosestDistance() ?? self::DEFAULT_DISTANCE_NO_RIVER)
        ;

        $ask = $debug || is_null($river) || ($distance > self::DISTANCE_MIN_ASK);

        if ($force) {
            $ask = false;
        }

        if ($ask) {
            $answer = $this->ask($location, $river, $distance, $debug);

            switch ($answer) {
                /* Cancel current import. */
                case Question::CANCEL:
                    return null;

                /* Set ignore mapping. */
                case Question::NO:
                    return false;

                /* Use the current setting mapping. */
                default:
                    break;
            }
        }

        if (!$locationDb instanceof Location) {
            throw new LogicException(sprintf('Location "%s" not found.', $location->getName()));
        }

        if (!$ignoreMapping && !$riverDb instanceof River) {
            throw new LogicException(sprintf('River "%s" not found.', $location->getName()));
        }

        $this->output->writeln(
            sprintf(
                'Set location "%d" to river "%s".',
                $locationDb->getId(),
                is_null($riverDb) ? 'ignore' : $riverDb->getId()
            )
        );

        if (is_null($riverDb) || is_null($river)) {
            $locationDb->setMappingRiverIgnore(true);
            return $locationDb;
        }

        $locationDb->setMappingRiverIgnore(false);
        $locationDb->setMappingRiverSimilarity((string) $similarity);
        $locationDb->setMappingRiverDistance((string) $river->getClosestDistance());
        $locationDb->addRiver($riverDb);

        return $locationDb;
    }

    /**
     * Process all locations.
     *
     * Returns:
     * - true: All locations were processed.
     * - null: Cancel this command.
     *
     * @param Location[] $locations
     * @return true|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws Throwable
     * @throws TypeInvalidException
     */
    private function doLocations(
        array $locations
    ): true|null
    {
        $force = (bool) $this->input->getOption(KeyArray::FORCE);
        $debug = (bool) $this->input->getOption(KeyArray::DEBUG);

        $distanceMaxNextRiver = $this->input->getOption(self::OPTION_NAME_DISTANCE_MAX_NEXT_RIVER);

        if (!is_int($distanceMaxNextRiver) && !is_string($distanceMaxNextRiver)) {
            throw new LogicException(sprintf('Option "%s" must be an integer or string.', self::OPTION_NAME_DISTANCE_MAX_NEXT_RIVER));
        }

        $distanceMaxNextRiver = (int) $distanceMaxNextRiver;

        foreach ($locations as $index => $location) {

            /* Process location. */
            $locationDb = $this->doLocation(
                location: $location,
                distanceMaxNextRiver: $distanceMaxNextRiver,
                printLocationNumber: $index + 1,
                force: $force,
                debug: $debug
            );

            /* Cancel current import. */
            if (is_null($locationDb)) {
                return null;
            }

            /* Ignore current location. */
            if ($locationDb === false) {
                continue;
            }

            $this->entityManager->persist($locationDb);
        }

        $this->entityManager->flush();

        /* All locations were processed. */
        return true;
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws TypeInvalidException
     * @throws Throwable
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $ignoreIgnored = (bool) $this->input->getOption(self::OPTION_NAME_IGNORE_IGNORED);

        $number = $input->getOption(KeyArray::NUMBER);
        if (!is_int($number) && !is_string($number)) {
            throw new LogicException(sprintf('Option "%s" must be an integer or string.', KeyArray::NUMBER));
        }
        $number = (int) $number;

        $country = $this->countryRepository->findOneBy(['code' => CountryDb::DE]);

        if (!$country instanceof Country) {
            throw new LogicException(sprintf('Country "%s" not found.', CountryDb::DE));
        }

        $locations = $this->locationRepository->findRiversWithoutMapping(
            limit: $number,
            country: $country,
            ignoreIgnored: $ignoreIgnored
        );

        if (count($locations) <= 0) {
            $this->output->writeln('No locations found to map.');
            return Command::SUCCESS;
        }

        $numberMapped = $this->doLocations($locations);

        /* Method doLocation was cancelled. */
        if (is_null($numberMapped)) {
            return Command::SUCCESS;
        }

        $output->writeln('All tasks done.');

        return Command::SUCCESS;
    }
}
