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

namespace App\ApiPlatform\State;

use App\ApiPlatform\Resource\ImportMissing;
use App\ApiPlatform\Route\ImportRoute;
use App\ApiPlatform\State\Base\BaseProviderCustom;
use App\Constants\Language\LocaleCode;
use App\Entity\Country;
use App\Entity\Import as ImportEntity;
use App\Repository\ImportRepository;
use App\Repository\LocationRepository;
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\ApiPlatform\State\Base\Wrapper\BaseResourceWrapperProvider;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Class\ClassInvalidException;
use Ixnode\PhpTimezone\Constants\CountryAll;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ImportProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-05)
 * @since 0.1.0 (2024-06-05) First version.
 */
final class ImportMissingProvider extends BaseProviderCustom
{
    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     * @param ImportRepository $importRepository
     * @param LocationRepository $locationRepository
     * @param TranslatorInterface $translator
     * @param LocationService $locationService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request,
        protected ImportRepository $importRepository,
        protected LocationRepository $locationRepository,
        protected TranslatorInterface $translator,
        protected LocationService $locationService,
        protected EntityManagerInterface $entityManager
    )
    {
        parent::__construct($version, $parameterBag, $request, $locationService, $translator, $entityManager);
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
     * Returns a collection of import resources that have not been imported.
     *
     * @return BasePublicResource[]
     * @throws ClassInvalidException
     */
    private function doProvideGetCollectionMissing(): array
    {
        $countries = CountryAll::COUNTRY_NAMES;

        $importEntities = $this->importRepository->findBy([], ['path' => 'ASC']);

        $missingImports = [];

        foreach ($importEntities as $importEntity) {
            if (!$importEntity instanceof ImportEntity) {
                continue;
            }

            $country = $importEntity->getCountry();

            if (!$country instanceof Country) {
                throw new ClassInvalidException(Country::class, Country::class);
            }

            $countryCode = (string) $country->getCode();

            if (array_key_exists($countryCode, $countries)) {
                unset($countries[$countryCode]);
            }
        }

        foreach ($countries as $countryCode => $countryName) {
            if (!array_key_exists(LocaleCode::EN_GB, $countryName)) {
                continue;
            }

            $import = (new ImportMissing())
                ->setCountry((string) $countryName[LocaleCode::EN_GB])
                ->setCountryCode($countryCode)
            ;

            $missingImports[$countryCode] = $import;
        }

        return array_values($missingImports);
    }

    /**
     * Do the provided job.
     *
     * @return BasePublicResource|BasePublicResource[]
     * @throws CaseUnsupportedException
     * @throws ClassInvalidException
     */
    protected function doProvide(): BasePublicResource|array
    {
        return match(true) {
            $this->getRequestMethod() === BaseResourceWrapperProvider::METHOD_GET_COLLECTION => $this->doProvideGetCollectionMissing(),
            default => throw new CaseUnsupportedException('Unsupported mode from api endpoint /api/v1/import.'),
        };
    }
}
