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

namespace App\Controller;

use App\Constants\DB\FeatureCode;
use App\Constants\Language\LanguageCode;
use App\Controller\Base\BaseCoordinateController;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CoordinateController
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 */
class CoordinateController extends BaseCoordinateController
{
    /**
     * The controller to show the / root page.
     *
     * @return Response
     */
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_coordinate_overview');
    }

    /**
     * The controller to show an overview of coordinate examples.
     *
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    #[Route('/coordinate', name: 'app_coordinate_overview')]
    public function showOverview(): Response
    {
        return $this->render('coordinates/show.html.twig', [
            'coordinates' => $this->getCoordinates(),
        ]);
    }

    /**
     * The controller to show an overview of feature codes from given coordinate.
     *
     * @param string $latitude
     * @param string $longitude
     * @param string $name
     * @param string $isoLanguage
     * @param string $country
     * @return Response
     */
    #[Route('/coordinate/detail/feature-codes/{latitude}/{longitude}/{name}/{isoLanguage}/{country}', name: 'app_coordinate_detail_feature_codes')]
    public function showNextPlacesFeatureCodes(string $latitude, string $longitude, string $name, string $isoLanguage, string $country): Response
    {
        $featureCodeService = new FeatureCode($this->translator);
        $featureCodeService->setLocaleByLanguageAndCountry($isoLanguage, $country);

        return $this->render('coordinates/detail.html.twig', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'language' => $isoLanguage === LanguageCode::DE ? 'German' : 'English',
            'name' => $name,
            'features' => $featureCodeService->getAll(),
        ]);
    }
}
