FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Dev dependencies stay installed: the seeder needs fakerphp/faker.
RUN composer install --no-interaction --prefer-dist

EXPOSE 8000

# The compose healthcheck guarantees MySQL is up before this runs, and migrate and seed are both safe to repeat.
# APP_KEY is generated per start instead of committed: the API keeps no encrypted data, so nothing depends on it
# surviving a restart. --no-reload stops artisan serve from stripping the environment it passes to the PHP worker.
CMD ["sh", "-c", "export APP_KEY=\"base64:$(head -c 32 /dev/urandom | base64)\" && php artisan doctrine:migrations:migrate --no-interaction && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=8000 --no-reload"]
