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

namespace App\Tests\Api\Version;

use Exception;
use Ixnode\PhpApiVersionBundle\Constants\Api\ApiRoute;
use Ixnode\PhpApiVersionBundle\Constants\Api\ApiSchema;
use Ixnode\PhpApiVersionBundle\Tests\Api\Base\BaseApiTestCase;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpJsonSchemaValidator\Validator;
use JsonException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class VersionTest
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-01-01)
 * @since 0.1.0 (2023-01-01) First version.
 * @link VersionProvider
 * @link Version
 */
class VersionTest extends BaseApiTestCase
{
    /**
     * This method is called before class.
     *
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        self::initClientEnvironment();
    }

    /**
     * Test wrapper for api endpoint "/version".
     *
     * @test
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws JsonException
     */
    public function wrapper(): void
    {
        /* Arrange */
        $response = $this->doRequest(ApiRoute::VERSION_RESOURCE);

        $json = new Json($response->toArray());

        /* Act */
        $validator = new Validator($json, new File(ApiSchema::VERSION_RESOURCE_VALID));

        /* Assert */
        $this->assertTrue($this->validateAndWriteOutput($validator), BaseApiTestCase::MESSAGE_API_RESPONSE_INVALID);
    }
}
