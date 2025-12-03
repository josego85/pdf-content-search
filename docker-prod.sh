#!/bin/bash
# ============================================
# Production Docker Management Script
# ============================================
set -e

PROJECT_NAME="pdf-search-prod"
COMPOSE_FILE="docker-compose.yml"

case "$1" in
  up)
    echo "🚀 Starting PRODUCTION environment..."
    docker compose -p "$PROJECT_NAME" -f "$COMPOSE_FILE" up -d
    echo "⏳ Waiting for database..."
    sleep 5
    echo "🔧 Running migrations..."
    docker compose -p "$PROJECT_NAME" -f "$COMPOSE_FILE" exec php php bin/console doctrine:migrations:migrate --no-interaction
    echo "✅ Production ready at http://localhost:8080"
    ;;
  down)
    echo "🛑 Stopping PRODUCTION environment..."
    docker compose -p "$PROJECT_NAME" -f "$COMPOSE_FILE" down
    ;;
  restart)
    echo "🔄 Restarting PRODUCTION environment..."
    docker compose -p "$PROJECT_NAME" -f "$COMPOSE_FILE" restart
    ;;
  logs)
    docker compose -p "$PROJECT_NAME" -f "$COMPOSE_FILE" logs -f "${@:2}"
    ;;
  exec)
    docker compose -p "$PROJECT_NAME" -f "$COMPOSE_FILE" exec "${@:2}"
    ;;
  build)
    echo "🏗️  Building PRODUCTION images..."
    docker compose -p "$PROJECT_NAME" -f "$COMPOSE_FILE" build "${@:2}"
    ;;
  clean)
    echo "🗑️  Removing PRODUCTION volumes (WARNING: data will be lost)..."
    read -p "Are you sure? (yes/no): " confirm
    if [ "$confirm" == "yes" ]; then
      docker compose -p "$PROJECT_NAME" -f "$COMPOSE_FILE" down -v
      echo "✅ Volumes removed"
    else
      echo "❌ Cancelled"
    fi
    ;;
  *)
    echo "Usage: $0 {up|down|restart|logs|exec|build|clean}"
    echo ""
    echo "Commands:"
    echo "  up      - Start production environment with migrations"
    echo "  down    - Stop production environment (keeps volumes)"
    echo "  restart - Restart all services"
    echo "  logs    - View logs (optional: service name)"
    echo "  exec    - Execute command in container"
    echo "  build   - Build production images"
    echo "  clean   - Remove all volumes (WARNING: deletes data)"
    exit 1
    ;;
esac
