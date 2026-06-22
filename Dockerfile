FROM php:8.2-apache

# 1. Instalar las librerías del sistema para IMAP y caracteres especiales (mbstring)
RUN apt-get update && apt-get install -y \
    libc-client-dev \
    libkrb5-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Compilar e instalar las extensiones en PHP
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap mbstring

# 3. Copiar tu index.php directamente a la carpeta pública de Apache
COPY . /var/www/html/

# 4. Asegurar los permisos para que Apache pueda leer y servir tu archivo web
RUN chown -R www-data:www-data /var/www/html

# 5. El truco maestro para Render:
# Modifica el puerto de Apache en tiempo de ejecución para usar el puerto dinámico ($PORT)
# y luego arranca el servidor en primer plano de forma infinita.
CMD sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf && \
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/g" /etc/apache2/sites-available/000-default.conf && \
    apache2-foreground
