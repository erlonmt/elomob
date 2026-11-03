# The API image. The default target, production, serves Laravel with PHP-FPM behind the nginx of compose.yaml, without
# the development packages. The test target adds them and runs the PHPUnit suites (docker compose run --rm test).
FROM php:8.5-fpm-bookworm AS base
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev libicu-dev libzip-dev unzip git \
    && docker-php-ext-install pdo_pgsql mbstring intl bcmath zip \
    && pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app

FROM base AS test
COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-interaction --no-scripts
COPY . .
RUN composer dump-autoload --optimize --no-scripts && php artisan package:discover --no-interaction \
    && cp .env.example .env && php artisan key:generate --no-interaction
CMD ["composer", "test"]

FROM base AS production
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts \
    && php artisan package:discover --no-interaction \
    && rm /usr/local/bin/composer \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/start.sh
EXPOSE 9000
ENTRYPOINT ["docker/start.sh"]
