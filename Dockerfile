# Imagen de PHP con Apache
FROM php:8.2-apache

# Herramientas necesarias para instalar la extensión de MongoDB y Composer
RUN apt-get update && apt-get install -y git unzip libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensión de MongoDB para PHP
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Composer (gestor de paquetes de PHP)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instalo la librería de MongoDB en /var/www (fuera de la carpeta de la web)
WORKDIR /var/www
RUN composer require mongodb/mongodb --no-interaction

# La web va en /var/www/html
WORKDIR /var/www/html
COPY . /var/www/html/

# Apache necesita poder escribir en uploads (fotos y productos subidos)
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads
