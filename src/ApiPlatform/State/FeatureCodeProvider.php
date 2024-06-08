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
use App\ApiPlatform\Resource\FeatureCode;
use App\ApiPlatform\Route\FeatureCodeRoute;
use App\ApiPlatform\State\Base\BaseProviderCustom;
use App\Constants\DB\FeatureClass as FeatureClassDb;
use App\Constants\DB\FeatureCode as FeatureCodeDb;
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class FeatureCodeProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-07)
 * @since 0.1.0 (2024-06-07) First version.
 */
final class FeatureCodeProvider extends BaseProviderCustom
{
    /**
     * @param Version $version
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $request
     * @param TranslatorInterface $translator
     * @param LocationService $locationService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        protected Version $version,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $request,
        protected LocationService $locationService,
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $entityManager
    )
    {
        parent::__construct($version, $parameterBag, $request, $locationService, $translator, $entityManager);
    }

    /**
     * Returns the route properties according to current class.
     *
     * @return array<string, array<string, string|null>>
     */
    protected function getRouteProperties(): array
    {
        return FeatureCodeRoute::PROPERTIES;
    }

    /**
     * @param string|null $filterClass
     * @return FeatureCode[]
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    private function doProvideGetCollection(string|null $filterClass = null): array
    {
        $locale = $this->getLocaleByFilter();

        $language = $this->getLanguageByLocale($locale);

        $featureClasses = [];

        foreach (FeatureCodeDb::ALL_GROUPED as $class => $codes) {

            /* Filter by class if given. */
            if (!is_null($filterClass) && $filterClass !== $class) {
                continue;
            }

            $className = (new FeatureClassDb($this->translator, $language))->translate($class);

            foreach ($codes as $code) {
                $codeName = (new FeatureCodeDb($this->translator, $language))->translate($code);

                $featureClasses[] = (new FeatureCode())
                    ->setCode($code)
                    ->setCodeName($codeName)
                    ->setClass($class)
                    ->setClassName($className)
                ;
            }
        }

        return $featureClasses;
    }

    /**
     * Do the provided job.
     *
     * @return FeatureCode[]
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function doProvide(): array
    {
        $filterClass = match (true) {
            $this->hasFilter(Name::CLASS_) => $this->getFilter(Name::CLASS_),
            default => null,
        };

        if (!is_string($filterClass) && !is_null($filterClass)) {
            $this->setError('Given class filter is not a string.');
            return [];
        }

        return $this->doProvideGetCollection($filterClass);
    }
}
