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

namespace App\ApiPlatform\State;

use App\ApiPlatform\Resource\Example;
use App\ApiPlatform\Route\ExampleRoute;
use App\ApiPlatform\State\Base\BaseProviderCustom;
use App\Service\Example\ExampleService;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\ApiPlatform\State\Base\Wrapper\BaseResourceWrapperProvider;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ExampleProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 */
final class ExampleProvider extends BaseProviderCustom
{
    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     * @param TranslatorInterface $translator
     * @param ExampleService $exampleService
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request,
        protected TranslatorInterface $translator,
        protected ExampleService $exampleService
    )
    {
        parent::__construct($version, $parameterBag, $request);
    }

    /**
     * Returns the route properties according to current class.
     *
     * @return array<string, array<string, bool|int|string>>
     */
    protected function getRouteProperties(): array
    {
        return ExampleRoute::PROPERTIES;
    }

    /**
     * Returns a collection of example resources.
     *
     * @return BasePublicResource[]
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    private function doProvideGetCollection(): array
    {
        $examples = [];

        foreach ($this->exampleService->getExamples() as $example) {
            if (!is_array($example)) {
                throw new LogicException('The example must be an array.');
            }

            $example = (new Example())->setPlace($example);

            $examples[] = $example;
        }

        return $examples;
    }

    /**
     * Do the provided job.
     *
     * @return BasePublicResource|BasePublicResource[]
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    protected function doProvide(): BasePublicResource|array
    {
        switch (true) {

            /*
             * https://www.location-api.localhost/api/v1/example.json
             */
            case $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION:
                return $this->doProvideGetCollection();

            /*
             * Unsupported query type
             */
            default:
                $this->setError('Unsupported query type.');
                return [];
        }
    }
}
