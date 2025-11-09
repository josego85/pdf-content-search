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

# Install PHP extensions + Xdebug (in single layer for build deps)
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
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
    && pecl install xdebug-3.4.0 \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy PHP configuration files first (specific files only)
COPY .docker/dev/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY .docker/dev/php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Copy dependency manifests first (for better layer caching)
COPY composer.json composer.lock ./

# Install PHP dependencies (will be cached unless composer.json/lock changes)
RUN composer install --prefer-dist --no-interaction --no-scripts \
    && composer dump-autoload --optimize

# Copy Node dependency manifests
COPY package.json package-lock.json ./

# Install Node dependencies (will be cached unless package.json/lock changes)
RUN npm install --ignore-scripts

# Copy application files (this layer only rebuilds when code changes)
COPY . .

# Set permissions
RUN mkdir -p var/cache var/log var/sessions public/pdfs \
    && chown -R www-data:www-data var public/pdfs \
    && chmod -R 775 var public/pdfs

EXPOSE 9000

CMD ["php-fpm"]
