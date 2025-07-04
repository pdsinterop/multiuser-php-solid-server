FROM php:8.3-apache
LABEL maintainer="yvo@muze.nl"
RUN apt-get update
RUN apt-get install -y ssl-cert
RUN docker-php-ext-install bcmath
RUN a2enmod rewrite allowmethods ssl

COPY . /opt/solid
COPY ./docker/solid.conf /etc/apache2/sites-enabled/000-default.conf
EXPOSE 443
