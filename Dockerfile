FROM php:8.2-apache

# 1. Descargar el instalador automatizado de extensiones de PHP
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# 2. Darle permisos de ejecución e instalar imap y mbstring de forma directa
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions imap mbstring

# 3. Copiar tu index.php directamente a la carpeta pública de Apache
COPY . /var/www/html/

# 4. Asegurar los permisos para que Apache pueda leer y servir tu archivo web
RUN chown -R www-data:www-data /var/www/html

# 5. Configurar el puerto dinámico de Render en tiempo de ejecución y arrancar Apache
CMD sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf && \
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/g" /etc/apache2/sites-available/000-default.conf && \
    apache2-foreground
