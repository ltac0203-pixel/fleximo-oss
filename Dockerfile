# =============================================================================
# Fleximo - Google Cloud Run 用 Dockerfile
# Multi-stage build: Node.js (frontend) -> PHP (backend) -> Production
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: Frontend build (Vite + React)
# -----------------------------------------------------------------------------
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

COPY vite.config.js tsconfig*.json tailwind.config.* postcss.config.* ./
COPY resources/ resources/

RUN npm run build

# -----------------------------------------------------------------------------
# Stage 2: PHP dependencies
# -----------------------------------------------------------------------------
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

# -----------------------------------------------------------------------------
# Stage 3: Production image (Nginx + PHP-FPM)
# -----------------------------------------------------------------------------
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    icu-libs \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    oniguruma

# Install PHP extensions
RUN apk add --no-cache --virtual .build-deps \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        intl \
        zip \
        gd \
        opcache \
        bcmath \
        pcntl \
    && apk del .build-deps

# PHP production settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-fleximo.ini"

# Nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Supervisor config
COPY docker/supervisord.conf /etc/supervisord.conf

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY --from=composer /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

# Create required directories and set permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Optimize Laravel for production
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Cloud Run uses PORT env (default 8080)
ENV PORT=8080
EXPOSE 8080

# Start Nginx + PHP-FPM via Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
