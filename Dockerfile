FROM php:8.3-apache

# 1. Habilitar el módulo rewrite de Apache para soportar las URLs amigables
RUN a2enmod rewrite

# 2. Instalar pdo, pdo_mysql y mysqli (necesarios para tu base de datos MariaDB de ConectaBarrio)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 3. Configurar Apache para que apunte a la carpeta /public (esencial para seguridad y enrutamiento MVC)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Copiar los archivos al contenedor
COPY . /var/www/html/

# 5. Permisos de Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
