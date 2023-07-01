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

namespace App\ApiPlatform\State\Base;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use App\ApiPlatform\Route\LocationRoute;
use App\ApiPlatform\State\LocationProvider;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\Utils\TypeCasting\TypeCastingHelper;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class BaseProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-01)
 * @since 0.1.0 (2023-07-01) First version.
 * @implements ProviderInterface<BasePublicResource>
 */
abstract class BaseProvider implements ProviderInterface, ProcessorInterface
{
    protected InputInterface $input;

    /** @var array<string, InputArgument|InputOption> $inputArguments */
    protected array $inputArguments = [];

    /** @var array<string, mixed> $inputArgumentValues */
    protected array $inputArgumentValues = [];

    protected ?string $error = null;

    final public const NAME_KERNEL_PROJECT_DIR = 'kernel.project_dir';

    protected const TEXT_UNDEFINED_METHOD = 'Please overwrite the "%s" method in your provider to use this function.';

    /**
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     */
    public function __construct(protected ParameterBagInterface $parameterBag, protected RequestStack $request)
    {
        $this->input = new ArrayInput([]);
    }

    /**
     * Do the provided job and returns the base resource.
     *
     * @return BasePublicResource|BasePublicResource[]
     * @throws CaseUnsupportedException
     * @noRector
     */
    protected function doProvide(): BasePublicResource|array
    {
        throw new CaseUnsupportedException(sprintf(self::TEXT_UNDEFINED_METHOD, __METHOD__));
    }

    /**
     * Do the processed job and returns the resource wrapper.
     *
     * @return BasePublicResource
     * @throws CaseUnsupportedException
     * @noRector
     */
    protected function doProcess(): BasePublicResource
    {
        throw new CaseUnsupportedException(sprintf(self::TEXT_UNDEFINED_METHOD, __METHOD__));
    }

    /**
     * Binds given input definitions.
     *
     * @param InputArgument[]|InputOption[] $inputs
     * @return void
     */
    private function bindInputDefinition(array $inputs): void
    {
        $inputDefinition = new InputDefinition($inputs);

        $this->input->bind($inputDefinition);
    }

    /**
     * Sets given argument.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setArgument(string $name, mixed $value): void
    {
        $this->inputArguments[$name] = new InputArgument($name);

        $this->inputArgumentValues[$name] = $value;
    }

    /**
     * @return InputInterface
     */
    public function getInputInterface(): InputInterface
    {
        $this->bindInputDefinition(array_values($this->inputArguments));

        foreach ($this->inputArguments as $name => $input) {
            match (true) {
                $input instanceof InputArgument => $this->input->setArgument($name, $this->inputArgumentValues[$name]),
                $input instanceof InputOption => $this->input->setOption($name, $this->inputArgumentValues[$name]),
            };
        }

        return $this->input;
    }

    /**
     * Returns the route properties according to current class.
     *
     * @return array<string, array<string, bool|int|string|string[]>>
     * @throws ClassInvalidException
     */
    protected function getRouteProperties(): array
    {
        return match (static::class) {
            LocationProvider::class => LocationRoute::PROPERTIES,
            default => throw new ClassInvalidException(static::class, BaseProvider::class)
        };
    }

    /**
     * Gets the project directory.
     *
     * @return string
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function getProjectDir(): string
    {
        if (!$this->parameterBag->has(self::NAME_KERNEL_PROJECT_DIR)) {
            throw new ArrayKeyNotFoundException(self::NAME_KERNEL_PROJECT_DIR);
        }

        return (new TypeCastingHelper($this->parameterBag->get(self::NAME_KERNEL_PROJECT_DIR)))->strval();
    }

    /**
     * Returns the current request.
     *
     * @return Request
     * @throws CaseUnsupportedException
     */
    protected function getCurrentRequest(): Request
    {
        $currentRequest = $this->request->getCurrentRequest();

        if (is_null($currentRequest)) {
            throw new CaseUnsupportedException('Can\'t get the CurrentRequest class(<code>$this->getRequest()->getCurrentRequest();</code>).');
        }

        return $currentRequest;
    }

    /**
     * Returns the header bag.
     *
     * @return HeaderBag
     * @throws CaseUnsupportedException
     */
    protected function getHeaderBag(): HeaderBag
    {
        $currentRequest = $this->getCurrentRequest();

        return $currentRequest->headers;
    }

    /**
     * Returns if the given name exists as a header request.
     *
     * @param string $name
     * @return bool
     * @throws CaseUnsupportedException
     */
    public function hasHeader(string $name): bool
    {
        $headerBag = $this->getHeaderBag();

        return $headerBag->has($name);
    }

    /**
     * Returns the header bag request.
     *
     * @param string $name
     * @return string|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseUnsupportedException
     */
    public function getHeader(string $name): ?string
    {
        if (!$this->hasHeader($name)) {
            throw new ArrayKeyNotFoundException($name);
        }

        return $this->getHeaderBag()->get($name);
    }

    /**
     * Returns the given name from header (as string).
     *
     * @param string $name
     * @return string|null
     * @throws CaseUnsupportedException
     */
    public function getHeaderAsStringOrNull(string $name): ?string
    {
        if (!$this->hasHeader($name)) {
            return null;
        }

        return strval($this->getHeaderBag()->get($name));
    }

    /**
     * Returns the given name from header (as string).
     *
     * @param string $name
     * @return string
     * @throws CaseUnsupportedException
     */
    public function getHeaderAsString(string $name): string
    {
        if (!$this->hasHeader($name)) {
            throw new CaseUnsupportedException(sprintf('Header missing "%s"', $name));
        }

        return strval($this->getHeaderBag()->get($name));
    }

    /**
     * Returns the given name from header (as float).
     *
     * @param string $name
     * @return float|null
     * @throws CaseUnsupportedException
     */
    public function getHeaderAsFloatOrNull(string $name): ?float
    {
        if (!$this->hasHeader($name)) {
            return null;
        }

        return floatval($this->getHeaderBag()->get($name));
    }

    /**
     * Returns the given name from header (as float).
     *
     * @param string $name
     * @return float
     * @throws CaseUnsupportedException
     */
    public function getHeaderAsFloat(string $name): float
    {
        if (!$this->hasHeader($name)) {
            throw new CaseUnsupportedException(sprintf('Header missing "%s"', $name));
        }

        return floatval($this->getHeaderBag()->get($name));
    }

    /**
     * Returns the given name from header (as integer).
     *
     * @param string $name
     * @return int|null
     * @throws CaseUnsupportedException
     */
    public function getHeaderAsIntOrNull(string $name): ?int
    {
        if (!$this->hasHeader($name)) {
            return null;
        }

        return intval($this->getHeaderBag()->get($name));
    }

    /**
     * Returns the given name from header (as integer).
     *
     * @param string $name
     * @return int
     * @throws CaseUnsupportedException
     */
    public function getHeaderAsInt(string $name): int
    {
        if (!$this->hasHeader($name)) {
            throw new CaseUnsupportedException(sprintf('Header missing "%s"', $name));
        }

        return intval($this->getHeaderBag()->get($name));
    }

    /**
     * Returns the given name from header (as bool).
     *
     * @param string $name
     * @return bool
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws TypeInvalidException
     */
    public function isHeaderAsBoolean(string $name): bool
    {
        if (!$this->hasHeader($name)) {
            return false;
        }

        $value = (new TypeCastingHelper($this->getHeaderBag()->get($name)))->strval();

        return match ($value) {
            'true' => true,
            'false' => false,
            default => throw new CaseInvalidException($value, ['true', 'false']),
        };
    }

    /**
     * Gets an error of this resource.
     *
     * @return string|null
     */
    protected function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Sets an error of this resource.
     *
     * @param string|null $error
     * @return self
     */
    protected function setError(?string $error): self
    {
        $this->error = $error;

        return $this;
    }
}
