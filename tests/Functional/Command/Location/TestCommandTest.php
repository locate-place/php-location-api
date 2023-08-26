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

namespace App\Tests\Functional\Command\Location;

use App\Command\Location\CoordinateCommand;
use App\Constants\Command\CommandSchema;
use App\Constants\Place\Search;
use App\Repository\LocationRepository;
use App\Service\LocationCountryService;
use App\Service\LocationService;
use App\Utils\Db\Repository;
use Ixnode\PhpApiVersionBundle\Command\Version\VersionCommand;
use Ixnode\PhpApiVersionBundle\Tests\Functional\Command\Base\BaseFunctionalCommandTest;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpJsonSchemaValidator\Validator;
use JsonException;

/**
 * Class TestCommandTest
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-14)
 * @since 0.1.0 (2023-08-14) First version.
 * @link VersionCommand
 */
class TestCommandTest extends BaseFunctionalCommandTest
{
    /**
     * @return void
     */
    public function doConfig(): void
    {
        $this
            ->setServiceRepositoryClass(Repository::class)
            ->setConfigUseDb()
            ->setConfigUseParameterBag()
            ->setConfigUseRequestStack()
            ->setConfigUseTranslator()
            ->setConfigUseCommand(
                commandName: CoordinateCommand::COMMAND_NAME,
                commandClass: CoordinateCommand::class,
                commandClassParameterClosure: fn() => [new LocationService(
                    new Version($this->getProjectDir()),
                    $this->parameterBag,
                    $this->request,
                    $this->repository->getRepository(LocationRepository::class),
                    $this->translator,
                    new LocationCountryService($this->parameterBag)
                )]
            );
    }

    /**
     * Test wrapper (KeyCommand).
     *
     * @dataProvider dataProvider
     *
     * @test
     * @param int $number
     * @param string $key
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     */
    public function wrapper(
        int $number,
        string $key
    ): void
    {
        /* Arrange */
        $search = new Json(Search::VALUES[$key]);
        $latitude = $search->getKeyFloat(['coordinate', 'latitude']);
        $longitude = $search->getKeyFloat(['coordinate', 'longitude']);
        $location = $search->getKeyArray(['location']);

        $this->commandTester->execute([
            CoordinateCommand::ARGUMENT_NAME_LATITUDE => $latitude,
            CoordinateCommand::ARGUMENT_NAME_LONGITUDE => $longitude,
        ]);
        $json = new Json($this->commandTester->getDisplay());

        /* Act */
        $validator = new Validator($json, new File(CommandSchema::COORDINATE_TEST_RESOURCE));

        /* Assert */
        $this->assertIsNumeric($number); // To avoid phpmd warning.
        $this->assertTrue($this->validateAndWriteOutput($validator), BaseFunctionalCommandTest::MESSAGE_JSON_RESPONSE_INVALID);
        $this->assertEquals($location, $json->getKeyArray(['data', 'location']));
    }

    /**
     * Data provider (simple).
     *
     * @return array<int, array<int, string|int|float|null|array<string, mixed>>>
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function dataProvider(): array
    {
        $number = 0;

        $data = [];

        foreach (Search::VALUES as $key => $value) {
            $data[] = [
                ++$number,
                $key,
            ];
        }

        return $data;
    }
}
