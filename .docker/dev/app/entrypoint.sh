#!/bin/bash
set -e

# ============================================
# Development Entrypoint
# Installs dependencies on bind-mounted volume
# ============================================

# Install PHP dependencies if missing or outdated
if [ ! -d "vendor" ] || [ "composer.lock" -nt "vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    composer install --prefer-dist --no-interaction
fi

# Install Node dependencies if missing or outdated
if [ ! -d "node_modules" ] || [ "package-lock.json" -nt "node_modules/.package-lock.json" ]; then
    echo "Installing npm dependencies..."
    npm install --ignore-scripts
fi

# Create necessary directories
mkdir -p var/cache var/log var/sessions public/pdfs /var/log/supervisor

# Fix permissions
chown -R www-data:www-data var public/pdfs 2>/dev/null || true
chmod -R 775 var public/pdfs 2>/dev/null || true

# Execute CMD (supervisord)
exec "$@"
