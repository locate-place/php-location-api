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

namespace App\Controller;

use App\Controller\Base\BaseCoordinateController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DocumentController
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-05-31)
 * @since 0.1.0 (2024-05-31) First version.
 */
class DocumentController extends BaseCoordinateController
{
    /**
     * The controller to show the / root page.
     *
     * @return Response
     */
    #[Route('/', name: 'app_documentation')]
    public function documentation(): Response
    {
        return $this->render('document/redoc.html.twig');
    }

    /**
     * The controller to show the /swagger page.
     *
     * @return Response
     */
    #[Route('/swagger', name: 'app_swagger')]
    public function swagger(): Response
    {
        return $this->render('document/swagger.html.twig');
    }
}
