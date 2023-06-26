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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ixnode\PhpApiVersionBundle\Entity\Version as VersionOrigin;
use Ixnode\PhpApiVersionBundle\Repository\VersionRepository;

/**
 * Entity class Version
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-26)
 * @since 0.1.0 (2023-06-26) First version.
 */
#[ORM\Entity(repositoryClass: VersionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Version extends VersionOrigin
{
}
