FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-scripts

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY --from=vendor /app /app
RUN npm run build

FROM dunglas/frankenphp:1-php8.3
WORKDIR /app

# install-php-extensions purges apt packages, so curl/ffmpeg/python come after it.
RUN install-php-extensions \
    pdo_mysql \
    gd \
    intl \
    zip \
    bcmath \
    exif \
    pcntl \
    opcache \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        ffmpeg \
        python3 \
        python-is-python3 \
    && rm -rf /var/lib/apt/lists/* \
    && printf '%s\n' \
        'upload_max_filesize=256M' \
        'post_max_size=256M' \
        'memory_limit=512M' \
        'max_execution_time=600' \
        'max_input_time=600' \
        > /usr/local/etc/php/conf.d/uploads.ini

ENV SERVER_NAME=":80"
ENV PHP_OPCACHE_ENABLE=1

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
COPY docker/entrypoint.sh /usr/local/bin/qaari-entrypoint
RUN chmod +x /usr/local/bin/qaari-entrypoint \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/tmp bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
# Static file — avoids Laravel /up during first boot while migrate is still running.
# Coolify requires curl/wget in the image.
HEALTHCHECK --interval=15s --timeout=5s --start-period=180s --retries=5 \
    CMD curl -fsS http://127.0.0.1:80/healthz || exit 1

ENTRYPOINT ["/usr/local/bin/qaari-entrypoint"]
