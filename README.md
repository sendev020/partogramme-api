# 🩺 Partogramme OMS – API Laravel

API backend pour application Flutter de suivi du travail obstétrical (Partogramme OMS).

## Stack
- Laravel 10+
- PostgreSQL (Render)
- Auth: Sanctum (optionnel)
- API REST JSON

## Installation locale
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
# partogramme-api
