# Add needed arguments
ARG IMAGE_ADD

# Use debian:bullseye-slim image
FROM ${IMAGE_ADD}debian:bullseye-slim

# Set environment variables (Apache)
ENV APACHE_HTTPS_PORT 443
ENV APACHE_HTTP_PORT 80
ENV APACHE_DIRECTORY_CONFIGURATION /etc/apache2
ENV APACHE_DIRECTORY_NAME_CERTIFICATES cert
ENV APACHE_PATH_SITES_AVAILABLE sites-available/site.conf
ENV APACHE_PATH_PRIVATE_KEY cert/server.key
ENV APACHE_PATH_PUBLIC_KEY cert/server.crt
ENV APACHE_PATH_DHPARAM cert/dhparam.pem
ENV APACHE_PATH_LOCAL_VHOSTS conf.d/site.conf

# Set environment variables (PHP)
ENV PHP_VERSION 8.2.1
ENV PHP_VERSION_MINOR 8.2
ENV PHP_RUN_DIRECTORY /run/php
ENV PHP_FPM_PORT 9000
ENV PHP_GD_VERSION 2.3.0

# Set environment variables (Composer)
ENV COMPOSER_VERSION 2.4.4
ENV COMPOSER_ALLOW_SUPERUSER 1

# Set environment variables (Node)
ENV NODE_VERSION_MAYOR 16

# Set environment variables (General)
ENV WORK_DIRECTORY /var/www/web

# Set working dir
WORKDIR $WORK_DIRECTORY



##### Application Configuration #####

# Install applications
RUN    apt-get update \
	&& apt-get -y install \
        apache2 \
        apt-transport-https \
        ca-certificates \
        cron \
        curl \
        default-mysql-client \
        git  \
        imagemagick \
        lsb-release \
        rsync \
        supervisor \
        unzip \
        wget \
        zip \
	&& wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg \
	&& (echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list) \
    && (echo 'deb [trusted=yes] https://repo.symfony.com/apt/ /' > /etc/apt/sources.list.d/symfony-cli.list) \
    && (curl -sL https://deb.nodesource.com/setup_${NODE_VERSION_MAYOR}.x | bash -) \
	&& apt-get update \
    && apt-get upgrade -y \
	&& apt-get -y install \
        libavif-dev \
        nodejs \
        readline-common \
        symfony-cli \
        zlib1g-dev \
        php${PHP_VERSION_MINOR}-bcmath \
        php${PHP_VERSION_MINOR}-cli \
        php${PHP_VERSION_MINOR}-curl \
        php${PHP_VERSION_MINOR}-fpm \
        php${PHP_VERSION_MINOR}-gd \
        php${PHP_VERSION_MINOR}-imagick \
        php${PHP_VERSION_MINOR}-intl \
        php${PHP_VERSION_MINOR}-mbstring \
        php${PHP_VERSION_MINOR}-mysql \
        php${PHP_VERSION_MINOR}-opcache \
        php${PHP_VERSION_MINOR}-pgsql \
        php${PHP_VERSION_MINOR}-soap  \
        php${PHP_VERSION_MINOR}-sqlite3  \
        #php${PHP_VERSION_MINOR}-xdebug \
        php${PHP_VERSION_MINOR}-xml \
        php${PHP_VERSION_MINOR}-zip \
    && (curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=${COMPOSER_VERSION}) \
    && npm install -g npm \
    && npm install --global yarn \
    && mkdir -p /run/php \
    && sed -ri "s/^listen = .+$/listen = $PHP_FPM_PORT/" /etc/php/${PHP_VERSION_MINOR}/fpm/pool.d/www.conf \
    && sed -ri "s/^;clear_env = no$/clear_env = no/" /etc/php/${PHP_VERSION_MINOR}/fpm/pool.d/www.conf \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*



##### Certification Configuration #####

# Create certificate folder
RUN mkdir -p ${APACHE_DIRECTORY_CONFIGURATION}/${APACHE_DIRECTORY_NAME_CERTIFICATES}

# Create self signed certificate
RUN openssl req -x509 -nodes -days 3650 -newkey rsa:2048 -keyout ${APACHE_DIRECTORY_CONFIGURATION}/${APACHE_PATH_PRIVATE_KEY} -out ${APACHE_DIRECTORY_CONFIGURATION}/${APACHE_PATH_PUBLIC_KEY} -subj "/C=DE/ST=Saxony/L=Dresden/O=Ixnode/OU=IT/CN=localhost"

# Create dhparam.pem
RUN openssl dhparam -out ${APACHE_DIRECTORY_CONFIGURATION}/${APACHE_PATH_DHPARAM} 4096



##### Default Configuration #####

# Create user
RUN useradd -ms /bin/bash user

# Change permissions for mounted folders
RUN    mkdir -p ${WORK_DIRECTORY}/var \
    && mkdir -p ${WORK_DIRECTORY}/vendor \
    && chown user:user ${WORK_DIRECTORY}/var \
    && chown user:user ${WORK_DIRECTORY}/vendor



##### PHP Configuration #####

# Switch to production configuration
#RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Add opcache.ini
COPY conf.d/opcache.ini /etc/php/${PHP_VERSION_MINOR}/fpm/conf.d/opcache.ini

# Add config.ini
COPY conf.d/config.ini /etc/php/${PHP_VERSION_MINOR}/fpm/conf.d/config.ini

# Add php-fpm configuration
COPY conf.d/www.conf /etc/php/${PHP_VERSION_MINOR}/fpm/pool.d/www.conf

# Add error log pat
RUN sed -i 's/error_log = .*/error_log = \/proc\/self\/fd\/2/' /etc/php/8.2/fpm/php-fpm.conf

# Connect log files
#RUN ln -sf /dev/stderr /var/log/php${PHP_VERSION_MINOR}-fpm.log



##### Apache Configuration #####

# Add some configs to apache
RUN echo "ServerName localhost" | tee -a /etc/apache2/apache2.conf

# Copy vhost configuration to apache
COPY ${APACHE_PATH_LOCAL_VHOSTS} ${APACHE_DIRECTORY_CONFIGURATION}/${APACHE_PATH_SITES_AVAILABLE}

# Activate apache modules
RUN a2enmod ssl headers rewrite remoteip proxy proxy_http proxy_balancer proxy_fcgi

# Disable apache sites
RUN a2dissite 000-default
RUN a2ensite site

# Connect log files
RUN ln -sf /dev/stdout /var/log/apache2/access.log
RUN ln -sf /dev/stderr /var/log/apache2/error.log



##### Crontab Configuration #####

# Copy chown configuration to cron.d
COPY --chown=root:root --chmod=644 cron.d/chown /etc/cron.d/chown

# Copy symfony configuration to cron.d
COPY --chown=root:root --chmod=644 cron.d/symfony /etc/cron.d/symfony

# Connect log files
RUN ln -sf /dev/stdout /var/log/cron.log



##### Supervisord Configuration #####

# Copy confd. supervisord to /etc/supervisor/conf.d/supervisord.conf
COPY --chown=root:root conf.d/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chown=root:root conf.d/supervisord-main.conf /etc/supervisor/supervisord.conf



##### Port Configuration #####

# Expose PHP FPM 9000 port
EXPOSE $PHP_FPM_PORT

# Also expose port 443 and 80
EXPOSE $APACHE_HTTPS_PORT
EXPOSE $APACHE_HTTP_PORT



##### Start Configuration #####

# Start supervisord and keep container running
CMD ["/usr/bin/supervisord", "-n"]
