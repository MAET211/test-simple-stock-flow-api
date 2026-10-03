# syntax=docker/dockerfile:1.7
FROM php:8.3-fpm-bookworm

ARG INSTALL_DEV=0
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx supervisor curl git unzip libicu-dev libzip-dev libonig-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath intl mbstring zip opcache pcntl \
    && rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN if [ "$INSTALL_DEV" = "1" ]; then \
        composer install --no-scripts --no-autoloader --prefer-dist; \
    else \
        composer install --no-dev --no-scripts --no-autoloader --prefer-dist; \
    fi

COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/cache storage/framework/views storage/logs bootstrap/cache /var/media \
    && chown -R www-data:www-data storage bootstrap/cache /var/media

EXPOSE 8000

HEALTHCHECK --interval=10s --timeout=3s --start-period=15s --retries=5 \
    CMD curl -fsS http://127.0.0.1:8000/health || exit 1

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
