FROM docker.io/shinsenter/php:8.3-fpm-apache
LABEL maintainer="yvo@muze.nl"
RUN apt-get update
RUN apt-get install -y ssl-cert git unzip
COPY --from=docker.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath
RUN a2enmod rewrite allowmethods ssl

COPY . /opt/solid
COPY ./docker/solid.conf /etc/apache2/sites-enabled/00-default.conf
#COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN composer install --working-dir=/opt/solid --prefer-dist

EXPOSE 443
