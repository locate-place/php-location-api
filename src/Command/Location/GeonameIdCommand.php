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

namespace App\Command\Location;

use App\Command\Base\Base;
use App\Service\LocationService;
use Exception;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpContainer\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 * Class GeonameIdCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-27)
 * @since 0.1.0 (2023-06-27) First version.
 * @example bin/console location:geoname-id [coordinate]
 * @example bin/console location:geoname-id 2956832
 */
class GeonameIdCommand extends Base
{
    protected static string $defaultName = 'location:geoname-id';

    private const ARGUMENT_NAME_GEONAME_ID = 'geoname-id';

    private const OPTION_NAME_VERBOSE = 'verbose';

    private readonly Serializer $serializer;

    /**
     * @param LocationService $locationService
     */
    public function __construct(protected LocationService $locationService)
    {
        $this->serializer = $this->getSerializer();

        parent::__construct();
    }

    /**
     * Returns the symfony serializer.
     *
     * @return Serializer
     */
    private function getSerializer(): Serializer
    {
        $normalizers = [new ObjectNormalizer()];
        $encoders = [new JsonEncoder()];
        return new Serializer($normalizers, $encoders);
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setName((string) self::$defaultName)
            ->setDescription('Returns some information about the given coordinate.')
            ->setDefinition([
                new InputArgument(self::ARGUMENT_NAME_GEONAME_ID, InputArgument::REQUIRED, 'The geoname id of the location.'),
            ])
            ->setHelp(
                <<<'EOT'

The <info>location:coordinate</info> command returns some information about the given geoname id.

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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->input = $input;

        $geonameId = (new TypeCastingHelper($input->getArgument(self::ARGUMENT_NAME_GEONAME_ID)))->intval();

        $verbose = (bool) $input->getOption(self::OPTION_NAME_VERBOSE);

        $location = $this->locationService->getLocationByGeonameId(
            /* Search */
            geonameId: $geonameId,

            /* Configuration */
            addLocations: true,
            addNextPlacesConfig: true,
        );

        $jsonContent = $this->serializer->serialize($location, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['meters']]);
        $json = new Json($jsonContent);

        if (!$verbose) {
            $this->output->writeln($json->getJsonStringFormatted());
            return Command::SUCCESS;
        }

        $this->printAndLog(sprintf('Given geoname id: %d', $geonameId));
        $this->printAndLog($json->getJsonStringFormatted());

        /* Command successfully executed. */
        return Command::SUCCESS;
    }
}
