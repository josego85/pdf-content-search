# ============================================
# Development Dockerfile
# Strategy: Extensions cached + deps via entrypointd
# ============================================

# Stage 1: Build PHP extensions (cached indefinitely)
FROM php:8.4.21-fpm-alpine@sha256:2e8d5b74437b02cbc3c632903d20a10fdcc956ba56d25bff951cc2b610767c9a AS php-extensions

# Runtime dependencies
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
    unzip

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
    libpq-dev libpng-dev libjpeg-turbo-dev freetype-dev icu-dev libxml2-dev oniguruma-dev linux-headers \
 && apk add --no-cache --virtual .phpize-deps autoconf dpkg-dev dpkg file libc-dev make pkgconf re2c \
 && ARCH=$(uname -m) \
 && ALPINE=$(cat /etc/alpine-release | cut -d. -f1,2) \
 && GCC_PKG=$(apk search -e gcc | grep "^gcc-" | head -1) \
 && GPP_PKG=$(apk search -e "g++" | grep "^g++-" | head -1) \
 && curl -sfL "https://dl-cdn.alpinelinux.org/alpine/v${ALPINE}/main/${ARCH}/${GCC_PKG}.apk" -o /tmp/gcc.apk \
 && curl -sfL "https://dl-cdn.alpinelinux.org/alpine/v${ALPINE}/main/${ARCH}/${GPP_PKG}.apk" -o /tmp/gpp.apk \
 && tar -xzf /tmp/gcc.apk -C / --exclude=".PKGINFO" --exclude=".SIGN.*" \
 && tar -xzf /tmp/gpp.apk -C / --exclude=".PKGINFO" --exclude=".SIGN.*" \
 && rm -f /tmp/gcc.apk /tmp/gpp.apk

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
FROM php:8.4.21-fpm-alpine@sha256:2e8d5b74437b02cbc3c632903d20a10fdcc956ba56d25bff951cc2b610767c9a

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
    ocrmypdf \
    tesseract-ocr-data-eng \
    tesseract-ocr-data-spa \
    tesseract-ocr-data-deu \
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

# Accept build arguments for user/group IDs (development)
ARG USER_ID=1000
ARG GROUP_ID=1000

# Create user with matching host UID/GID for development
# This prevents permission issues when VSCode creates files
RUN if [ "$USER_ID" != "82" ]; then \
    deluser www-data 2>/dev/null || true; \
    addgroup -g ${GROUP_ID} www-data; \
    adduser -D -u ${USER_ID} -G www-data www-data; \
    fi

# Entrypoint installs deps on bind-mounted volume at startup
COPY .docker/dev/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
