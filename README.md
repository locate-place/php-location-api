# PHP Location API

[![Release](https://img.shields.io/github/v/release/twelvepics-com/php-location-api)](https://github.com/twelvepics-com/php-location-api/releases)
[![](https://img.shields.io/github/release-date/twelvepics-com/php-location-api)](https://github.com/twelvepics-com/php-location-api/releases)
![](https://img.shields.io/github/repo-size/twelvepics-com/php-location-api.svg)
[![PHP](https://img.shields.io/badge/PHP-^8.2-777bb3.svg?logo=php&logoColor=white&labelColor=555555&style=flat)](https://www.php.net/supported-versions.php)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-777bb3.svg?style=flat)](https://phpstan.org/user-guide/rule-levels)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-Unit%20Tests-6b9bd2.svg?style=flat)](https://phpunit.de)
[![PHPCS](https://img.shields.io/badge/PHPCS-PSR12-416d4e.svg?style=flat)](https://www.php-fig.org/psr/psr-12/)
[![PHPMD](https://img.shields.io/badge/PHPMD-ALL-364a83.svg?style=flat)](https://github.com/phpmd/phpmd)
[![Rector - Instant Upgrades and Automated Refactoring](https://img.shields.io/badge/Rector-PHP%208.2-73a165.svg?style=flat)](https://github.com/rectorphp/rector)
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

```bash
bin/console doctrine:migrations:migrate  --no-interaction
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

Takes about 60 seconds for 10000 lines. The country DE for
example with 200000 needs about half an hour to import.
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