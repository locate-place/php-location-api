# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Releases

### [1.0.15] - 2024-06-07

* Add endpoint /api/v1/feature-class

### [1.0.14] - 2024-06-07

* Fix geoname id search

### [1.0.13] - 2024-06-06

* Update OpenAPI Specification

### [1.0.12] - 2024-06-01

* Add Api Platform descriptions
* Add AutocompleteLocation type to autocomplete endpoint
* Add AutocompleteFeature type to autocomplete endpoint

### [1.0.11] - 2024-06-01

* Add link to locate.place app

### [1.0.10] - 2024-06-01

* Add Swagger UI and Bootstrap to overview page

### [1.0.9] - 2024-06-01

* Add demo to README.md and all version badges

### [1.0.8] - 2024-06-01

* Add relative url to redoc

### [1.0.7] - 2024-05-31

* Add first country district configuration
* Add new adm detection and optimizations
* Update symfony to 7.1
* Add redoc to /

### [1.0.6] - 2024-05-26

* Fix english and spanish full-text search

### [1.0.5] - 2024-05-25

* Change url from location.twelvepics.com to loc8.place

### [1.0.4] - 2024-05-25

* Remove stop words from fulltext search

### [1.0.3] - 2024-05-25

* Add deploy script

### [1.0.2] - 2024-05-25

* Add translations for saddle and gap

### [1.0.1] - 2024-05-25

* Improved full-text search

### [1.0.0] - 2024-05-24

* Unlocking all countries
* Improved import
* Add a new sort order for geoname ID queries
* Adding a search_index index for faster queries

### [0.1.87] - 2024-05-01

* Combine feature search types

### [0.1.86] - 2024-05-01

* Allow empty "feature-classes:"

### [0.1.85] - 2024-05-01

* Fix name order

### [0.1.84] - 2024-04-29

* Fix search
* Add alternate names to api response

### [0.1.83] - 2024-04-25

* Add order by to sub query

### [0.1.82] - 2024-04-24

* Optimize search
* Synchronize count and search

### [0.1.81] - 2024-04-22

* Improved location search

### [0.1.80] - 2024-04-22

* Filter by name via database query (autocomplete)
  * Show more results via autocomplete

### [0.1.79] - 2024-04-20

* Separate country locale from country search

### [0.1.78] - 2024-04-20

* Do not use default distance on name search endpoint

### [0.1.77] - 2024-04-20

* Allow all available country codes within query parser

### [0.1.76] - 2024-04-20

* Set default limit to 10 (search endpoint)

### [0.1.75] - 2024-04-20

* Add distance and limit to "given" section (search endpoint)

### [0.1.74] - 2024-04-20

* Add distance and limit to parsed query params (search endpoint)

### [0.1.73] - 2024-04-20

* Add query parser distance and limit to search query
* Add feature-classes and feature-codes to coordinate search with features

### [0.1.72] - 2024-04-18

* Add country filter

### [0.1.71] - 2024-04-17

* Add extended query parser

### [0.1.70] - 2024-04-16

* Remove A locations from the search
* Pager refactoring
* Parameter query refactoring

### [0.1.69] - 2024-04-13

* Ignore invalid feature codes

### [0.1.68] - 2024-04-12

* Remove non-evaluable SQL characters
* Add feature code and classes to autocomplete searches

### [0.1.67] - 2024-04-08

* Autocomplete endpoint: Separate country from name
* Fix feature class/code detection

### [0.1.66] - 2024-04-08

* Add extended query parser
  * single feature codes
  * feature codes with search term
  * feature codes with geoname id
  * etc.

### [0.1.65] - 2024-04-08

* Search refactoring; Search with relevance; Distance from location or river (closest point to river)
* Add SearchIndex entity; Add TsVector object and PostgreSQL TsVector DBAL type
* Add created_at and updated_at to table search_index
* Add autocomplete endpoint with location, feature class and feature code search

### [0.1.64] - 2024-04-03

* Add river length to search relevance
* Fix river:show command

### [0.1.63] - 2024-04-01

* Add more examples
* Use the country of given search to determine the administrative places

### [0.1.62] - 2024-04-01

* Add new examples to example endpoint
* Fix name search (case-insensitive)

### [0.1.61] - 2024-04-01

* Adds the FeatureContainer
* Enables correct river feature search
* Change next place search for rivers and lakes to 20000 meters

### [0.1.60] - 2024-04-01

* Command: river:show -s river -> how all rivers (also unmapped)

### [0.1.59] - 2024-03-31

* Add rivers to database and API endpoint
  * Add river length to properties
  * Adds a river table
  * etc.
* Add location:show command
* Add river:show command
* Add DebugQuery class
* Add river import commands
  * Add river:mapping command
  * Adds a river transfer command
* Add string_agg dbal function
* Add PostgreSQL optimizations
* Adds MigrationEventSubscriber to ignore creating the public and topology schema
* Improve make:migration command
  * Add topology. to doctrine schema_filter
* Doctrine dbal refactoring
* Add River entity and import scripts
* Change API CORS

### [0.1.58] - 2024-03-16

* Add GeographyPolygonType and GeometryPolygonType
* Add PostgreSQL ST_Intersects function
* Add ZipCodeAreaRepository::findZipCodeByCoordinate and more

### [0.1.57] - 2024-03-16

* Add zip code to location endpoint
* Add zip code import command zip-code:import; ZipCode entity and repository
* Add zip code download command zip-code:download
* Import refactoring

### [0.1.56] - 2024-03-10

* Decrease/adopt airport relevance on search

### [0.1.55] - 2024-03-10

* Fix airport parser (wrong cargo number)

### [0.1.54] - 2024-03-09

* Add airport passengers to relevance

### [0.1.53] - 2024-03-09

* Split search by space and add AND search

### [0.1.52] - 2024-03-08

* Introduced new Wikipedia parser for crawling airport properties, enhancing iata and icao detection, and adding a debug mode for detailed operation analysis. 
* Improved the crawling process with the inclusion of IgnoreBuilder for better management of constants and ignore rules, alongside force parameter implementation for more controlled crawling. 
* Expanded data accuracy and coverage by requesting disambiguation pages, adding ignored airports, and incorporating more IATA codes to enhance property detection. 
* Enhanced data structure by introducing property_type and source_id fields, extending VARCHAR limits for better data handling, and adding a source to track property origins. 
* Refined airport data extraction by identifying key metrics like 'Passengers' and 'Passenger volume', adding endpoints for airport data retrieval, and refining the crawler's functionality. 
* General refactoring and optimizations including command addition for improved usability, airport wikipedia crawler example for guidance, and field type adjustments for data consistency.

### [0.1.51] - 2024-02-23

* Fix other links

### [0.1.50] - 2024-02-23

* Add location/countries endpoint

### [0.1.49] - 2024-02-23

* Add more bad timezones

### [0.1.48] - 2024-02-22

* Add other links to location

### [0.1.47] - 2024-02-22

* Search for next place or admin place if another place than A or P was given

### [0.1.46] - 2024-02-22

* Add wikipedia parser
  * Adds english, german and spanish pages
* Adds type, source and changed to alternate_name table
* Adds source to location table
* Adds new property table for other (new) location properties

### [0.1.45] - 2024-02-18

* Activation of the Spanish language
* First Spanish translations

### [0.1.44] - 2024-02-18

* Fix valid state if error occurred

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
