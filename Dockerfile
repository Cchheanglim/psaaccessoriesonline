# Production image for Render (see render.yaml). Render builds and runs this
# on its own servers — you never need Docker on your own computer.
FROM php:8.2-apache

# PHP extensions: pdo_pgsql for Supabase, opcache for speed
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip git \
    && docker-php-ext-install pdo_pgsql pgsql bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: serve Laravel's public/ folder and allow its .htaccess rewrites
RUN a2enmod rewrite headers \
    && sed -ri 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!' /etc/apache2/sites-available/000-default.conf
COPY docker/apache.conf /etc/apache2/conf-enabled/zz-psaonline.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-psaonline.ini

WORKDIR /var/www/html

# Install PHP packages first so this layer is cached between deploys
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/start.sh /usr/local/bin/start.sh
RUN sed -i 's/\r$//' /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
