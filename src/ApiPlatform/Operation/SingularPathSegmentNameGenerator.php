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

use ApiPlatform\Operation\PathSegmentNameGeneratorInterface;
use Ixnode\PhpException\Type\TypeInvalidException;

/**
 * Class SingularPathSegmentNameGenerator
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-30)
 * @since 0.1.0 (2023-08-30) First version.
 */
final class SingularPathSegmentNameGenerator implements PathSegmentNameGeneratorInterface
{
    /**
     * @param string $name
     * @param bool $collection
     * @return string
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function getSegmentName(string $name, bool $collection = true): string
    {
        return $this->dashize($name);
    }

    /**
     * Returns the singular and dashed route name.
     *
     * @param string $string
     * @return string
     * @throws TypeInvalidException
     */
    private function dashize(string $string): string
    {
        $replacePattern = '~(?<=\\w)([A-Z])~';

        $dashized = preg_replace($replacePattern, '-$1', $string);

        if (!is_string($dashized)) {
            throw new TypeInvalidException('string', 'null');
        }

        return strtolower($dashized);
    }
}
