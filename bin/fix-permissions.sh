#!/bin/bash
# ============================================
# Fix Docker Permission Issues
# Senior Dev Solution for VSCode + Docker
# ============================================

set -e

echo "🔧 Fixing file permissions for Docker..."

# Get current user UID/GID
USER_ID=$(id -u)
GROUP_ID=$(id -g)

echo "Host UID: $USER_ID"
echo "Host GID: $GROUP_ID"

# Fix permissions on new files (from VSCode)
echo "Fixing permissions on source files..."
docker compose exec -T php sh -c "
    chown -R www-data:www-data /var/www/html/src
    chown -R www-data:www-data /var/www/html/var
    chown -R www-data:www-data /var/www/html/public/pdfs
    chmod -R 775 /var/www/html/var
    chmod -R 775 /var/www/html/public/pdfs
"

echo "✅ Permissions fixed!"
echo ""
echo "ℹ️  For permanent fix, rebuild container with:"
echo "   export USER_ID=\$(id -u) GROUP_ID=\$(id -g)"
echo "   docker compose build --no-cache php"
echo "   docker compose up -d"
