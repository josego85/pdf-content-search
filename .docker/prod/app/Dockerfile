# ============================================
# Production Dockerfile - Multi-stage
# Optimized for size and security
# ============================================

# ============================================
# Stage 1: Compiler — builds PHP extensions
# Separate stage so gcc/g++ never land in the production image.
# APK's HTTP client fails on large packages (gcc/g++) on Linux 7.x; use curl+tar instead.
# ============================================
FROM php:8.4.21-fpm-alpine@sha256:2e8d5b74437b02cbc3c632903d20a10fdcc956ba56d25bff951cc2b610767c9a AS compiler

# Build dependencies: APK's HTTP client fails on large packages (gcc/g++) on Linux 7.x.
# Strategy:
#   1. Install all build deps EXCEPT gcc/g++ via APK (small packages, no issue).
#   2. Create .phpize-deps virtual package WITHOUT gcc/g++ so docker-php-ext-{configure,install}
#      see it installed and skip their internal "apk add $PHPIZE_DEPS" call.
#   3. Download gcc/g++ via curl and extract via tar (standard I/O, not APK's broken path).
# $PHPIZE_DEPS expands to: autoconf dpkg-dev dpkg file g++ gcc libc-dev make pkgconf re2c
RUN apk update -q \
 && apk add --no-cache \
    autoconf dpkg-dev dpkg file libc-dev make pkgconf re2c \
    gmp isl26 mpfr4 mpc1 libatomic libgomp libstdc++ libstdc++-dev jansson binutils musl-dev \
    libpq-dev libpng-dev libjpeg-turbo-dev freetype-dev icu-dev libxml2-dev oniguruma-dev \
 && apk add --no-cache --virtual .phpize-deps autoconf dpkg-dev dpkg file libc-dev make pkgconf re2c \
 && ARCH=$(uname -m) \
 && ALPINE=$(cat /etc/alpine-release | cut -d. -f1,2) \
 && GCC_PKG=$(apk search -e gcc | grep "^gcc-" | head -1) \
 && GPP_PKG=$(apk search -e "g++" | grep "^g++-" | head -1) \
 && curl -sfL "https://dl-cdn.alpinelinux.org/alpine/v${ALPINE}/main/${ARCH}/${GCC_PKG}.apk" -o /tmp/gcc.apk \
 && curl -sfL "https://dl-cdn.alpinelinux.org/alpine/v${ALPINE}/main/${ARCH}/${GPP_PKG}.apk" -o /tmp/gpp.apk \
 && tar -xzf /tmp/gcc.apk -C / --exclude=".PKGINFO" --exclude=".SIGN.*" \
 && tar -xzf /tmp/gpp.apk -C / --exclude=".PKGINFO" --exclude=".SIGN.*" \
 && rm -f /tmp/gcc.apk /tmp/gpp.apk \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    mbstring \
    intl \
    exif \
    pcntl \
    bcmath \
    gd

# ============================================
# Stage 2: Base Image — lean runtime, no build deps
# ============================================
FROM php:8.4.21-fpm-alpine@sha256:2e8d5b74437b02cbc3c632903d20a10fdcc956ba56d25bff951cc2b610767c9a AS base

# Copy compiled extensions from compiler stage (gcc/g++ never touch this image)
COPY --from=compiler /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=compiler /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

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
    ocrmypdf \
    tesseract-ocr-data-eng \
    tesseract-ocr-data-spa \
    tesseract-ocr-data-deu \
    supervisor

WORKDIR /var/www/html

# ============================================
# Stage 2: Node Builder - Compile assets
# ============================================
FROM node:24-alpine@sha256:7fddd9ddeae8196abf4a3ef2de34e11f7b1a722119f91f28ddf1e99dcafdf114 AS node-builder

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
FROM composer:latest@sha256:743aebe48ca67097c36819040633ea77e44a561eca135e4fc84c002e63a1ba07 AS composer-builder

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

# Copy and configure entrypoint
COPY .docker/prod/app/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

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

# Entrypoint runs migrations + messenger setup, then starts Supervisor
ENTRYPOINT ["/entrypoint.sh"]
