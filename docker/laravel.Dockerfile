# laravel.Dockerfile
FROM php:8.3-apache

# 1) Traga o Composer oficial (sem apt)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 2) Dependências de sistema para extensões
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git unzip \
        libzip-dev zlib1g-dev \
        libldap2-dev \
    ; \
    # zip + PDO MySQL
    docker-php-ext-configure zip; \
    docker-php-ext-install -j"$(nproc)" pdo pdo_mysql zip; \
    # LDAP (se der erro de libdir, ver nota abaixo)
    docker-php-ext-configure ldap; \
    docker-php-ext-install -j"$(nproc)" ldap; \
    # Apache
    a2enmod rewrite; \
    # limpeza
    rm -rf /var/lib/apt/lists/*

# Ajusta DocumentRoot para /public e ativa AllowOverride
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && echo '<Directory /var/www/html>\n    AllowOverride All\n</Directory>' > /etc/apache2/conf-available/override.conf \
    && a2enconf override

WORKDIR /var/www/html
