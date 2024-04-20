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

use App\Entity\AlternateName;
use App\Entity\Country;
use App\Entity\Location;
use App\Entity\RiverPart;
use App\Entity\ZipCode;
use App\Entity\ZipCodeArea;
use App\Repository\AlternateNameRepository;
use App\Repository\CountryRepository;
use App\Repository\LocationRepository;
use App\Repository\RiverPartRepository;
use App\Repository\ZipCodeAreaRepository;
use App\Repository\ZipCodeRepository;
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
            $repositoryClassName === AlternateNameRepository::class => AlternateName::class,
            $repositoryClassName === CountryRepository::class => Country::class,
            $repositoryClassName === LocationRepository::class => Location::class,
            $repositoryClassName === RiverPartRepository::class => RiverPart::class,
            $repositoryClassName === VersionRepository::class => Version::class,
            $repositoryClassName === ZipCodeRepository::class => ZipCode::class,
            $repositoryClassName === ZipCodeAreaRepository::class => ZipCodeArea::class,
            default => throw new ClassInvalidException($repositoryClassName, VersionRepository::class),
        };
    }
}
