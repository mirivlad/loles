FROM node:22-alpine AS assets
WORKDIR /src
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY scripts ./scripts
COPY public ./public
RUN npm run build

FROM php:8.5-apache-bookworm
LABEL org.opencontainers.image.source="https://github.com/mirivlad/loles"

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_ALLOW_SUPERUSER=1

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates curl git unzip \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
        libicu-dev libonig-dev libzip-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j2 bcmath exif gd intl mbstring opcache pdo_mysql zip; \
    a2enmod rewrite headers; \
    sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf; \
    printf '%s\n' '<Directory /var/www/html/public>' 'AllowOverride All' 'Require all granted' '</Directory>' \
        > /etc/apache2/conf-available/loles.conf; \
    a2enconf loles; \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --no-progress --prefer-dist --optimize-autoloader

COPY . .
COPY --from=assets /src/public/build ./public/build
COPY --from=assets /src/public/tinymce/js/tinymce ./public/tinymce/js/tinymce
COPY --from=assets /src/public/vendor/fullcalendar ./public/vendor/fullcalendar
RUN composer dump-autoload --no-dev --no-scripts --optimize && \
    php artisan package:discover --ansi && \
    mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/app/public storage/logs bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-loles.ini
COPY docker/entrypoint.sh /usr/local/bin/loles-entrypoint
RUN chmod 755 /usr/local/bin/loles-entrypoint

EXPOSE 80
ENTRYPOINT ["loles-entrypoint"]
CMD ["apache2-foreground"]
