# Used for the Render (Path B) deployment. Vercel does not use this file.
FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SESSION_DRIVER=cookie \
    CACHE_STORE=array \
    PHP_CLI_SERVER_WORKERS=4

EXPOSE 10000

# Migrate + seed on every start (the seeder skips itself if data already exists).
CMD ["sh", "-c", "php artisan migrate --force && php artisan db:seed --force && php -S 0.0.0.0:${PORT:-10000} -t public server.php"]
