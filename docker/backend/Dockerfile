# Backend Laravel (PHP-FPM) untuk deploy Docker penuh.
# Dipakai oleh: docker-compose.yml (service `backend`).
# CBT React TIDAK di sini — lihat docker-compose.cbt.yml untuk varian satu server.
FROM php:8.3-fpm

# Ekstensi yang dibutuhkan: pdo_mysql (DB), redis (cache/queue),
# gd + zip (maatwebsite/excel, dompdf, qrcode), mbstring/xml/curl (Laravel).
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql bcmath gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

# Install dependensi dulu (manfaatkan layer cache) lalu salin source.
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --prefer-dist

COPY backend/ ./

RUN composer dump-autoload --optimize \
    && php artisan storage:link --force || true \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
