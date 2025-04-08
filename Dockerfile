FROM php:8.4-fpm

# 1. Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libjpeg-dev \
    libfreetype6-dev \
    poppler-utils \
    gnupg \
    ca-certificates \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Install required PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

# 3. Install Node.js and npm properly
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get update \
    && apt-get install -y nodejs \
    && npm install -g npm@latest tailwindcss postcss autoprefixer

# 4. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Set working directory
WORKDIR /var/www/html

# 6. Copy composer files first
COPY composer.json composer.lock ./

# 7. Install PHP dependencies
RUN composer install --no-scripts --no-autoloader

# 8. Copy package.json and install Node dependencies
COPY package*.json ./
RUN npm install

# 9. Copy the rest of the application
COPY . .

# 10. Generate optimized autoload files
RUN composer dump-autoload --optimize

# 11. Set file ownership
RUN chown -R www-data:www-data /var/www/html