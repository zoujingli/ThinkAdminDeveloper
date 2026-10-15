ARG PHP_SERIES=8.2
FROM public.ecr.aws/docker/library/php:${PHP_SERIES}-fpm

ARG PHP_SERIES
ARG COMPOSER_VERSION=2.8.12
ARG PHPUNIT_VERSION=11.5.50

# PHP 7.2 的 Debian 已归档；该镜像仅用于兼容验收，不能作为生产镜像。
RUN if [ "$PHP_SERIES" = "7.2" ]; then \
        sed -i -e 's/deb.debian.org/archive.debian.org/g' \
            -e 's/security.debian.org/archive.debian.org/g' \
            -e '/buster-updates/d' /etc/apt/sources.list; \
    fi \
    && apt-get -o Acquire::Check-Valid-Until=false update \
    && apt-get install -y --no-install-recommends git unzip zip procps util-linux nginx libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && if [ "$PHP_SERIES" = "7.2" ]; then \
        docker-php-ext-configure gd --with-freetype-dir=/usr --with-jpeg-dir=/usr --with-png-dir=/usr; \
    else \
        docker-php-ext-configure gd --with-freetype --with-jpeg; \
    fi \
    && docker-php-ext-install -j4 gd bcmath pdo_mysql pcntl sockets

WORKDIR /tmp
RUN curl --fail --location --retry 3 --max-time 180 "https://getcomposer.org/download/${COMPOSER_VERSION}/composer.phar" -o composer.phar \
    && curl --fail --location --retry 3 --max-time 180 "https://getcomposer.org/download/${COMPOSER_VERSION}/composer.phar.sha256sum" -o composer.sha256 \
    && sha256sum -c composer.sha256 \
    && mv composer.phar /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer \
    && curl --fail --location --retry 3 --max-time 180 "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar" -o /opt/phpunit.phar \
    && php /opt/phpunit.phar --version

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PHPUNIT_BIN=/opt/phpunit.phar \
    THINKADMIN_TEST_DB=:memory:
WORKDIR /work/project
