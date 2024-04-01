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

namespace App\DataTypes\Base;

use LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Class DataTypeNormalizer
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 */
class DataTypeNormalizer implements NormalizerInterface
{
    /**
     * Returns the normalized value for the given object and format.
     *
     * @param mixed $object
     * @param string|null $format
     * @param array<int|string, mixed> $context
     * @return array<int|string, mixed>
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if (!is_object($object)) {
            throw new LogicException(sprintf('Unsupported object type "%s"', gettype($object)));
        }

        if (!$object instanceof DataType) {
            throw new LogicException(sprintf('Unsupported object class "%s"', $object::class));
        }

        return $object->getArray();
    }

    /**
     * Returns whether this normalizer supports the given data and format.
     *
     * @inheritdoc
     */
    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof DataType;
    }
}
