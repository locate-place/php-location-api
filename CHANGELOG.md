# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Releases

### [0.1.43] - 2024-02-11

* Switch own coordinate shortcut from c to p

### [0.1.42] - 2024-02-10

* Add hospitals to next places
* Change own position parameter from c to p

### [0.1.41] - 2024-01-24

* Add next places
* Add calendar.twelvepics.com domain to COORS

### [0.1.40] - 2024-01-23

* Add production docker setup
  * Fix docker composer production configuration
  * Add certresolver to docker setup to create letsencrypt certificates
  * Switch to node version 18
* Add result information to example search

### [0.1.39] - 2024-01-23

* Refactoring
* Add page filter to search endpoint
* Add stations to next places; Introduce the page filter; Add results to list endpoints
* Optimize text search
  * Add pg_trgm extension to PostgreSQL
  * Add gin_trgm index to location.name and alternate_name.alternate_name
  * Add ILIKE operator to doctrine

### [0.1.38] - 2024-01-22

* Use the distance and limit configuration from next_places.yaml if no one was given (list search)

### [0.1.37] - 2024-01-21

* No case-sensitive search
* Fix relevance-user sorting
* Adjust next places configuration

### [0.1.36] - 2024-01-20

* Add geoname id list search

### [0.1.35] - 2024-01-20

* Add feature classes to next places

### [0.1.34] - 2024-01-20

* Separate distance sort
  * distance vs. distance-user
  * relevance vs. relevance-user

### [0.1.33] - 2024-01-19

* Add distance and direction for search coordinate and user position

### [0.1.32] - 2024-01-18

* Fix distance calculation within next-places area: Use given coordinate or main location

### [0.1.31] - 2024-01-17

* Fix next places configuration
* Add further feature code translations
* Add geoname id to geoname id query

### [0.1.30] - 2024-01-15

* Fix next places configuration

### [0.1.29] - 2024-01-15

* Add the parsed and translated query array to given array

### [0.1.28] - 2024-01-14

* Add search endpoint; Add relevance sort
* Add alternate_name index to table alternate_name and name index to location.
* Add coordinate with location to given response
* Add Query class to easily get filter (parameter), uri variables and pathes
* Add LocationProvider descriptions
* Add distance sort to examples endpoint

### [0.1.27] - 2024-01-11

* Add example endpoint v2
* Preparation for new search endpoint
* Refactoring

### [0.1.26] - 2024-01-07

* Add schema endpoint
  * /api/v1/location/coordinate.json?schema
  * /api/v1/location.json?schema
  * etc.
* Add country and next_places to endpoint (given parameters)
* Add feature code filter to endpoint /api/v1/location
  * Next airports: /api/v1/location.json?coordinate=51.0504%2C%2013.7373&distance=1000&limit=10&feature_code=AIRP
  * etc.
* Add "Next places" overview
  * Add "Next places groups" to config
* Add /example endpoint
* Add new indexes to boost coordinate queries
* Add query parser and tests
* Add Feature Codes as constants
* Add performance logger
* Replace long country names
  * Bundesrepublik Deutschland > Deutschland
  * Schweizerische Eidgenossenschaft > Schweiz
* Add IATA and ICAO codes to airports

### [0.1.25] - 2023-12-30

* Add population, elevation and dem to api endpoint
* Refactoring

### [0.1.24] - 2023-12-30

* Add wikipedia links

### [0.1.23] - 2023-12-30

* Replace location id with geoname id

### [0.1.22] - 2023-12-30

* Add order by population
* Add id-location to district, city, borough and country (beside name)
* Add de to LocationServiceAlternateName

### [0.1.21] - 2023-08-28

* Add state config
* Add Malta tests and configuration

### [0.1.20] - 2023-08-27

* Add alternate names
* Add alternate names import
* Add iso language parameter to api query and command line

### [0.1.19] - 2023-08-26

* Add New York / Binghamton test
* Add "|" filter to exceptions

### [0.1.18] - 2023-08-26

* Add United States tests
* Add boroughs to United States (New York)

### [0.1.17] - 2023-08-26

* Remove country config to yaml file

### [0.1.16] - 2023-08-16

* Add new location tests
* Configuration refactoring

### [0.1.15] - 2023-08-14

* Add location:test for testing purposes
  * Translates a given word to coordinates
  * Useful for testing purposes
* Add command location:test functional tests
* Add first "final" data structure to location:coordinate (data, given, time-taken, etc.)
* Update API platform from v3.1.12 to v3.1.14
* Add environment and db driver name to version:show command
* Increase alternate name field (location) from 8192 to 16384
* findNextLocationByXXX refactoring
  * More filter and sorting options
* Add country settings (admin fields) for city and district detection
  * Improved detection

### [0.1.14] - 2023-07-31

* Improve import time
* Add location:geoname-id and location:coordinate command

### [0.1.13] - 2023-07-31

* Add new geography coordinate field, indizes and db changes; Improvement of the query speed; Add geography and geometry doctrine dbal types; Add PostgreSQL distance operator <->
* Remove replacement field location.coordinate_geography
* Fix lat/lon order for PostGISType::convertToDatabaseValue

### [0.1.12] - 2023-07-29

* Add district, city, state detection
* Add country translations
* Add Google and OpenStreetMap links
* Add execution time to import
* Add ST_Distance to doctrine
* Add new QueryBuilder location finder

### [0.1.11] - 2023-07-23

* Add distance to endpoint /api/v1/location
* Order result by distance

### [0.1.10] - 2023-07-22

* Composer update
* Upgrading ixnode/php-coordinate (0.1.2 => 0.1.6)
* Upgrading phpstan/phpstan (1.10.25 => 1.10.26)
* Upgrading povils/phpmnd (v3.1.0 => v3.2.0)
* Upgrading rector/rector (0.17.3 => 0.17.6)

### [0.1.9] - 2023-07-22

* Update README.md

### [0.1.8] - 2023-07-22

* Execute location:check before location:import

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
