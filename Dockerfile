FROM php:8.2-apache

# 1. Instalar dependencias del sistema y extensiones de PHP necesarias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Habilitar el módulo rewrite de Apache para soportar las reglas de enrutamiento (.htaccess)
RUN a2enmod rewrite

# 3. Configurar Apache para que apunte a la carpeta /public como directorio web raíz
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Copiar todo el código del proyecto al contenedor de Apache
COPY . /var/www/html/

# 5. Establecer los permisos adecuados para que Apache pueda leer y servir los archivos
RUN chown -R www-data:www-data /var/www/html

# 6. Exponer el puerto 80 estándar
EXPOSE 80
