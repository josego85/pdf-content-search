#!/bin/sh
# ============================================
# Production PHP Container Entrypoint
# ============================================
# Runs database migrations and messenger setup
# before handing off to Supervisor.
# ============================================

set -e

echo "[init] Waiting for database..."
until nc -z "${POSTGRES_HOST:-database}" "${POSTGRES_PORT:-5432}" > /dev/null 2>&1; do
    sleep 2
done
echo "[init] Database ready."

echo "[init] Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
echo "[init] Migrations done."

echo "[init] Setting up Messenger transports..."
php bin/console messenger:setup-transports
echo "[init] Messenger transports ready."

echo "[init] Warming up cache..."
php bin/console cache:warmup --env=prod
echo "[init] Cache warm."

echo "[init] Handing off to Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
