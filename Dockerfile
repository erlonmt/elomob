FROM php:8.5-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev libicu-dev libzip-dev unzip git \
    && docker-php-ext-install pdo_pgsql mbstring intl bcmath zip \
    && pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-interaction --no-scripts
COPY . .
RUN composer dump-autoload --optimize --no-scripts && php artisan package:discover --no-interaction \
    && chmod +x docker/start.sh
EXPOSE 8000
ENTRYPOINT ["docker/start.sh"]
