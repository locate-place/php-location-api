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

namespace App\ApiPlatform\Resource;

use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;

/**
 * Class Base
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-31)
 * @since 0.1.0 (2023-07-31) First version.
 */
class Base extends BasePublicResource
{
    /** @var array<string, mixed> $all */
    protected array $all;

    /**
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->all;
    }

    /**
     * @param array<string, mixed> $all
     * @return self
     */
    public function setAll(array $all): self
    {
        $this->all = $all;

        return $this;
    }
}
