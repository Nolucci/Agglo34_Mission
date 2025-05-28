FROM php:8.4-fpm-alpine

RUN apk update --no-cache && apk add --no-cache \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/html

COPY . /var/www/html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install

EXPOSE 9000

CMD ["php-fpm"]