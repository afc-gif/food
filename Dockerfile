# syntax=docker/dockerfile:1

FROM composer:2 AS composer_bin
FROM node:20-bullseye AS node_bin

# Builder: install PHP extensions, composer, node, and build assets
FROM php:8.3-fpm-bullseye AS builder

ARG DEBIAN_FRONTEND=noninteractive

RUN sed -i '/debian-security/d; s|deb.debian.org/debian|archive.debian.org/debian|g' /etc/apt/sources.list \
 && apt-get update -o Acquire::Check-Valid-Until=false \
 && apt-get install -y --no-install-recommends \
    git unzip zip curl libpq-dev libzip-dev libonig-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_pgsql gd zip bcmath mbstring pcntl \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer_bin /usr/bin/composer /usr/local/bin/composer
COPY --from=node_bin /usr/local/bin/node /usr/local/bin/node
COPY --from=node_bin /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
 && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

WORKDIR /app/backend

# Copy app code
COPY backend /app/backend

# Prepare writable dirs
RUN mkdir -p storage/framework/{cache,data,sessions,views} bootstrap/cache \
 && chmod -R 777 storage bootstrap/cache

ENV BROADCAST_CONNECTION=log

ENV COMPOSER_ALLOW_SUPERUSER=1

# Install PHP deps
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

# Install Node deps (with dev for build) and build assets, then clean node_modules
RUN npm ci --no-progress \
 && npm run build \
 && rm -rf node_modules

# Runtime image: Nginx + PHP-FPM with Opcache
FROM php:8.3-fpm-bullseye

ARG DEBIAN_FRONTEND=noninteractive

RUN sed -i '/debian-security/d; s|deb.debian.org/debian|archive.debian.org/debian|g' /etc/apt/sources.list \
 && apt-get update -o Acquire::Check-Valid-Until=false \
 && apt-get install -y --no-install-recommends \
    nginx curl libpq-dev libzip-dev libonig-dev gettext-base \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_pgsql gd zip bcmath mbstring pcntl \
 && rm -rf /var/lib/apt/lists/*

# Nginx + PHP config
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php-fpm-pool.conf /usr/local/etc/php-fpm.d/zz-custom-pool.conf

WORKDIR /app/backend

# Bring built app
COPY --from=builder /app/backend /app/backend

# Default envs (override in Render)
ENV APP_ENV=production \
    APP_URL=https://afc.com.ng \
    PORT=80

EXPOSE 80

COPY docker/start.sh /start.sh
RUN chmod +x /start.sh \
 && mkdir -p /var/log/nginx \
 && chown -R www-data:www-data storage bootstrap/cache

CMD ["/start.sh"]
