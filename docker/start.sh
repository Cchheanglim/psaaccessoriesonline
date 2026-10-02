#!/bin/sh
set -e

# Render tells the app which port to listen on through $PORT
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Cache config and routes using the environment variables set in Render
php artisan config:cache
php artisan route:cache
chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
