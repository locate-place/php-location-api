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

use App\ApiPlatform\Resource\FeatureClass;
use App\ApiPlatform\Route\FeatureClassRoute;
use App\ApiPlatform\State\Base\BaseProviderCustom;
use App\Constants\DB\FeatureClass as FeatureClassDb;
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Ixnode\PhpApiVersionBundle\ApiPlatform\Resource\Base\BasePublicResource;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ImportProvider
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-06-07)
 * @since 0.1.0 (2024-06-07) First version.
 */
final class FeatureClassProvider extends BaseProviderCustom
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
     * @return array<string, array<string, int|string|string[]>>
     */
    protected function getRouteProperties(): array
    {
        return FeatureClassRoute::PROPERTIES;
    }

    /**
     * Do the provided job.
     *
     * @return BasePublicResource[]
     * @throws ArrayKeyNotFoundException
     * @throws TypeInvalidException
     */
    protected function doProvide(): array
    {
        $locale = $this->getLocaleByFilter();

        $language = $this->getLanguageByLocale($locale);

        $featureClasses = [];

        foreach (FeatureClassDb::ALL as $code) {
            $featureClasses[] = (new FeatureClass())
                ->setClass($code)
                ->setName((new FeatureClassDb($this->translator, $language))->translate($code))
            ;
        }

        return $featureClasses;
    }
}
