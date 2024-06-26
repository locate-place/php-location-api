<?php

/*
* This file is part of the ixnode/php-api-version-bundle project.
*
* (c) Björn Hempel <https://www.hempel.li/>
*
* For the full copyright and license information, please view the LICENSE.md
* file that was distributed with this source code.
*/

declare(strict_types=1);

namespace App\Utils\Constant;

use App\Constants\Key\KeyArray;
use LogicException;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class IgnoreBuilder
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-28)
 * @since 0.1.0 (2024-02-28) First version.
 */
class IgnoreBuilder
{
    /** @var string[] $classPath */
    private array $classPath;

    /** @var array<string, array<string, string>> $ignoreValues */
    private array $ignoreValues = [];

    private const PATH_SRC = 'src';

    private const NAMESPACE_APP = 'App';

    private const DATE_FORMAT = 'Y-m-d H:i:s';

    private const NAME_CONSTANT_IGNORE = 'IGNORE';

    private const CLASS_TEMPLATE = <<<TEMPLATE
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

namespace App\%s;

/**
 * Class %s
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (%s)
 * @since 0.1.0 (%s) Automatically generated by IgnoreBuilder class.
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class %s
{
%s
}

TEMPLATE;


    /**
     * @param KernelInterface $kernel
     */
    public function __construct(
        private readonly KernelInterface $kernel
    )
    {
    }

    /**
     * Gets the class path.
     *
     * @return string[]
     */
    public function getClassPath(): array
    {
        if (!isset($this->classPath)) {
            throw new LogicException('Class path is not set. Use IgnoreBuilder::setClassPath() before.');
        }

        return $this->classPath;
    }

    /**
     * @param string[] $classPath
     * @return self
     */
    public function setClassPath(array $classPath): self
    {
        $this->classPath = $classPath;

        $this->setIgnoreValues();

        return $this;
    }

    /**
     * Sets the given ignore values from existing class.
     *
     * @return void
     */
    private function setIgnoreValues(): void
    {
        if (!isset($this->classPath)) {
            throw new LogicException('Class path is not set. Use IgnoreBuilder::setClassPath() before.');
        }

        /* Builds the class string. */
        $className = implode('\\', [self::NAMESPACE_APP, ...$this->getClassPath()]);

        /* Checks if the class exists. */
        if (!class_exists($className)) {
            throw new LogicException(sprintf('Class "%s" does not exist.', $className));
        }

        /* Try to get IGNORE constant. */
        try {
            $reflectionClass = new ReflectionClass($className);

            if ($reflectionClass->hasConstant(self::NAME_CONSTANT_IGNORE)) {
                $ignoreValues = $reflectionClass->getConstant(self::NAME_CONSTANT_IGNORE);

                if (!is_array($ignoreValues)) {
                    throw new LogicException(sprintf('Constant "%s" must be an array.', self::NAME_CONSTANT_IGNORE));
                }

                $this->ignoreValues = $ignoreValues;
                return;
            }
        } catch (ReflectionException) {
            $this->ignoreValues = [];
            return;
        }

        $this->ignoreValues = [];
    }

    /**
     * Returns all ignored values.
     *
     * @return array<string, array<string, string>>
     */
    public function getIgnoreValues(): array
    {
        if (!isset($this->classPath)) {
            throw new LogicException('Class path is not set. Use IgnoreBuilder::setClassPath() before.');
        }

        return $this->ignoreValues;
    }

    /**
     * Adds a new "ignore" variable.
     *
     * @param string $key
     * @param string $reason
     * @param bool $ignoreExistingKey
     * @return void
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function addIgnoreVariable(string $key, string $reason, bool $ignoreExistingKey = false): void
    {
        if (!$ignoreExistingKey) {
            if (array_key_exists($key, $this->ignoreValues)) {
                throw new LogicException(sprintf('Ignore variable "%s" already exists.', $key));
            }
        }

        $this->ignoreValues[$key] = [
            KeyArray::KEY => $key,
            KeyArray::REASON => $reason,
        ];

        /* Sort after assoziative key. */
        ksort($this->ignoreValues);

        /* Writes the new class. */
        file_put_contents($this->getClassPathString(), $this->getPhpCodeClass());
    }

    /**
     * Returns the path to the "ignore" class.
     *
     * @return string
     */
    private function getClassPathString(): string
    {
        if (!isset($this->classPath)) {
            throw new LogicException('Class path is not set. Use IgnoreBuilder::setClassPath() before.');
        }

        return sprintf(
            '%s/%s/%s.php',
            $this->kernel->getProjectDir(),
            self::PATH_SRC,
            implode('/', $this->getClassPath())
        );
    }

    /**
     * Returns the PHP code for the constant.
     *
     * @return string
     */
    private function getPhpCodeConstant(): string
    {
        $separator = '    ';

        $string = sprintf('%s%s = ['.PHP_EOL, $separator, sprintf('final public const %s', self::NAME_CONSTANT_IGNORE));
        foreach ($this->ignoreValues as $key => $value) {
            $string .= sprintf('%s\'%s\' => ['.PHP_EOL, str_repeat($separator, 2), $key);

            foreach ($value as $subKey => $subValue) {
                $string .= sprintf(
                    '%s\'%s\' => \'%s\','.PHP_EOL,
                    str_repeat($separator, 3),
                    $subKey,
                    $subValue
                );
            }

            $string .= sprintf('%s],'.PHP_EOL, str_repeat($separator, 2));
        }
        $string .= sprintf('%s];', $separator);

        return $string;
    }

    /**
     * Returns the PHP code for the class.
     *
     * @return string
     */
    public function getPhpCodeClass(): string
    {
        $path = $this->getClassPath();
        $class = array_pop($path);
        $namespace = implode('\\', $path);

        $date = date(self::DATE_FORMAT);

        return sprintf(
            self::CLASS_TEMPLATE,
            $namespace,
            $class,
            $date,
            $date,
            $class,
            $this->getPhpCodeConstant()
        );
    }
}
