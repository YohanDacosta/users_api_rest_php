FROM php:8.2-fpm

RUN apt-get update && apt-get install -y

RUN apt-get update && apt-get install -y \
        git \
        zlib1g-dev \
        libxml2-dev \
        libzip-dev \
    && docker-php-ext-install \ 
        zip \
        intl \
		mysqli \
        pdo pdo_mysql
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN mv composer.phar /usr/bin/composer
RUN php -r "unlink('composer-setup.php');"