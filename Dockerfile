FROM php:8.2-cli

# Dépendances système + extensions PHP
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpq-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo pdo_pgsql zip gd bcmath

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Variables Laravel requises
ENV APP_ENV=production
ENV APP_KEY=base64:koPrU4yn+df3JMpnH5puQM5wUQOx4fQcPkIiNFUT37o=

WORKDIR /app

# Copier fichiers composer
COPY composer.json composer.lock ./

# Installer dépendances
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copier le reste du projet
COPY . .

# Créer .env si absent
RUN cp .env.example .env || true

# Permissions
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD php artisan migrate --force \
&& php artisan db:seed --force \
&& php artisan serve --host=0.0.0.0 --port=$PORT
