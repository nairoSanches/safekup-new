FROM php:8.2-apache

# Dependências e extensões PHP necessárias para Laravel + MySQL
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        libldap2-dev \
        unzip \
        git \
        composer \
    && docker-php-ext-install pdo pdo_mysql zip \
    && docker-php-ext-configure ldap \
    && docker-php-ext-install ldap \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Ajusta DocumentRoot para /public e ativa AllowOverride
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && echo '<Directory /var/www/html>\n    AllowOverride All\n</Directory>' > /etc/apache2/conf-available/override.conf \
    && a2enconf override

WORKDIR /var/www/html
