FROM php:8.2-fpm

# Dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Dossier de travail
WORKDIR /var/www/html

# Copier le projet
COPY . .

# Installer dépendances Laravel
RUN composer install --no-dev --optimize-autoloader

# Permissions Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Port Render
EXPOSE 10000

# Lancer Laravel
CMD php artisan migrate --force \
    && artisan db:seed --force \
    && php artisan serve --host=0.0.0.0 --port=$PORT

