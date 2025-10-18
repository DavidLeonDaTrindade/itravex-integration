# Apache + PHP (cómodo para Laravel)
FROM php:8.3-apache

# Extensiones necesarias
RUN apt-get update && apt-get install -y \
    libzip-dev libpng-dev libonig-dev git unzip \
 && docker-php-ext-install pdo_mysql mysqli mbstring zip \
 && docker-php-ext-enable pdo_mysql mysqli

# Habilitar mod_rewrite y apuntar DocumentRoot a /public
RUN a2enmod rewrite
ARG APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
