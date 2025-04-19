FROM php:8.3-apache

# Dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git curl vim libmariadb-dev \
    && docker-php-ext-install pdo_mysql zip

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Wait DB
COPY wait-for-mysql.php /usr/local/bin/wait-for-mysql.php
RUN chmod +x /usr/local/bin/wait-for-mysql.php

# Init script
COPY init.sh /usr/local/bin/init.sh
RUN chmod +x /usr/local/bin/init.sh

WORKDIR /var/www/html