# PHP Location API

[![Release](https://img.shields.io/github/v/release/twelvepics-com/php-location-api)](https://github.com/twelvepics-com/php-location-api/releases)
[![PHP](https://img.shields.io/badge/PHP-^8.2-777bb3.svg?logo=php&logoColor=white&labelColor=555555&style=flat)](https://www.php.net/supported-versions.php)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-brightgreen.svg?style=flat)](https://phpstan.org/user-guide/rule-levels)
[![PHPCS](https://img.shields.io/badge/PHPCS-PSR12-brightgreen.svg?style=flat)](https://www.php-fig.org/psr/psr-12/)
[![LICENSE](https://img.shields.io/github/license/ixnode/php-api-version-bundle)](https://github.com/ixnode/php-api-version-bundle/blob/master/LICENSE)

> This project provides a location API.

## Installation

```bash
git clone https://github.com/bjoern-hempel/php-location-api.git && cd php-location-api
```

```bash
docker compose up -d
```

```bash
docker compose exec php composer install
```

Open the project in your browser:

* https://www.location-api.localhost/
* https://www.location-api.localhost/api/v1
* https://www.location-api.localhost/api/v1/version.json

> Hint: If you want to use real urls instead of using port numbers,
> try to use https://github.com/bjoern-hempel/local-traefik-proxy

### Adminer

If you want to use the adminer to manage your db data.

* https://adminer.location-api.localhost/

## Import data

Download data from: http://download.geonames.org/export/dump/

Extract the txt file with its data to `import/location/DE.txt`.
As an example for the country DE.

Import command:

```bash
bin/console import:location import/location/DE.txt
```

Takes about 60 seconds for 10000 lines. The country DE for
example with 200000 needs about half an hour to import.
Depending on the performance of the system used and the
amounts of data in the table.
