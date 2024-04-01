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

namespace App\Utils\Feature;

use App\Constants\DB\FeatureClass as DbFeatureClass;
use App\Constants\DB\FeatureCode as DbFeatureCode;

/**
 * Class FeatureContainer
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-11)
 * @since 0.1.0 (2024-01-11) First version.
 */
readonly class FeatureContainer
{
    /**
     * @param array<int, string>|string|null $featureClasses
     * @param array<int, string>|string|null $featureCodes
     */
    public function __construct(
        private array|string|null $featureClasses,
        private array|string|null $featureCodes,
    )
    {
    }

    /**
     * Returns if the current feature class and feature code combination is a river, stream, lake, etc. location.
     *
     * @return bool
     */
    public function isGroupRiverLake(): bool
    {
        $isFeatureClass = $this->isFeatureClassStreamLake();
        $isFeatureCode = $this->isFeatureCodeStreamLake();

        if (is_null($isFeatureClass) && is_null($isFeatureCode)) {
            return false;
        }

        if (is_null($isFeatureClass)) {
            return $isFeatureCode;
        }

        if (is_null($isFeatureCode)) {
            return $isFeatureClass;
        }

        return $isFeatureClass && $isFeatureCode;
    }

    /**
     * Returns if the current feature code is a river.
     *
     * @return bool
     */
    public function isRiver(): bool
    {
        if (!is_null($this->featureClasses)) {
            return false;
        }

        if (is_null($this->featureCodes)) {
            return false;
        }

        if (is_string($this->featureCodes)) {
            return $this->featureCodes === DbFeatureCode::STM;
        }

        return $this->featureCodes === [DbFeatureCode::STM];
    }

    /**
     * Returns if the current feature code is a river.
     *
     * @return bool
     */
    public function containsRiver(): bool
    {
        if ($this->isFeatureClassStreamLake()) {
            return true;
        }

        if (is_null($this->featureCodes)) {
            return false;
        }

        if (is_string($this->featureCodes)) {
            return $this->featureCodes === DbFeatureCode::STM;
        }

        return in_array(DbFeatureCode::STM, $this->featureCodes);
    }

    /**
     * Returns the feature codes without the river feature code.
     *
     * @return array<int, string>|null
     */
    public function getFeatureCodesWithoutRiver(): array|null
    {
        $featureCodes = $this->featureCodes;

        if (is_null($featureCodes)) {
            return null;
        }

        if (is_string($featureCodes)) {
            $featureCodes = [$featureCodes];
        }

        $featureCodes = array_filter($featureCodes, fn($value) => $value !== DbFeatureCode::STM);

        return array_values($featureCodes);
    }

    /**
     * Returns if the current feature class search is a river, stream, lake, etc. search.
     *
     * @return bool|null
     */
    private function isFeatureClassStreamLake(): bool|null
    {
        /* Unable to determine feature class. */
        if (is_null($this->featureClasses)) {
            return null;
        }

        if ($this->featureClasses === DbFeatureClass::H) {
            return true;
        }

        if ($this->featureClasses === [DbFeatureClass::H]) {
            return true;
        }

        return false;
    }

    /**
     * Returns if the current feature code search is a river, stream, lake, etc. search.
     *
     * @return bool|null
     */
    private function isFeatureCodeStreamLake(): bool|null
    {
        $featureCodes = $this->featureCodes;

        /* Unable to determine feature code. */
        if (is_null($featureCodes)) {
            return null;
        }

        if (is_string($featureCodes)) {
            return in_array($featureCodes, DbFeatureCode::H);
        }

        foreach ($featureCodes as $featureCode) {
            if (!in_array($featureCode, DbFeatureCode::H)) {
                return false;
            }
        }

        return true;
    }
}
