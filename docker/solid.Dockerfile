FROM php:8.3-apache
LABEL maintainer="yvo@muze.nl"
RUN apt-get update
RUN apt-get install -y ssl-cert git unzip
RUN docker-php-ext-install bcmath
RUN a2enmod rewrite allowmethods ssl

COPY . /opt/solid
COPY ./docker/solid.conf /etc/apache2/sites-enabled/000-default.conf

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN composer install --working-dir=/opt/solid --prefer-dist

EXPOSE 443
