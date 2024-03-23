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

use App\Repository\LocationRepository;
use App\Repository\RiverPartRepository;
use App\Repository\RiverRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddLocationMappingCommand.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-03-23)
 * @since 0.1.0 (2024-03-23) First version.
 */
class AddLocationMappingCommand extends Command
{
    protected static $defaultName = 'river:mapping';

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
            ->setDescription('Adds location mappings to river table.')
            ->setHelp(
                <<<'EOT'

The <info>river:mapping</info> adds location mappings to river table.

EOT
            );
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $coordinate = new Coordinate(51.058330, 13.741670);

        print sprintf('Coordinate: %s,%s', $coordinate->getLatitude(), $coordinate->getLongitude()).PHP_EOL;
        print PHP_EOL;

        $rivers = $this->riverRepository->findRivers(
            coordinate: $coordinate,
            distanceMeter: 2000,
            limit: 100
        );

        foreach ($rivers as $river) {
            print sprintf(
                '%-30s   %6s km   %10d   %f km   %s,%s',
                    $river->getName(),
                    round((float) $river->getLength(), 2),
                    $river->getRiverCode(),
                    $river->getDistance(),
                    $river->getClosestCoordinate()?->getLatitude(),
                    $river->getClosestCoordinate()?->getLongitude()
            ).PHP_EOL;
        }

//        exit();
//
//
//        $rivers = $this->locationRepository->findRiversWithoutMapping();
//
//        foreach ($rivers as $river) {
//            print $river->getName().PHP_EOL;
//        }

        return Command::SUCCESS;
    }
}
