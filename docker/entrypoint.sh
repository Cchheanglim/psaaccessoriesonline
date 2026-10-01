#!/bin/sh
set -e

# Fail fast instead of serving a half-configured app.
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generate one with: php artisan key:generate --show" >&2
    exit 1
fi

# Laravel reads env() only while building these caches, so they are built
# here, at start-up, from the real environment rather than at image build time.
php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
