FROM composer:2.10 AS composer

FROM php:8.5-fpm

ARG UID=1000
ARG GID=1000

# System libraries required by PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl default-mysql-client \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev \
        libicu-dev libxml2-dev libxslt1-dev libzip-dev libffi-dev \
        libmagickwand-dev libmcrypt-dev libyaml-dev libssl-dev \
        libcurl4-openssl-dev libbrotli-dev libsodium-dev \
    && rm -rf /var/lib/apt/lists/*

# Core extensions (matching local PHP 8.5 module list)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure ffi --with-ffi \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath calendar exif ffi ftp gd gettext intl mysqli pdo_mysql \
        pcntl shmop soap sockets sysvmsg sysvsem sysvshm xsl zip

# opcache is built in since PHP 8.5; install only if it is still a shared ext
RUN php -m | grep -qi 'opcache' || docker-php-ext-install opcache

# PECL extensions
RUN pecl install igbinary \
    && docker-php-ext-enable igbinary \
    && pecl install -D 'enable-redis-igbinary="yes"' redis \
    && pecl install imagick mcrypt yaml xdebug \
    && pecl install -D 'enable-sockets="yes" enable-openssl="yes" enable-mysqlnd="yes" enable-swoole-curl="yes"' swoole \
    && docker-php-ext-enable redis imagick mcrypt yaml swoole \
    && rm -rf /tmp/pear

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/zz-magento.ini
COPY xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini
COPY www.conf /usr/local/etc/php-fpm.d/zz-www.conf

# Run as host user to avoid file permission issues on bind mounts
RUN groupmod -o -g ${GID} www-data && usermod -o -u ${UID} www-data \
    && mkdir -p /var/www/.composer && chown -R www-data:www-data /var/www

WORKDIR /var/www/html
USER www-data
