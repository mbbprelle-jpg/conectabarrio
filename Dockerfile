FROM php:8.3-apache

# 1. Habilitar el módulo rewrite de Apache para soportar las URLs amigables
RUN a2enmod rewrite

# 2. Instalar utilidades, extensiones de BD e imagen (GD + WebP para optimizar documentos)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip \
        libjpeg62-turbo-dev libpng-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_mysql mysqli gd \
    && rm -rf /var/lib/apt/lists/*

# 3. Composer (PHPMailer y futuras dependencias)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 4. Configurar Apache para que apunte a la carpeta /public (esencial para seguridad y enrutamiento MVC)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# 5. Instalar dependencias PHP antes de copiar todo (mejor caché de capas Docker)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && rm -rf /root/.composer/cache

# 6. Copiar el resto de la aplicación
COPY . /var/www/html/

# 7. Permisos de Apache + carpeta de documentos (fuera de public/)
RUN mkdir -p /var/www/html/storage/documentos \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
