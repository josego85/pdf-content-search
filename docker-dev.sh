#!/bin/bash
# ============================================
# Development Docker Management Script
# ============================================
set -e

case "$1" in
  up)
    echo "🚀 Starting DEVELOPMENT environment..."
    docker compose up -d
    echo "⏳ Waiting for database..."
    sleep 5
    echo "🔧 Running migrations..."
    docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
    echo "✅ Development ready at http://localhost"
    ;;
  down)
    echo "🛑 Stopping DEVELOPMENT environment..."
    docker compose down
    ;;
  restart)
    echo "🔄 Restarting DEVELOPMENT environment..."
    docker compose restart
    ;;
  logs)
    docker compose logs -f "${@:2}"
    ;;
  exec)
    docker compose exec "${@:2}"
    ;;
  build)
    echo "🏗️  Building DEVELOPMENT images..."
    docker compose build "${@:2}"
    ;;
  clean)
    echo "🗑️  Removing DEVELOPMENT volumes (WARNING: data will be lost)..."
    read -p "Are you sure? (yes/no): " confirm
    if [ "$confirm" == "yes" ]; then
      docker compose down -v
      echo "✅ Volumes removed"
    else
      echo "❌ Cancelled"
    fi
    ;;
  *)
    echo "Usage: $0 {up|down|restart|logs|exec|build|clean}"
    echo ""
    echo "Commands:"
    echo "  up      - Start development environment with migrations"
    echo "  down    - Stop development environment (keeps volumes)"
    echo "  restart - Restart all services"
    echo "  logs    - View logs (optional: service name)"
    echo "  exec    - Execute command in container"
    echo "  build   - Build development images"
    echo "  clean   - Remove all volumes (WARNING: deletes data)"
    exit 1
    ;;
esac
