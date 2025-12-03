# ============================================
# Development Dockerfile - OPTIMIZED
# Strategy: Maximum cache reuse + BuildKit
# Build time: ~30s (vs 2min+ before)
# ============================================

# Stage 1: Build PHP extensions (cached indefinitely)
FROM php:8.4.15-fpm-alpine AS php-extensions

# Install build + runtime deps in single layer
RUN apk add --no-cache \
    # Runtime dependencies (permanent)
    postgresql-libs \
    libpng \
    libjpeg-turbo \
    freetype \
    icu-libs \
    libxml2 \
    oniguruma \
    poppler-utils \
    git \
    nodejs \
    npm \
    bash \
    vim \
    unzip \
    # Build dependencies (for compilation)
    $PHPIZE_DEPS \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    libxml2-dev \
    oniguruma-dev \
    linux-headers

# Compile ALL PHP extensions + PCOV in parallel
# This layer is cached and NEVER recompiles unless PHP version changes
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    mbstring \
    intl \
    exif \
    pcntl \
    bcmath \
    gd \
    && pecl install -o -f pcov \
    && docker-php-ext-enable pcov

# Stage 2: Final development image
FROM php:8.4.15-fpm-alpine

# Copy compiled extensions from stage 1 (instant!)
COPY --from=php-extensions /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php-extensions /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Install ONLY runtime dependencies (no build-deps = smaller image)
RUN apk add --no-cache \
    postgresql-libs \
    libpng \
    libjpeg-turbo \
    freetype \
    icu-libs \
    libxml2 \
    oniguruma \
    poppler-utils \
    git \
    nodejs \
    npm \
    bash \
    vim \
    unzip \
    supervisor

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy PHP configuration files
COPY .docker/dev/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY .docker/dev/php/pcov.ini /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini

# Copy Supervisor configuration
COPY .docker/dev/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy dependency manifests first (better cache hit rate)
COPY composer.json composer.lock ./

# Use BuildKit cache mount for Composer (ultra-fast rebuilds)
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --prefer-dist --no-interaction --no-scripts \
    && composer dump-autoload --optimize

# Copy Node dependency manifests
COPY package.json package-lock.json ./

# Use BuildKit cache mount for npm (ultra-fast rebuilds)
RUN --mount=type=cache,target=/root/.npm \
    npm install --ignore-scripts

# Copy application files (only rebuilds when code changes)
COPY . .

# Accept build arguments for user/group IDs (development)
ARG USER_ID=1000
ARG GROUP_ID=1000

# Create user with matching host UID/GID for development
# This prevents permission issues when VSCode creates files
RUN if [ "$USER_ID" != "82" ]; then \
    # Delete existing www-data user
    deluser www-data 2>/dev/null || true; \
    # Create new www-data with host UID/GID
    addgroup -g ${GROUP_ID} www-data; \
    adduser -D -u ${USER_ID} -G www-data www-data; \
    fi

# Set permissions
RUN mkdir -p var/cache var/log var/sessions public/pdfs /var/log/supervisor \
    && chown -R www-data:www-data var public/pdfs \
    && chmod -R 775 var public/pdfs

EXPOSE 9000

# Start Supervisor (manages PHP-FPM + Messenger Worker)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
