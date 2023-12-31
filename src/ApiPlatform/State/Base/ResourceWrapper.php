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

use Ixnode\PhpApiVersionBundle\ApiPlatform\State\Base\Wrapper\ResourceWrapper as IxnodeResourceWrapper;

/**
 * Class ResourceWrapper
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-30)
 * @since 0.1.0 (2023-08-30) First version.
 */
class ResourceWrapper extends IxnodeResourceWrapper
{
    /** @var array{full: string, short: string, url: string} $dataLicence */
    protected array $dataLicence;

    private string $timeTaken;

    private string $memoryTaken;

    /**
     * @return array{full: string, short: string, url: string}
     */
    public function getDataLicence(): array
    {
        return $this->dataLicence;
    }

    /**
     * @param array{full: string, short: string, url: string} $dataLicence
     * @return self
     */
    public function setDataLicence(array $dataLicence): self
    {
        $this->dataLicence = $dataLicence;

        return $this;
    }

    /**
     * @return string
     */
    public function getTimeTaken(): string
    {
        return $this->timeTaken;
    }

    /**
     * @param string $timeTaken
     * @return self
     */
    public function setTimeTaken(string $timeTaken): self
    {
        $this->timeTaken = $timeTaken;

        return $this;
    }

    /**
     * @return string
     */
    public function getMemoryTaken(): string
    {
        return $this->memoryTaken;
    }

    /**
     * @param string $memoryTaken
     * @return self
     */
    public function setMemoryTaken(string $memoryTaken): self
    {
        $this->memoryTaken = $memoryTaken;

        return $this;
    }
}
