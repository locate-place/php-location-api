# PHP Location API

[![Release](https://img.shields.io/github/v/release/locate-place/php-location-api)](https://github.com/locate-place/php-location-api/releases)
[![Release Data](https://img.shields.io/github/release-date/locate-place/php-location-api)](https://github.com/locate-place/php-location-api/releases)
[![Repository Size](https://img.shields.io/github/repo-size/locate-place/php-location-api.svg)](https://github.com/locate-place/php-location-api)
[![Symfony](https://img.shields.io/badge/Symfony-^7.1-1f2937.svg?logo=symfony&logoColor=white&labelColor=555555&style=flat)](https://symfony.com/doc/current/setup.html)
[![Doctrine](https://img.shields.io/badge/Doctrine-^3.2-fa6931.svg?labelColor=555555&style=flat)](https://www.doctrine-project.org/projects/doctrine-orm/en/3.2/index.html)
[![API Platform](https://img.shields.io/badge/API%20Platform-^3.3-0099a1.svg?labelColor=555555&style=flat)](https://api-platform.com/docs/distribution/)
[![PHP](https://img.shields.io/badge/PHP-^8.2-777bb3.svg?logo=php&logoColor=white&labelColor=555555&style=flat)](https://www.php.net/supported-versions.php)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-777bb3.svg?style=flat)](https://phpstan.org/user-guide/rule-levels)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-Unit%20Tests-6b9bd2.svg?style=flat)](https://phpunit.de)
[![PHPCS](https://img.shields.io/badge/PHPCS-PSR12-416d4e.svg?style=flat)](https://www.php-fig.org/psr/psr-12/)
[![PHPMD](https://img.shields.io/badge/PHPMD-ALL-364a83.svg?style=flat)](https://github.com/phpmd/phpmd)
[![Rector - Instant Upgrades and Automated Refactoring](https://img.shields.io/badge/Rector-^0.17-73a165.svg?style=flat)](https://github.com/rectorphp/rector)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-14.11-336791.svg?style=flat&logo=postgresql&logoColor=white)](https://www.postgresql.org/docs/14/index.html)
[![PostGIS](https://img.shields.io/badge/PostGIS-3.4-112b3c.svg?style=flat)](https://postgis.net/documentation/getting_started/)
[![LICENSE](https://img.shields.io/github/license/ixnode/php-api-version-bundle)](https://github.com/ixnode/php-api-version-bundle/blob/master/LICENSE)

> Welcome to the php-location-api repository! This project provides the backend API for the
> locate.place application, delivering comprehensive location information. From population
> statistics and mountain data to airport details and postal codes, this API is the
> backbone of our location-based services.

## Demo

Check out the live demo and documentation here: [loc8.place](https://loc8.place/)

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

```bash
bin/console doctrine:migrations:migrate  --no-interaction
```

Open the project in your browser:

* https://www.location-api.localhost/
* https://www.location-api.localhost/api/v1
* https://www.location-api.localhost/api/v1/version.json

> Hint: If you want to use real urls instead of using port numbers,
> try to use https://github.com/bjoern-hempel/local-traefik-proxy

## Get location information

### Via command line

```bash
bin/console location:coordinate "51.0504, 13.7373" -i es
```

### Via API

```bash
curl -ks https://www.location-api.localhost/api/v1/location/coordinate\?coordinate\=51.0504%2C%2013.7373\&language\=es | jq .
```

### Result

Both will result in:

```json
{
  "data": {
    "geoname-id": 2935022,
    "name": "Dresden",
    "feature": {
      "class": "P",
      "class-name": "Städte, Dörfer, etc.",
      "code": "PPLA",
      "code-name": "Sitz einer Verwaltungseinheit erster Ordnung"
    },
    "coordinate": {
      "latitude": 51.05089,
      "longitude": 13.73832,
      "srid": 4326,
      "distance": {
        "meters": 89.7,
        "kilometers": 0.09
      },
      "direction": {
        "degree": 64.34,
        "direction": "NE"
      }
    },
    "timezone": {
      "timezone": "Europe/Berlin",
      "country": "DE",
      "current-time": "2023-08-27 15:23:55",
      "offset": "+02:00",
      "latitude": 52.5,
      "longitude": 13.36666
    },
    "location": {
      "district-locality": "Innere Altstadt",
      "city-municipality": "Dresde",
      "state": "Sachsen",
      "country": "Alemania"
    },
    "link": {
      "google": "https://www.google.de/maps/place/51.050400+13.737300",
      "openstreetmap": "https://www.openstreetmap.org/?lat=51.050400&lon=13.737300&mlat=51.050400&mlon=13.737300&zoom=14&layers=M"
    }
  },
  "given": {
    "geoname-id": 0,
    "coordinate": {
      "raw": "51.0504, 13.7373",
      "parsed": {
        "latitude": "51.0504",
        "longitude": "13.7373",
        "latitudeDms": "51°3′1.44″N",
        "longitudeDms": "13°44′14.28″E"
      }
    },
    "language": {
      "raw": "es",
      "parsed": {
        "name": "Spanish, Castilian"
      }
    }
  },
  "valid": true,
  "date": "2023-08-27T13:23:55+00:00",
  "timeTaken": "65ms",
  "version": "0.1.20",
  "data-licence": {
    "full": "Creative Commons Attribution 4.0 License",
    "short": "CC-BY 4.0",
    "source": "https://download.geonames.org/export/dump/readme.txt"
  }
}
```

### Adminer

If you want to use the adminer to manage your db data.

* https://adminer.location-api.localhost/

## Check your configuration

```bash
php -v
```

```bash
PHP 8.2.7 (cli) (built: Jun  9 2023 07:38:59) (NTS)
Copyright (c) The PHP Group
Zend Engine v4.2.7, Copyright (c) Zend Technologies
    with Zend OPcache v8.2.7, Copyright (c), by Zend Technologies
    with Xdebug v3.2.1, Copyright (c) 2002-2023, by Derick Rethans
```

## Create structure

```bash
bin/console doctrine:migrations:migrate  --no-interaction
```

## Import data

The data comes from http://download.geonames.org/export/dump/
and are placed under `import/location`.

### Download command:

```bash
bin/console location:download DE
```

### Check command:

Check the structure of downloaded file.

```bash
bin/console location:check import/location/DE.txt
```

### Import command:

If no error appears with the check command:

```bash
bin/console location:import import/location/DE.txt
```

Takes about 20 seconds for 10000 lines. The country DE for
example with 200000 needs about seven minutes to import.
Depending on the performance of the system used and the
amounts of data in the table.

## Test command

* PHPCS - PHP Coding Standards Fixer
* PHPMND - PHP Magic Number Detector
* PHPStan - PHP Static Analysis Tool
* PHPUnit - The PHP Testing Framework
* Rector - Instant Upgrades and Automated Refactoring

Execute them all:

```bash
composer test:hardcore
```