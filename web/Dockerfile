# web/Dockerfile
FROM php:8.2-apache

# Extensiones necesarias para MySQLi
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Activar mod_rewrite si lo necesitas
RUN a2enmod rewrite

# Copiar código al contenedor
COPY . /var/www/html/

# Permisos básicos (opcional)
RUN chown -R www-data:www-data /var/www/html

# Puerto por defecto de Apache
EXPOSE 80
