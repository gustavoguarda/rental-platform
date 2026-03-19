FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    redis \
    && docker-php-ext-install pdo pdo_pgsql opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-scripts || true

COPY . .

EXPOSE 9000

CMD ["php-fpm"]
