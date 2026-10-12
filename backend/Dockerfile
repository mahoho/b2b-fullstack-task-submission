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

# The compose healthcheck guarantees MySQL is up before this runs.
# Migrate and seed are both safe to repeat on restart.
CMD ["sh", "-c", "php artisan doctrine:migrations:migrate --no-interaction && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=8000"]
