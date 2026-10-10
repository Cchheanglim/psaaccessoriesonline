#!/bin/sh
set -e

# Render tells the app which port to listen on through $PORT
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Cache config and routes using the environment variables set in Render
php artisan config:cache
php artisan route:cache

# Apply any new database changes (only migrations that haven't run yet). If one fails, the
# deploy stops here and Render keeps the previous version online.
php artisan migrate --force

# Fill a brand-new database with the catalog and default accounts. The seeders are additive
# (they only create what's missing), so this is safe on every deploy and never overwrites live data.
php artisan db:seed --force

chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
