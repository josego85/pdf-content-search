# ============================================
# Development Dockerfile
# Full tooling: Node.js, Composer, Git, etc.
# ============================================
FROM php:8.4.14-fpm-alpine

# Install runtime + development dependencies
RUN apk add --no-cache \
    # Runtime dependencies
    postgresql-libs \
    libpng \
    libjpeg-turbo \
    freetype \
    icu-libs \
    libxml2 \
    oniguruma \
    poppler-utils \
    # Development tools
    git \
    nodejs \
    npm \
    bash \
    vim \
    unzip

# Install PHP extensions
RUN apk add --no-cache --virtual .build-deps \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    libxml2-dev \
    oniguruma-dev \
    linux-headers \
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

# Install Xdebug for code coverage (development only)
# Version 3.4.0 is compatible with PHP 8.4
RUN apk add --no-cache --virtual .xdebug-deps $PHPIZE_DEPS \
    && pecl install xdebug-3.4.0 \
    && docker-php-ext-enable xdebug \
    && apk del .xdebug-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files first (so .docker/ directory is available)
COPY . .

# Copy PHP configuration files (now .docker/ is in build context)
COPY .docker/dev/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY .docker/dev/php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install PHP dependencies (skip scripts first, run them after)
RUN composer install --prefer-dist --no-interaction --no-scripts \
    && composer dump-autoload --optimize

# Install Node dependencies
RUN npm install --ignore-scripts

# Set permissions
RUN mkdir -p var/cache var/log var/sessions public/pdfs \
    && chown -R www-data:www-data var public/pdfs \
    && chmod -R 775 var public/pdfs

EXPOSE 9000

CMD ["php-fpm"]
