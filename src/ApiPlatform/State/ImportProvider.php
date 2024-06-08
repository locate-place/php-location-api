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

use App\ApiPlatform\OpenApiContext\Name;
use App\ApiPlatform\Resource\Import;
use App\ApiPlatform\Route\ImportRoute;
use App\ApiPlatform\State\Base\BaseProviderCustom;
use App\Constants\DB\Format;
use App\Entity\Country;
use App\Entity\Import as ImportEntity;
use App\Repository\ImportRepository;
use App\Repository\LocationRepository;
use App\Service\LocationService;
use App\Utils\Api\ApiLogger;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\ApiPlatform\State\Base\Wrapper\BaseResourceWrapperProvider;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpException\Type\TypeInvalidException;
use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ImportProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-07-22)
 * @since 0.1.0 (2023-07-22) First version.
 */
final class ImportProvider extends BaseProviderCustom
{
    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     * @param LocationService $locationService
     * @param TranslatorInterface $translator
     * @param EntityManagerInterface $entityManager
     * @param ApiLogger $apiLogger
     * @param ImportRepository $importRepository
     * @param LocationRepository $locationRepository
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request,
        protected LocationService $locationService,
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $entityManager,
        protected ApiLogger $apiLogger,
        protected ImportRepository $importRepository,
        protected LocationRepository $locationRepository,
    )
    {
        parent::__construct(
            $version,
            $parameterBag,
            $request,
            $locationService,
            $translator,
            $entityManager,
            $apiLogger
        );
    }

    /**
     * Returns the route properties according to current class.
     *
     * @return array<string, array<string, int|string|string[]>>
     */
    protected function getRouteProperties(): array
    {
        return ImportRoute::PROPERTIES;
    }

    /**
     * Translates an Import entity to an Import resource.
     *
     * @param ImportEntity $importEntity
     * @return Import
     * @throws ArrayKeyNotFoundException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    private function getImport(ImportEntity $importEntity): Import
    {
        $format = $this->hasFilter(Name::FORMAT) ? $this->getFilterString(Name::FORMAT) : Format::SIMPLE;

        $country = $importEntity->getCountry();

        if (!$country instanceof Country) {
            throw new ClassInvalidException(Country::class, Country::class);
        }

        $countryCode = (string) $country->getCode();

        $import = (new Import())
            ->setCountry((string) $country->getName())
            ->setCountryCode($countryCode)
        ;

        if ($format === Format::SIMPLE) {
            return $import;
        }

        $createdAt = $importEntity->getCreatedAt();

        if (!$createdAt instanceof DateTimeImmutable) {
            throw new ClassInvalidException(DateTimeImmutable::class, DateTimeImmutable::class);
        }

        $updatedAt = $importEntity->getUpdatedAt();

        if (!$updatedAt instanceof DateTimeImmutable) {
            throw new ClassInvalidException(DateTimeImmutable::class, DateTimeImmutable::class);
        }

        return $import
            ->setPath((string) $importEntity->getPath())
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($updatedAt)
            ->setExecutionTime((int) $importEntity->getExecutionTime())
        ;
    }

    /**
     * Returns a collection of import resources that have been imported.
     *
     * @return BasePublicResource[]
     * @throws ArrayKeyNotFoundException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function doProvideGetCollection(): array
    {
        /** @var Import[] $imports */
        $imports = [];

        $countAllAlternateNames = [];
        $countAllLocations = [];

        $importEntities = $this->importRepository->findBy([], ['path' => 'ASC']);

        foreach ($importEntities as $importEntity) {
            if (!$importEntity instanceof ImportEntity) {
                continue;
            }

            $country = $importEntity->getCountry();

            if (!$country instanceof Country) {
                throw new ClassInvalidException(Country::class, Country::class);
            }

            $countryCode = (string) $country->getCode();

            /* All alternate name imports. */
            if (str_contains($importEntity->getPath() ?? '', 'import/alternate-name')) {
                $countAlternateName = $importEntity->getRows() ?: 0;

                if (!array_key_exists($countryCode, $countAllAlternateNames)) {
                    $countAllAlternateNames[$countryCode] = 0;
                }

                if ($countAlternateName <= 0) {
                    continue;
                }

                $countAllAlternateNames[$countryCode] += $countAlternateName;
                continue;
            }

            /* Add location imports. */
            if (str_contains($importEntity->getPath() ?? '', 'import/location')) {
                $countLocation = $importEntity->getRows() ?: 0;
                // $countLocation = $this->locationRepository->getNumberOfLocations($country);

                if (!array_key_exists($countryCode, $countAllLocations)) {
                    $countAllLocations[$countryCode] = 0;
                }

                $countAllLocations[$countryCode] += $countLocation;

                $imports[$countryCode] = $this->getImport($importEntity);

                $imports[$countryCode]->setNumberOfAlternateNames(0);

                continue;
            }

            throw new LogicException('Unsupported path: '. $importEntity->getPath());
        }

        /* Add the number of alternate names. */
        foreach ($countAllAlternateNames as $countryCode => $count) {
            if (!array_key_exists($countryCode, $imports)) {
                continue;
            }

            $imports[$countryCode]->setNumberOfAlternateNames($count);
        }

        /* Add the number of locations. */
        foreach ($countAllLocations as $countryCode => $count) {
            if (!array_key_exists($countryCode, $imports)) {
                continue;
            }

            $imports[$countryCode]->setNumberOfLocations($count);
        }

        return array_values($imports);
    }

    /**
     * Do the provided job.
     *
     * @return BasePublicResource|BasePublicResource[]
     * @throws ArrayKeyNotFoundException
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     * @throws TypeInvalidException
     */
    protected function doProvide(): BasePublicResource|array
    {
        return match(true) {
            $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION => $this->doProvideGetCollection(),
            default => throw new CaseUnsupportedException('Unsupported mode from api endpoint /api/v1/import.'),
        };
    }
}
