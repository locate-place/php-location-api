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

namespace App\ApiPlatform\State;

use App\ApiPlatform\State\Base\BaseProviderCustom;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Version as VersionResource;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Route\VersionRoute;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;

/**
 * Class VersionProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-08)
 * @since 0.1.0 (2024-06-08) First version.
 */
final class VersionProvider extends BaseProviderCustom
{
    /**
     * Returns the route properties according to current class.
     *
     * @return array<string, array<string, int|string|string[]>>
     */
    protected function getRouteProperties(): array
    {
        return VersionRoute::PROPERTIES;
    }

    /**
     * Do the provided job and returns the base resource.
     *
     * @inheritdoc
     * @throws FileNotFoundException
     * @throws ArrayKeyNotFoundException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     * @throws FileNotReadableException
     * @throws CaseInvalidException
     * @throws JsonException
     */
    protected function doProvide(): BasePublicResource
    {
        return (new VersionResource())
            ->setName($this->version->getName())
            ->setDescription($this->version->getDescription())
            ->setAuthors($this->version->getAuthors())
            ->setLicense($this->version->getLicense())
            ->setVersion($this->version->getVersion())
            ->setDate($this->version->getDate());
    }

    /**
     * Do the processed job and returns the resource wrapper.
     *
     * @inheritdoc
     */
    protected function doProcess(): BasePublicResource
    {
        return new VersionResource();
    }
}
