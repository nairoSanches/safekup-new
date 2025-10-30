FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libldap2-dev \
 && rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    docker-php-ext-configure ldap --with-ldap=/usr; \
    docker-php-ext-install -j"$(nproc)" ldap

RUN a2enmod rewrite
