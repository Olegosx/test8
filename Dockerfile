FROM php:8.2-cli-alpine

RUN apk add --no-cache sqlite-dev curl \
    && docker-php-ext-install pdo pdo_sqlite

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

RUN chmod +x /app/docker-entrypoint.sh

EXPOSE 8083

ENTRYPOINT ["/app/docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8083", "-t", "public/"]
