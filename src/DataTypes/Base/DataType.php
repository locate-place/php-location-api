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

namespace App\DataTypes\Base;

use Ixnode\PhpContainer\Json;

/**
 * Class DataType
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 */
abstract class DataType extends Json
{
    /**
     * @inheritdoc
     */
    public function __construct(object|array|string $json = [])
    {
        parent::__construct($json);
    }
}
