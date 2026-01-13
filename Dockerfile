FROM php:8.2-cli

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
WORKDIR /app

# Copier les fichiers composer d'abord (cache Docker)
COPY composer.json composer.lock ./

# Installer dépendances Laravel
RUN composer install --no-dev --optimize-autoloader

# Copier le reste du projet
COPY . .

# Permissions Laravel
RUN chmod -R 775 storage bootstrap/cache

# Render injecte le port
EXPOSE 10000

# Commande de démarrage Render
CMD php artisan migrate --force \
 && php artisan db:seed --force \
 && php artisan serve --host=0.0.0.0 --port=$PORT
