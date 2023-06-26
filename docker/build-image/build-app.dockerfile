# ===========================================
# Docker file for building a ready to use docker image.
#
# @author Björn Hempel <bjoern@hempel.li>
# @version 0.1.0 (2023-06-24)
# @since 0.1.0 (2023-06-24) First version
# ===========================================

# ===========================================
# Build image of app:
# -------------------
# Build helper images
# ❯ docker compose build
#
# Build the ready to use image with version x.x.x
# ❯ docker compose -f docker-compose.build-image.yml build
#
# Check the built image
# ❯ docker image ls | grep "php-api-version/app"
#
# Check the image content
# ❯ docker run -it --rm -u www-data:users ixnode/php-api-version/app:$(cat VERSION) bash
#
# Check version
# ❯ docker run -it --rm -u www-data:users ixnode/php-api-version/app:$(cat VERSION) bin/console version:show
#
#  Name:            ixnode/php-api-version-bundle
#  Description:     Provides the base API plattform functionality
#  Version:         0.1.7
#  Date:            Saturday, June 24, 2023 - 16:38:23
#  License:         Copyright (c) 2022 Björn Hempel
#  Authors:         Björn Hempel <bjoern@hempel.li>
#  PHP Version:     8.2.7
#  Symfony Version: 6.3.0
#
# ===========================================

# Use some arguments (docker-compose.build-image.yml:services.app.build.args)
ARG BUILD_IMAGE

# Use existing empty php image and fill it with the required files.
FROM $BUILD_IMAGE

# Copy all data from context to image (except for the settings of .dockerignore)
COPY --chown=www-data:users . /var/www/web/

# Run composer install to install the required packages (include them into the docker image)
RUN composer install
