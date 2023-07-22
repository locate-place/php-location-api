# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Releases

### [0.1.7] - 2023-07-22

* Add import table and log each import (each country)

### [0.1.6] - 2023-07-04

* Add new fresh packages
* Add distance, feature class and coordinate to endpoint
* Extend given parameter

### [0.1.5] - 2023-07-02

* Add DQL Functions for ST_DWithin and ST_MakePoint

### [0.1.4] - 2023-07-02

* Optimization interaction with the package ixnode/php-api-version-bundle
* Refactoring

### [0.1.3] - 2023-07-01

* Add first /location endpoint

### [0.1.2] - 2023-07-01

* Add PostGis to PostgreSQL
* Add location file checker
* Add location downloader
* Improve location importer
* Refactoring
* Update README.md documentation
* Add new migration structure

### [0.1.1] - 2023-06-29

* Add first importer
* README.md documentation
* Add migration structure

### [0.1.0] - 2023-06-27

* Initial release
* Add src
* Add tests
  * PHP Coding Standards Fixer
  * PHPMND - PHP Magic Number Detector
  * PHPStan - PHP Static Analysis Tool
  * PHPUnit - The PHP Testing Framework
  * Rector - Instant Upgrades and Automated Refactoring
* Add README.md
* Add LICENSE.md
* Docker environment
* Composer requirements
* Add additional packages

## Add new version

```bash
# Checkout master branch
$ git checkout main && git pull

# Check current version
$ vendor/bin/version-manager --current

# Increase patch version
$ vendor/bin/version-manager --patch

# Change changelog
$ vi CHANGELOG.md

# Push new version
$ git add CHANGELOG.md VERSION && git commit -m "Add version $(cat VERSION)" && git push

# Tag and push new version
$ git tag -a "$(cat VERSION)" -m "Version $(cat VERSION)" && git push origin "$(cat VERSION)"
```
