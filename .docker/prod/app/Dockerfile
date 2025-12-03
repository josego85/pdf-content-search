# ============================================
# Production Dockerfile - Multi-stage
# Optimized for size and security
# ============================================

# ============================================
# Stage 1: Base Image
# ============================================
FROM php:8.4.15-fpm-alpine AS base

# Install runtime dependencies only
RUN apk add --no-cache \
    postgresql-libs \
    libpng \
    libjpeg-turbo \
    freetype \
    icu-libs \
    libxml2 \
    oniguruma \
    poppler-utils \
    supervisor

# Install PHP extensions (build deps removed after)
RUN apk add --no-cache --virtual .build-deps \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    libxml2-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    mbstring \
    intl \
    exif \
    pcntl \
    bcmath \
    gd \
    && apk del .build-deps

WORKDIR /var/www/html

# ============================================
# Stage 2: Node Builder - Compile assets
# ============================================
FROM node:22-alpine AS node-builder

WORKDIR /build

COPY package.json package-lock.json ./

# Install dependencies (skip prepare scripts to avoid husky)
RUN npm ci --prefer-offline --no-audit --ignore-scripts

COPY assets ./assets
COPY webpack.config.js postcss.config.js tailwind.config.js ./

RUN npm run build

# ============================================
# Stage 3: Composer Builder - PHP dependencies
# ============================================
FROM composer:latest AS composer-builder

WORKDIR /build

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# ============================================
# Stage 4: Final Production Image
# ============================================
FROM base AS production

# Copy Composer binary temporarily
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files
COPY composer.json composer.lock ./

# Copy vendor from builder
COPY --from=composer-builder /build/vendor ./vendor

# Copy application files
COPY --chown=www-data:www-data . .

# Copy .env file for production
COPY --chown=www-data:www-data .env .env

# Configure production environment variables
RUN set -eux; \
    sed -i 's/APP_ENV=dev/APP_ENV=prod/' .env; \
    sed -i 's/APP_DEBUG=1/APP_DEBUG=0/' .env; \
    if grep -q "APP_SECRET=$" .env || grep -q "APP_SECRET=\"\"" .env; then \
        SECRET=$(head -c 32 /dev/urandom | base64 | tr -d '\n+=/' | head -c 32); \
        sed -i "s|APP_SECRET=.*|APP_SECRET=${SECRET}|" .env; \
    fi

# Copy built assets from node builder
COPY --from=node-builder --chown=www-data:www-data /build/public/build ./public/build

# Copy Supervisor configuration
COPY .docker/prod/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# Remove Composer (not needed in production)
RUN rm /usr/bin/composer

# Create necessary directories
RUN mkdir -p var/cache var/log var/sessions public/pdfs /var/log/supervisor \
    && chown -R www-data:www-data var public/pdfs \
    && chmod -R 775 var public/pdfs

# Supervisor runs as root, then drops privileges to www-data for workers
# DO NOT set USER www-data here

EXPOSE 9000

# Start Supervisor (manages PHP-FPM + Messenger Workers)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
