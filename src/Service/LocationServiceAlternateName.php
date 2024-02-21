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

namespace App\Service;

use App\Entity\AlternateName;
use App\Entity\Location;
use App\Repository\AlternateNameRepository;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class LocationServiceAlternateName
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-28)
 * @since 0.1.0 (2023-08-28) First version.
 */
class LocationServiceAlternateName
{
    /**
     * @param AlternateNameRepository $alternateNameRepository
     */
    public function __construct(protected AlternateNameRepository $alternateNameRepository)
    {
    }

    /**
     * Returns the alternate name by given iso language.
     *
     * @param Location|null $location
     * @param string|null $isoLanguage
     * @param string|null $language
     * @return string
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getNameByIsoLanguage(?Location $location, string $isoLanguage = null, string $language = null): string
    {
        if (is_null($location)) {
            return 'n/a';
        }

        if (is_null($isoLanguage)) {
            return (string) $location->getName();
        }

        $alternateName = $this->alternateNameRepository->findOneByIsoLanguage($location, $isoLanguage, $language);

        if ($alternateName instanceof AlternateName) {
            $name = $alternateName->getAlternateName();

            if (!is_null($name)) {
                return $name;
            }
        }

        return (string) $location->getName();
    }
}
