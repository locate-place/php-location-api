<?php

/*
 * This file is part of the ixnode/php-api-version-bundle project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

require_once('plugins/login-servers.php');

return new AdminerLoginServers([
    'PostgreSQL (development)' => [
        'server' => 'com-twelvepics-php-location-api-development-postgresql',
        'driver' => 'pgsql',
    ],
]);