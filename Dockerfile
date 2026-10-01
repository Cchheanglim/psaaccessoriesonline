# Production image for Render (or any Docker host).
#
#   docker build -t psaonlineaccessories .
#   docker run -p 8080:10000 --env-file .env psaonlineaccessories

# --- PHP dependencies -------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# --- Front-end assets -------------------------------------------------------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
# Tailwind scans Laravel's pagination views for class names.
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# --- Runtime ----------------------------------------------------------------
FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql opcache \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'expose_php = Off'; \
        echo 'upload_max_filesize = 6M'; \
        echo 'post_max_size = 8M'; \
        echo 'opcache.validate_timestamps = 0'; \
    } > "$PHP_INI_DIR/conf.d/app.ini"

# Serve only public/, hide the Apache version, and listen on Render's $PORT.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public PORT=10000
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    && sed -ri 's!Listen 80!Listen ${PORT}!' /etc/apache2/ports.conf \
    && sed -ri 's!<VirtualHost \*:80>!<VirtualHost *:${PORT}>!' /etc/apache2/sites-available/000-default.conf \
    && { echo 'ServerName localhost'; echo 'ServerTokens Prod'; echo 'ServerSignature Off'; } > /etc/apache2/conf-enabled/zz-hardening.conf

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint \
    && rm -rf resources/prototype tests node_modules

USER www-data
EXPOSE 10000
ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]
