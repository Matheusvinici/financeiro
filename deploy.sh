#!/bin/bash
cd /home/u809852588/domains/myfinancas.shop/public_html || exit 1
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder --force
php artisan config:cache
