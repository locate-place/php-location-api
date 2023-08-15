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

namespace App\Utils\Db;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Ixnode\PhpApiVersionBundle\Entity\Version;
use Ixnode\PhpApiVersionBundle\Repository\VersionRepository;
use Ixnode\PhpApiVersionBundle\Utils\Db\Repository as RepositoryIxnode;
use Ixnode\PhpException\Class\ClassInvalidException;

/**
 * Class Repository
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-01-01)
 * @since 0.1.0 (2023-01-01) First version.
 */
class Repository extends RepositoryIxnode
{
    /**
     * Returns the Entity class from given repository class.
     *
     * @param class-string $repositoryClassName
     * @return class-string
     * @throws ClassInvalidException
     */
    protected function getEntityClass(string $repositoryClassName): string
    {
        return match (true) {
            $repositoryClassName === LocationRepository::class => Location::class,
            $repositoryClassName === VersionRepository::class => Version::class,
            default => throw new ClassInvalidException($repositoryClassName, VersionRepository::class),
        };
    }
}
