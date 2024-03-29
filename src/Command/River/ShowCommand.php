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

use App\Constants\Key\KeyArray;
use App\Entity\Location;
use App\Repository\LocationRepository;
use App\Repository\RiverPartRepository;
use App\Repository\RiverRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ShowCommand.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-23)
 * @since 0.1.0 (2024-03-23) First version.
 *
 * @example bin/console river:show --position="51.120552, 13.132655" --distance=3000 --limit=4
 */
class ShowCommand extends Command
{
    protected static $defaultName = 'river:show';

    /**
     * @param EntityManagerInterface $entityManager
     * @param LocationRepository $locationRepository
     * @param RiverRepository $riverRepository
     * @param RiverPartRepository $riverPartRepository
     */
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly LocationRepository $locationRepository,
        protected readonly RiverRepository $riverRepository,
        protected readonly RiverPartRepository $riverPartRepository
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
                new InputOption(KeyArray::POSITION, null, InputOption::VALUE_OPTIONAL, 'The latitude value.', '51.058330,13.741670'),
                new InputOption(KeyArray::DISTANCE, null, InputOption::VALUE_OPTIONAL, 'The distance value (meters).', 2000),
                new InputOption(KeyArray::LIMIT, null, InputOption::VALUE_OPTIONAL, 'The limit value.', 100),
                new InputOption(KeyArray::RIVER_NAME, null, InputOption::VALUE_OPTIONAL, 'The river name value.', null),
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
     * Executes the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $position = $input->getOption(KeyArray::POSITION);
        $distance = $input->getOption(KeyArray::DISTANCE);
        $limit = $input->getOption(KeyArray::LIMIT);
        $riverName = $input->getOption(KeyArray::RIVER_NAME);

        if (!is_string($position)) {
            throw new LogicException('The given position is not a string.');
        }

        if (!is_string($distance) && !is_int($distance)) {
            throw new LogicException('The given distance is not a string or an integer.');
        }

        if (!is_string($limit) && !is_int($limit)) {
            throw new LogicException('The given limit is not a string or an integer.');
        }

        if (!is_null($riverName) && !is_string($riverName)) {
            throw new LogicException('The given name is not a string.');
        }

        $coordinate = new Coordinate($position);
        $distance = (int) $distance;
        $limit = (int) $limit;

        $time = microtime(true);
        $rivers = $this->riverRepository->findRivers(
            coordinate: $coordinate,
            riverNames: is_null($riverName) ? null : explode(Location::NAME_SEPARATOR, $riverName),
            distanceMeter: $distance,
            limit: $limit
        );
        $time = microtime(true) - $time;

        $output->writeln(sprintf('Coordinate: %s, %s (%3.4f seconds)'.PHP_EOL, $coordinate->getLatitude(), $coordinate->getLongitude(), $time));

        $output->writeln(sprintf(
            '%-10s %-42s   %-11s   %-10s   %-9s   %s',
            'ID',
            'Name',
            'Length',
            'River Code',
            'Distance',
            'Latitude,Longitude',
        ));
        $output->writeln(str_repeat('-', 120));
        foreach ($rivers as $river) {
            print sprintf(
                '%10d %-40s   %8.2f km   %12d   %6.2f km   %s,%s (%s)',
                    $river->getId(),
                    $this->getMbStrPad($river->getName() ?? '', 40),
                    round((float) $river->getLength(), 2),
                    $river->getRiverCode(),
                    $river->getDistance(),
                    $river->getClosestCoordinate()?->getLatitude(),
                    $river->getClosestCoordinate()?->getLongitude(),
                    $coordinate->getDirection(new Coordinate($river->getClosestCoordinate()?->getLatitude(), $river->getClosestCoordinate()?->getLongitude())),
            ).PHP_EOL;
        }
        print PHP_EOL;


        return Command::SUCCESS;
    }
}
