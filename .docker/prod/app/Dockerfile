# ============================================
# Production Dockerfile - Multi-stage
# Optimized for size and security
# ============================================

# ============================================
# Stage 1: Base Image
# ============================================
FROM php:8.4.14-fpm-alpine AS base

# Install runtime dependencies only
RUN apk add --no-cache \
    postgresql-libs \
    libpng \
    libjpeg-turbo \
    freetype \
    icu-libs \
    libxml2 \
    oniguruma \
    poppler-utils

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

# Copy built assets from node builder
COPY --from=node-builder --chown=www-data:www-data /build/public/build ./public/build

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# Remove Composer (not needed in production)
RUN rm /usr/bin/composer

# Create necessary directories
RUN mkdir -p var/cache var/log var/sessions public/pdfs \
    && chown -R www-data:www-data var public/pdfs \
    && chmod -R 775 var public/pdfs

# Switch to non-root user
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
