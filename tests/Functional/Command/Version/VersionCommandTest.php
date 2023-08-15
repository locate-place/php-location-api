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

namespace App\Tests\Functional\Command\Version;

use App\Utils\Version\Version;
use Ixnode\PhpApiVersionBundle\Command\Version\VersionCommand;
use Ixnode\PhpApiVersionBundle\Constants\Command\CommandSchema;
use Ixnode\PhpApiVersionBundle\Tests\Functional\Command\Base\BaseFunctionalCommandTest;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpJsonSchemaValidator\Validator;
use JsonException;

/**
 * Class VersionCommandTest
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-01-01)
 * @since 0.1.0 (2023-01-01) First version.
 * @link VersionCommand
 */
class VersionCommandTest extends BaseFunctionalCommandTest
{
    /**
     * @return void
     */
    public function doConfig(): void
    {
        $this
            ->setConfigUseDb()
            ->setConfigUseParameterBag()
            ->setConfigUseKernel()
            ->setConfigUseCommand(
                VersionCommand::COMMAND_NAME,
                VersionCommand::class,
                fn () => [
                    new Version($this->getProjectDir()),
                    self::$kernel,
                    $this->entity->getEntityManager()
                ]
            );
    }

    /**
     * Test wrapper (KeyCommand).
     *
     * @test
     * @throws JsonException
     * @throws FileNotFoundException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws FileNotReadableException
     */
    public function wrapper(): void
    {
        /* Arrange */
        $this->commandTester->execute(['--format' => 'json']);
        $json = new Json($this->commandTester->getDisplay());

        /* Act */
        $validator = new Validator($json, new File(CommandSchema::VERSION_RESOURCE));

        /* Assert */
        $this->assertTrue($this->validateAndWriteOutput($validator), BaseFunctionalCommandTest::MESSAGE_JSON_RESPONSE_INVALID);
    }
}
