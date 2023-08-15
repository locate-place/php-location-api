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

namespace App\Command\Location;

use App\Command\Base\Base;
use App\Constants\Place\Search;
use Exception;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpContainer\Json;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 * Class TestCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-14)
 * @since 0.1.0 (2023-08-14) First version.
 * @example bin/console location:test [search]
 * @example bin/console location:test cecilienhof
 */
class TestCommand extends Base
{
    final public const COMMAND_NAME = 'location:test';

    final public const ARGUMENT_NAME_SEARCH = 'search';

    private const OPTION_NAME_VERBOSE = 'verbose';

    private const OPTION_NAME_DEBUG = 'debug';

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Returns some information about the given coordinate.')
            ->setDefinition([
                new InputArgument(self::ARGUMENT_NAME_SEARCH, InputArgument::REQUIRED, 'The search valur of the coordinate to test.'),
            ])
            ->addOption(self::OPTION_NAME_DEBUG, 'd', InputOption::VALUE_NONE, 'Shows debug information.')
            ->setHelp(
                <<<'EOT'

The <info>location:test</info> command returns some information about the given search value (via <info>location:coordinate</info>).

EOT
            );
    }

    /**
     * Execute the commands.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Exception
     * @throws ExceptionInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->input = $input;

        $search = (new TypeCastingHelper($input->getArgument(self::ARGUMENT_NAME_SEARCH)))->strval();

        if (!array_key_exists($search, Search::VALUES)) {
            $this->output->writeln('<error>The given search value is not supported.</error>');
            return Command::INVALID;
        }

        $search = new Json(Search::VALUES[$search]);

        $latitude = $search->getKeyFloat(['coordinate', 'latitude']);
        $longitude = $search->getKeyFloat(['coordinate', 'longitude']);

        $verbose = (bool) $input->getOption(self::OPTION_NAME_VERBOSE);
        $debug = (bool) $input->getOption(self::OPTION_NAME_DEBUG);

        $application = $this->getApplication();

        if (!$application instanceof Application) {
            $this->output->writeln('<error>Unable to get application.</error>');
            return Command::INVALID;
        }

        $coordinateCommand = $application->find(CoordinateCommand::COMMAND_NAME);

        $coordinateArguments = [
            CoordinateCommand::ARGUMENT_NAME_LATITUDE => (string) $latitude,
            CoordinateCommand::ARGUMENT_NAME_LONGITUDE => (string) $longitude,
            sprintf('--%s', self::OPTION_NAME_VERBOSE)  => $verbose,
            sprintf('--%s', self::OPTION_NAME_DEBUG)  => $debug,
        ];

        $coordinateInput = new ArrayInput($coordinateArguments);

        return $coordinateCommand->run($coordinateInput, $output);
    }
}
