FROM php:8.3-apache

# Composer oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git unzip libzip-dev zlib1g-dev libldap2-dev; \
    docker-php-ext-configure zip; \
    docker-php-ext-install -j"$(nproc)" pdo pdo_mysql zip; \
    docker-php-ext-configure ldap --with-ldap=/usr; \
    docker-php-ext-install -j"$(nproc)" ldap; \
    a2enmod rewrite; \
    rm -rf /var/lib/apt/lists/*

# DocumentRoot -> /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && echo '<Directory /var/www/html>\n    AllowOverride All\n</Directory>' > /etc/apache2/conf-available/override.conf \
    && a2enconf override

WORKDIR /var/www/html
