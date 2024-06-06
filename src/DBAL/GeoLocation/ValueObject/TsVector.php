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

namespace App\DBAL\GeoLocation\ValueObject;

/**
 * Class TsVector
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.1 (2024-04-07)
 * @since 0.1.0 (2024-04-07) First version.
 */
readonly class TsVector
{
    /** @var array<string, int> $tsvector */
    private array $tsvector;

    /**
     * @param string $tsvectorString
     */
    public function __construct(private string $tsvectorString)
    {
        $this->tsvector = $this->parseTsVectorString($tsvectorString);
    }

    /**
     * Returns the tsvector array.
     *
     * @return array<string, int>
     */
    public function getTsVector(): array
    {
        return $this->tsvector;
    }

    /**
     * Returns the tsvector string.
     *
     * @return string
     */
    public function getTsVectorString(): string
    {
        return $this->tsvectorString;
    }

    /**
     * Returns the array of tsvector string representation.
     *
     * @param string $tsvectorString
     * @return array<string, int>
     */
    private function parseTsVectorString(string $tsvectorString): array
    {
        $result = [];
        $pairs = explode(' ', $tsvectorString);

        foreach ($pairs as $pair) {
            [$key, $value] = explode(':', $pair);

            $result[$key] = (int) $value;
        }

        return $result;
    }
}
