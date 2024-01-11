<?php

/*
 * This file is part of the https://gitlab.rsm-support.de/hamburg-energie/ordering-process-api project.
 *
 * (c) Björn Hempel <bjoern.hempel@ressourcenmangel.de>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\DataTypes;

use App\Constants\Key\KeyArray;
use App\DataTypes\Base\DataType;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;

/**
 * Class Coordinate
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 */
class Coordinate extends DataType
{
    private const DISTANCE_UNAVAILABLE_ON_EARTH = 50_000_000;

    /**
     * Returns the distance in meters.
     *
     * @return float
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     * @throws JsonException
     */
    public function getDistance(): float
    {
        $path = [KeyArray::DISTANCE, KeyArray::METERS, KeyArray::VALUE];

        if ($this->hasKey($path)) {
            return $this->getKeyFloat($path);
        }

        return (float) self::DISTANCE_UNAVAILABLE_ON_EARTH;
    }
}
