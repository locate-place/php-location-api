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

namespace App\ApiPlatform\Operation;

use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use Ixnode\PhpNamingConventions\NamingConventions;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Class CamelCaseToSeparatedConverter
 *
 * @author Björn Hempel <bjoern.hempel@ressourcenmangel.de>
 * @version 0.1.0 (2023-08-30)
 * @since 0.1.0 (2023-08-30) First version.
 * @description Used for config/packages/api_platform.yaml as name_converter.
 */
readonly class CamelCaseToSeparatedConverter implements NameConverterInterface
{
    /**
     * @param array<int, string>|null $attributes
     * @param bool $lowerCamelCase
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct(private readonly ?array $attributes = null, private readonly bool $lowerCamelCase = true)
    {
    }

    /**
     * @param string $propertyName
     * @return string
     * @throws FunctionReplaceException
     */
    public function normalize(string $propertyName): string
    {
        if (null === $this->attributes || in_array($propertyName, $this->attributes)) {
            return (new NamingConventions($propertyName))->getSeparated();
        }

        return $propertyName;
    }

    /**
     * @param string $propertyName
     * @return string
     * @throws FunctionReplaceException
     */
    public function denormalize(string $propertyName): string
    {
        $namingConverter = new NamingConventions($propertyName);

        $camelCasedName = $this->lowerCamelCase ? $namingConverter->getCamelCase() : $namingConverter->getPascalCase();

        if (null === $this->attributes || in_array($camelCasedName, $this->attributes)) {
            return $camelCasedName;
        }

        return $propertyName;
    }
}
